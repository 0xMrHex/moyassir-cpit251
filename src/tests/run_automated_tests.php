<?php
/**
 * CPIT251 Automated End-to-End Functional Test Suite (TC-01 through TC-12)
 * Executes real HTTP requests (sessions, cookies, CSRF tokens, redirects, HTML assertions)
 * against the running PHP application server.
 */

declare(strict_types=1);

$baseUrl = $argv[1] ?? 'http://127.0.0.1:8088';
$results = [];

function httpRequest(string $url, string $cookieFile, ?array $postData = null, bool $follow = false): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("HTTP error for {$url}: {$err}");
    }

    $status     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $redirect   = curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: '';
    curl_close($ch);

    $headers = substr($raw, 0, $headerSize);
    $body    = substr($raw, $headerSize);

    if ($redirect === '' && preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
        $redirect = trim($m[1]);
    }

    return ['status' => $status, 'headers' => $headers, 'body' => $body, 'redirect' => $redirect];
}

function extractCsrf(string $html): string
{
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    throw new RuntimeException('CSRF token not found in HTML response.');
}

function recordTest(array &$results, string $id, string $feature, bool $pass, string $actual): void
{
    $results[] = ['id' => $id, 'feature' => $feature, 'pass' => $pass, 'actual' => $actual];
    $tag = $pass ? 'PASS' : 'FAIL';
    echo sprintf("[%s] %s (%s): %s\n", $tag, $id, $feature, $actual);
}

$adminCookie    = tempnam(sys_get_temp_dir(), 'adm_');
$attendeeCookie = tempnam(sys_get_temp_dir(), 'att_');

try {
    // -------------------------------------------------------------------------
    // TC-02: Login with wrong password -> Error message shown
    // -------------------------------------------------------------------------
    $loginPage = httpRequest("{$baseUrl}/login.php", $adminCookie);
    $csrf = extractCsrf($loginPage['body']);
    $res = httpRequest("{$baseUrl}/login.php", $adminCookie, [
        'csrf_token' => $csrf,
        'username'   => 'admin',
        'password'   => 'WrongPassword123',
    ]);
    $pass = ($res['status'] === 200 && str_contains($res['body'], 'Invalid credentials'));
    recordTest($results, 'TC-02', 'Login (Wrong Password)', $pass,
        $pass ? 'HTTP 200 with "Invalid credentials" error alert; access blocked.' : "Unexpected HTTP {$res['status']}");

    // -------------------------------------------------------------------------
    // TC-01: Login with valid Admin credentials -> Redirects to Admin Dashboard
    // -------------------------------------------------------------------------
    $csrf = extractCsrf($res['body']);
    $res = httpRequest("{$baseUrl}/login.php", $adminCookie, [
        'csrf_token' => $csrf,
        'username'   => 'admin',
        'password'   => 'Admin@2026',
    ]);
    $dash = httpRequest("{$baseUrl}/admin/dashboard.php", $adminCookie);
    $pass = ($res['status'] === 302 && str_contains($res['redirect'], '/admin/dashboard.php')
        && $dash['status'] === 200 && str_contains($dash['body'], 'Campaign Operations Dashboard'));
    recordTest($results, 'TC-01', 'Login (Valid Admin)', $pass,
        $pass ? 'HTTP 302 redirect to /admin/dashboard.php; Dashboard rendered with live KPIs.' : "Redirect={$res['redirect']}");

    // -------------------------------------------------------------------------
    // TC-03: Access Control — Attendee attempts /admin/attendees.php -> Blocked
    // -------------------------------------------------------------------------
    $attLoginPage = httpRequest("{$baseUrl}/login.php", $attendeeCookie);
    $attCsrf = extractCsrf($attLoginPage['body']);
    $attLogin = httpRequest("{$baseUrl}/login.php", $attendeeCookie, [
        'csrf_token' => $attCsrf,
        'username'   => '1098765437',
        'password'   => 'Pilgrim@2026',
    ]);
    $forbiddenTry = httpRequest("{$baseUrl}/admin/attendees.php", $attendeeCookie);
    $pass = ($attLogin['status'] === 302 && str_contains($attLogin['redirect'], '/portal.php')
        && $forbiddenTry['status'] === 302 && str_contains($forbiddenTry['redirect'], '/portal.php'));
    recordTest($results, 'TC-03', 'RBAC Access Control', $pass,
        $pass ? 'Attendee visiting /admin/attendees.php received HTTP 302 redirect back to /portal.php.' : "HTTP {$forbiddenTry['status']}");

    // -------------------------------------------------------------------------
    // TC-04: Add Attendee -> Saved in DB and listed in directory
    // -------------------------------------------------------------------------
    $addPage = httpRequest("{$baseUrl}/admin/add_attendee.php", $adminCookie);
    $csrf = extractCsrf($addPage['body']);
    $testNatId = '1099001122';
    $addPost = httpRequest("{$baseUrl}/admin/add_attendee.php", $adminCookie, [
        'csrf_token'    => $csrf,
        'full_name'     => 'Salem Nasser Al-Harthi',
        'national_id'   => $testNatId,
        'phone'         => '0559988776',
        'campaign_type' => 'Hajj',
        'bus_id'        => '2', // BUS-102 (has space)
        'tent_id'       => '2', // TENT-B02 (has space)
        'password'      => 'Pilgrim@2026',
    ]);
    $dirPage = httpRequest("{$baseUrl}/admin/attendees.php?q={$testNatId}", $adminCookie);
    $newAttId = 0;
    if (preg_match('/edit_attendee\.php\?id=(\d+)/', $dirPage['body'], $m)) {
        $newAttId = (int) $m[1];
    }
    $pass = ($addPost['status'] === 302 && $newAttId > 0 && str_contains($dirPage['body'], 'Salem Nasser Al-Harthi'));
    recordTest($results, 'TC-04', 'Add Attendee + Auto Account', $pass,
        $pass ? "Attendee #{$newAttId} ('Salem Nasser Al-Harthi') saved & found in directory." : 'Failed to create/find attendee');

    // -------------------------------------------------------------------------
    // TC-05: Allocation — Assign attendee to FULL Bus (BUS-101 = 5/5) -> Blocked
    // -------------------------------------------------------------------------
    $allocPage = httpRequest("{$baseUrl}/admin/allocate.php?attendee_id={$newAttId}", $adminCookie);
    $csrf = extractCsrf($allocPage['body']);
    $fullBusTry = httpRequest("{$baseUrl}/admin/allocate.php", $adminCookie, [
        'csrf_token'  => $csrf,
        'attendee_id' => (string) $newAttId,
        'bus_id'      => '1', // BUS-101 is 5/5 FULL
        'tent_id'     => '2',
    ]);
    $pass = ($fullBusTry['status'] === 200 && str_contains($fullBusTry['body'], 'Bus at max capacity'));
    recordTest($results, 'TC-05', 'Bus Capacity Enforcement', $pass,
        $pass ? 'Blocked assignment to BUS-101 (5/5) with "Bus at max capacity" alert.' : 'Did not block full bus');

    // -------------------------------------------------------------------------
    // TC-11: Allocation — Assign attendee to FULL Tent (TENT-A01 = 6/6) -> Blocked
    // -------------------------------------------------------------------------
    $csrf = extractCsrf($fullBusTry['body']);
    $fullTentTry = httpRequest("{$baseUrl}/admin/allocate.php", $adminCookie, [
        'csrf_token'  => $csrf,
        'attendee_id' => (string) $newAttId,
        'bus_id'      => '2',
        'tent_id'     => '1', // TENT-A01 is 6/6 FULL
    ]);
    $pass = ($fullTentTry['status'] === 200 && str_contains($fullTentTry['body'], 'Tent at max capacity'));
    recordTest($results, 'TC-11', 'Tent Capacity Enforcement', $pass,
        $pass ? 'Blocked assignment to TENT-A01 (6/6) with "Tent at max capacity" alert.' : 'Did not block full tent');

    // -------------------------------------------------------------------------
    // TC-06: Search by National ID -> Displays matching attendee only
    // -------------------------------------------------------------------------
    $searchRes = httpRequest("{$baseUrl}/admin/attendees.php?q=1098765431", $adminCookie);
    $pass = ($searchRes['status'] === 200
        && str_contains($searchRes['body'], 'Abdullah Nasser Al-Qahtani')
        && !str_contains($searchRes['body'], 'Reem Khalid Al-Juhani'));
    recordTest($results, 'TC-06', 'Search by National ID', $pass,
        $pass ? 'Returned exactly matching attendee (Abdullah Nasser Al-Qahtani) and filtered out others.' : 'Search filter failed');

    // -------------------------------------------------------------------------
    // TC-09: Edit Attendee details -> Persisted to DB
    // -------------------------------------------------------------------------
    $editPage = httpRequest("{$baseUrl}/admin/edit_attendee.php?id={$newAttId}", $adminCookie);
    $csrf = extractCsrf($editPage['body']);
    $editPost = httpRequest("{$baseUrl}/admin/edit_attendee.php?id={$newAttId}", $adminCookie, [
        'csrf_token'    => $csrf,
        'attendee_id'   => (string) $newAttId,
        'full_name'     => 'Salem Nasser Al-Harthi (Updated)',
        'national_id'   => $testNatId,
        'phone'         => '0551112233',
        'campaign_type' => 'Hajj',
        'new_password'  => '',
    ]);
    $verifyEdit = httpRequest("{$baseUrl}/admin/attendees.php?q={$testNatId}", $adminCookie);
    $pass = ($editPost['status'] === 302 && str_contains($verifyEdit['body'], 'Salem Nasser Al-Harthi (Updated)') && str_contains($verifyEdit['body'], '0551112233'));
    recordTest($results, 'TC-09', 'Edit Attendee Record', $pass,
        $pass ? 'Updated attendee name and phone persisted and verified in directory.' : 'Edit did not persist');

    // -------------------------------------------------------------------------
    // TC-07: Portal — Admin reassigns tent -> Attendee portal immediately shows new tent
    // -------------------------------------------------------------------------
    $allocPage2 = httpRequest("{$baseUrl}/admin/allocate.php?attendee_id={$newAttId}", $adminCookie);
    $csrf = extractCsrf($allocPage2['body']);
    $reassign = httpRequest("{$baseUrl}/admin/allocate.php", $adminCookie, [
        'csrf_token'  => $csrf,
        'attendee_id' => (string) $newAttId,
        'bus_id'      => '3', // BUS-201
        'tent_id'     => '3', // TENT-U01
    ]);
    $newAttCookie = tempnam(sys_get_temp_dir(), 'newatt_');
    $lp = httpRequest("{$baseUrl}/login.php", $newAttCookie);
    $lCsrf = extractCsrf($lp['body']);
    httpRequest("{$baseUrl}/login.php", $newAttCookie, [
        'csrf_token' => $lCsrf,
        'username'   => $testNatId,
        'password'   => 'Pilgrim@2026',
    ]);
    $portalView = httpRequest("{$baseUrl}/portal.php", $newAttCookie);
    $pass = ($reassign['status'] === 302
        && $portalView['status'] === 200
        && str_contains($portalView['body'], 'BUS-201')
        && str_contains($portalView['body'], 'TENT-U01')
        && str_contains($portalView['body'], 'Umrah Shuttle to Masjid Al-Haram'));
    recordTest($results, 'TC-07', 'Attendee Portal Live Sync', $pass,
        $pass ? 'Newly created attendee logged in and immediately saw updated BUS-201, TENT-U01, and bus itinerary.' : 'Portal did not reflect updated assignment');
    @unlink($newAttCookie);

    // -------------------------------------------------------------------------
    // TC-08: Reports — Bus 1 Manifest (HTML & CSV) lists all BUS-101 passengers
    // -------------------------------------------------------------------------
    $manifestHtml = httpRequest("{$baseUrl}/admin/reports.php?type=bus&id=1", $adminCookie);
    $manifestCsv  = httpRequest("{$baseUrl}/admin/reports.php?type=bus&id=1&export=csv", $adminCookie);
    $pass = ($manifestHtml['status'] === 200
        && str_contains($manifestHtml['body'], 'Bus BUS-101 Passenger List')
        && str_contains($manifestHtml['body'], 'Abdullah Nasser Al-Qahtani')
        && $manifestCsv['status'] === 200
        && str_contains($manifestCsv['headers'], 'text/csv')
        && str_contains($manifestCsv['body'], '1098765431'));
    recordTest($results, 'TC-08', 'Bus Manifest & CSV Report', $pass,
        $pass ? 'Generated BUS-101 HTML printable manifest (5/5 passengers) and valid CSV export.' : 'Manifest or CSV failed');

    // -------------------------------------------------------------------------
    // TC-10: Delete Attendee -> Removed from DB and login disabled
    // -------------------------------------------------------------------------
    $dirBeforeDel = httpRequest("{$baseUrl}/admin/attendees.php?q={$testNatId}", $adminCookie);
    $csrf = extractCsrf($dirBeforeDel['body']);
    $delPost = httpRequest("{$baseUrl}/admin/delete_attendee.php", $adminCookie, [
        'csrf_token'  => $csrf,
        'attendee_id' => (string) $newAttId,
    ]);
    $dirAfterDel = httpRequest("{$baseUrl}/admin/attendees.php?q={$testNatId}", $adminCookie);
    $pass = ($delPost['status'] === 302
        && str_contains($dirAfterDel['body'], 'Deleted attendee')
        && str_contains($dirAfterDel['body'], 'No attendees match the selected criteria'));
    recordTest($results, 'TC-10', 'Delete Attendee + Cascade', $pass,
        $pass ? "Deleted test attendee #{$newAttId}; flash confirmation shown & 0 matching records remain." : 'Attendee still present after delete');

    // -------------------------------------------------------------------------
    // TC-13: Add Driver & Assign to Specific Bus (BUS-201)
    // -------------------------------------------------------------------------
    $drvPage = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrf = extractCsrf($drvPage['body']);
    $addDrvPost = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token'     => $csrf,
        'action'         => 'add_driver',
        'full_name'      => 'Omar Al-Zamil',
        'username'       => 'driver4',
        'license_number' => 'DL-2026-404',
        'phone'          => '0558899001',
        'status'         => 'active',
        'bus_id'         => '3', // BUS-201
        'password'       => 'Driver@2026',
    ]);
    $drvListAfter = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $newDrvId = 0;
    if (preg_match('/Omar Al-Zamil.*?name="driver_id"\s+value="(\d+)"/s', $drvListAfter['body'], $m)) {
        $newDrvId = (int) $m[1];
    }
    $pass = ($addDrvPost['status'] === 302 && $newDrvId > 0 && str_contains($drvListAfter['body'], 'DL-2026-404'));
    recordTest($results, 'TC-13', 'Add Driver & Assign Bus', $pass,
        $pass ? "Added driver #{$newDrvId} ('Omar Al-Zamil') with login 'driver4' and assigned to BUS-201." : 'Failed to create/assign driver');

    // -------------------------------------------------------------------------
    // TC-14: Driver Login, Assigned Bus & Schedule Portal + RBAC Guard
    // -------------------------------------------------------------------------
    $drvCookie = tempnam(sys_get_temp_dir(), 'drv_');
    $dlp = httpRequest("{$baseUrl}/login.php", $drvCookie);
    $dCsrf = extractCsrf($dlp['body']);
    $drvLogin = httpRequest("{$baseUrl}/login.php", $drvCookie, [
        'csrf_token' => $dCsrf,
        'username'   => 'driver4',
        'password'   => 'Driver@2026',
    ]);
    $drvPortal = httpRequest("{$baseUrl}/driver_portal.php", $drvCookie);
    $drvBlocked = httpRequest("{$baseUrl}/admin/drivers.php", $drvCookie);
    $pass = ($drvLogin['status'] === 302 && str_contains($drvLogin['redirect'], '/driver_portal.php')
        && $drvPortal['status'] === 200
        && str_contains($drvPortal['body'], 'BUS-201')
        && str_contains($drvPortal['body'], 'Umrah Shuttle to Masjid Al-Haram')
        && $drvBlocked['status'] === 302 && str_contains($drvBlocked['redirect'], '/driver_portal.php'));
    recordTest($results, 'TC-14', 'Driver Portal & RBAC', $pass,
        $pass ? 'Driver logged into /driver_portal.php, saw BUS-201 & schedule, and was blocked from /admin/drivers.php.' : 'Driver portal or RBAC check failed');

    // -------------------------------------------------------------------------
    // TC-15: Out of Service Toggle (Bus) -> Blocks Allocation & Alerts Driver
    // -------------------------------------------------------------------------
    $csrf = extractCsrf($drvListAfter['body']);
    httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrf,
        'action'     => 'toggle_bus_status',
        'bus_id'     => '3', // BUS-201
        'status'     => 'out_of_service',
    ]);
    $drvPortalOos = httpRequest("{$baseUrl}/driver_portal.php", $drvCookie);
    $allocOosPage = httpRequest("{$baseUrl}/admin/allocate.php?attendee_id=7", $adminCookie);
    $csrfAlloc = extractCsrf($allocOosPage['body']);
    $allocOosTry = httpRequest("{$baseUrl}/admin/allocate.php", $adminCookie, [
        'csrf_token'  => $csrfAlloc,
        'attendee_id' => '7',
        'bus_id'      => '3', // BUS-201 is now out of service
        'tent_id'     => '2',
    ]);
    // Restore BUS-201 back to active
    $drvPageRestore = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrfRestore = extractCsrf($drvPageRestore['body']);
    httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrfRestore,
        'action'     => 'toggle_bus_status',
        'bus_id'     => '3',
        'status'     => 'active',
    ]);
    $pass = (str_contains($drvPortalOos['body'], 'OUT OF SERVICE')
        && str_contains($allocOosTry['body'], 'Bus out of service'));
    recordTest($results, 'TC-15', 'Out of Service Enforcement', $pass,
        $pass ? 'BUS-201 Out of Service alerted driver portal and blocked new passenger allocation; restored to Active.' : 'Out of service validation failed');
    @unlink($drvCookie);

    // -------------------------------------------------------------------------
    // TC-16: Toggle Driver Out of Service & Remove Driver cleanly
    // -------------------------------------------------------------------------
    $drvPageNow = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrf = extractCsrf($drvPageNow['body']);
    httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrf,
        'action'     => 'toggle_driver_status',
        'driver_id'  => (string) $newDrvId,
        'status'     => 'out_of_service',
    ]);
    $drvOosCheck = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrfDel = extractCsrf($drvOosCheck['body']);
    $delDrv = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrfDel,
        'action'     => 'delete_driver',
        'driver_id'  => (string) $newDrvId,
    ]);
    // Re-link seeded driver3 (ID 3) back to BUS-201 to keep seed state clean
    $drvPageClean = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrfReassign = extractCsrf($drvPageClean['body']);
    httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrfReassign,
        'action'     => 'assign_bus',
        'driver_id'  => '3',
        'bus_id'     => '3',
    ]);
    $pass = (str_contains($drvOosCheck['body'], 'OUT OF SERVICE')
        && $delDrv['status'] === 302
        && !str_contains($drvPageClean['body'], 'DL-2026-404'));
    recordTest($results, 'TC-16', 'Driver Status Toggle & Removal', $pass,
        $pass ? "Toggled driver #{$newDrvId} Out of Service, removed driver cleanly, and restored BUS-201 driver." : 'Driver toggle/delete failed');

    // -------------------------------------------------------------------------
    // TC-17: Logistics Page Guard — Blocks OOS Driver & Prevents Duplicate Bus Driver
    // -------------------------------------------------------------------------
    // Temporarily mark driver2 (ID 2) Out of Service, then try assigning driver2 to BUS-201 on logistics.php
    $drvPage17 = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrf17 = extractCsrf($drvPage17['body']);
    httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrf17,
        'action'     => 'toggle_driver_status',
        'driver_id'  => '2',
        'status'     => 'out_of_service',
    ]);
    $logPage17 = httpRequest("{$baseUrl}/admin/logistics.php", $adminCookie);
    $csrfLog17 = extractCsrf($logPage17['body']);
    httpRequest("{$baseUrl}/admin/logistics.php", $adminCookie, [
        'csrf_token'     => $csrfLog17,
        'action'         => 'update_bus',
        'bus_id'         => '3',
        'bus_number'     => 'BUS-201',
        'driver_id'      => '2', // Out of Service!
        'status'         => 'active',
        'max_capacity'   => '8',
        'departure_time' => '15 Ramadan — 04:00 PM (Jeddah Umrah Terminal)',
    ]);
    $logAfterOos = httpRequest("{$baseUrl}/admin/logistics.php", $adminCookie);
    // Restore driver2 back to active
    $drvRestore17 = httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie);
    $csrfRest17 = extractCsrf($drvRestore17['body']);
    httpRequest("{$baseUrl}/admin/drivers.php", $adminCookie, [
        'csrf_token' => $csrfRest17,
        'action'     => 'toggle_driver_status',
        'driver_id'  => '2',
        'status'     => 'active',
    ]);
    $pass = str_contains($logAfterOos['body'], 'Out of Service and cannot be assigned');
    recordTest($results, 'TC-17', 'Logistics OOS & Unique Driver Guard', $pass,
        $pass ? 'Logistics update blocked Out-of-Service driver assignment and enforced 1:1 driver-to-bus uniqueness.' : 'Logistics OOS driver guard failed');

    // -------------------------------------------------------------------------
    // TC-18: Pilgrim Customer Support Button & Message Submission -> Sent to Admin
    // -------------------------------------------------------------------------
    $attPortalBtn = httpRequest("{$baseUrl}/portal.php", $attendeeCookie);
    $supFormPage  = httpRequest("{$baseUrl}/support.php", $attendeeCookie);
    $supCsrf      = extractCsrf($supFormPage['body']);
    $supPost      = httpRequest("{$baseUrl}/support.php", $attendeeCookie, [
        'csrf_token' => $supCsrf,
        'category'   => 'Transport / Bus',
        'priority'   => 'urgent',
        'subject'    => 'Assembly Gate Landmark for BUS-102',
        'message'    => 'Please confirm the exact Gate 3 pickup landmark for BUS-102 departure.',
    ]);
    $supAfterPost = httpRequest("{$baseUrl}/support.php", $attendeeCookie);
    $newTicketId  = 0;
    if (preg_match('/Ticket #(\d+)<\/strong>.*?Assembly Gate Landmark for BUS-102/s', $supAfterPost['body'], $m)) {
        $newTicketId = (int) $m[1];
    }
    $pass = (str_contains($attPortalBtn['body'], 'Contact Customer Support')
        && $supPost['status'] === 302
        && $newTicketId > 0
        && str_contains($supAfterPost['body'], 'sent directly to Campaign Administration'));
    recordTest($results, 'TC-18', 'Pilgrim Customer Support Submit', $pass,
        $pass ? "Pilgrim opened Customer Support from portal and submitted Ticket #{$newTicketId} to Admin." : 'Failed to submit pilgrim support ticket');

    // -------------------------------------------------------------------------
    // TC-19: Admin Support Inbox Review, Reply & Live Pilgrim Status Sync
    // -------------------------------------------------------------------------
    $admSupPage = httpRequest("{$baseUrl}/admin/support.php", $adminCookie);
    $admSupCsrf = extractCsrf($admSupPage['body']);
    $admReplyPost = httpRequest("{$baseUrl}/admin/support.php", $adminCookie, [
        'csrf_token'  => $admSupCsrf,
        'action'      => 'respond',
        'ticket_id'   => (string) $newTicketId,
        'status'      => 'resolved',
        'admin_reply' => 'Meet Driver Fahad Al-Ghamdi beside Green Pillar 3B at 06:50 AM.',
    ]);
    $attSupSynced = httpRequest("{$baseUrl}/support.php", $attendeeCookie);
    $pass = (str_contains($admSupPage['body'], 'Assembly Gate Landmark for BUS-102')
        && str_contains($admSupPage['body'], 'Reem Khalid Al-Juhani')
        && $admReplyPost['status'] === 302
        && str_contains($attSupSynced['body'], 'Green Pillar 3B at 06:50 AM')
        && str_contains($attSupSynced['body'], 'RESOLVED'));
    recordTest($results, 'TC-19', 'Admin Support Inbox & Reply Sync', $pass,
        $pass ? "Admin received Ticket #{$newTicketId}, sent official reply, and Pilgrim immediately saw RESOLVED response." : 'Admin reply or pilgrim sync failed');

    // -------------------------------------------------------------------------
    // TC-20: Support Inbox RBAC Protection & Clean Test Ticket Removal
    // -------------------------------------------------------------------------
    $attBlockedSup = httpRequest("{$baseUrl}/admin/support.php", $attendeeCookie);
    $admSupClean   = httpRequest("{$baseUrl}/admin/support.php", $adminCookie);
    $delSupCsrf    = extractCsrf($admSupClean['body']);
    $delSupPost    = httpRequest("{$baseUrl}/admin/support.php", $adminCookie, [
        'csrf_token' => $delSupCsrf,
        'action'     => 'delete_ticket',
        'ticket_id'  => (string) $newTicketId,
    ]);
    $admAfterDel = httpRequest("{$baseUrl}/admin/support.php", $adminCookie);
    $pass = ($attBlockedSup['status'] === 302
        && str_contains($attBlockedSup['redirect'], '/portal.php')
        && $delSupPost['status'] === 302
        && !str_contains($admAfterDel['body'], 'Assembly Gate Landmark for BUS-102'));
    recordTest($results, 'TC-20', 'Support RBAC & Ticket Cleanup', $pass,
        $pass ? "Attendee blocked from /admin/support.php (HTTP 302) and test Ticket #{$newTicketId} cleaned up." : 'Support RBAC or cleanup failed');

    // -------------------------------------------------------------------------
    // TC-12: Logout & Session Invalidation -> Protected routes redirect to login
    // -------------------------------------------------------------------------
    $logoutRes = httpRequest("{$baseUrl}/logout.php", $adminCookie);
    $afterLogout = httpRequest("{$baseUrl}/admin/dashboard.php", $adminCookie);
    $pass = ($logoutRes['status'] === 302 && str_contains($logoutRes['redirect'], '/login.php')
        && $afterLogout['status'] === 302 && str_contains($afterLogout['redirect'], '/login.php'));
    recordTest($results, 'TC-12', 'Logout & Session Protection', $pass,
        $pass ? 'Session destroyed; subsequent visit to /admin/dashboard.php redirected to /login.php.' : 'Session remained active');

} finally {
    @unlink($adminCookie);
    @unlink($attendeeCookie);
}

$passed = count(array_filter($results, static fn(array $r): bool => $r['pass']));
$total  = count($results);
echo "\nSUMMARY: {$passed}/{$total} test cases PASSED.\n";
exit($passed === $total ? 0 : 1);
