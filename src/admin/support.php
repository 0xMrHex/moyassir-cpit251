<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db           = Database::getInstance();
$supportModel = new SupportTicket($db);
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf     = $_POST['csrf_token'] ?? '';
    $action   = trim((string) ($_POST['action'] ?? 'respond'));
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security token expired. Please try saving your reply again.';
    } elseif ($action === 'delete_ticket') {
        if ($supportModel->delete($ticketId)) {
            setFlash('success', "Deleted Support Ticket #{$ticketId}.");
        } else {
            setFlash('error', "Support Ticket #{$ticketId} could not be found.");
        }
        header('Location: /admin/support.php');
        exit;
    } else {
        $status     = trim((string) ($_POST['status'] ?? 'open'));
        $adminReply = trim((string) ($_POST['admin_reply'] ?? ''));
        $res = $supportModel->respond($ticketId, $status, $adminReply);
        if ($res['ok']) {
            setFlash('success', "Updated Ticket #{$ticketId} (" . strtoupper(str_replace('_', ' ', $status)) . ').');
            header('Location: /admin/support.php');
            exit;
        }
        $error = (string) $res['error'];
    }
}

$statusFilter   = trim((string) ($_GET['status'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));

$tickets    = $supportModel->getAll($statusFilter, $categoryFilter);
$allTickets = ($statusFilter === '' && $categoryFilter === '') ? $tickets : $supportModel->getAll();

$openCount       = count(array_filter($allTickets, static fn(array $t): bool => $t['status'] === 'open'));
$inProgressCount = count(array_filter($allTickets, static fn(array $t): bool => $t['status'] === 'in_progress'));
$resolvedCount   = count(array_filter($allTickets, static fn(array $t): bool => $t['status'] === 'resolved'));

$quickReplies = [
    [
        'label'  => 'Gate & Bus Confirmed',
        'status' => 'resolved',
        'text'   => 'Wa Alaikum Assalam. Your assigned bus and departure gate are confirmed. Please arrive 30 minutes prior to departure for boarding check-in.',
    ],
    [
        'label'  => 'Tent Request Approved',
        'status' => 'resolved',
        'text'   => 'Your tent accommodation request has been approved and coordinated with our camp supervisor on site.',
    ],
    [
        'label'  => 'Coordinator Calling Shortly',
        'status' => 'in_progress',
        'text'   => 'A campaign field coordinator has received your message and will contact your mobile number shortly to assist you.',
    ],
    [
        'label'  => 'Issue Resolved',
        'status' => 'resolved',
        'text'   => 'This support request has been resolved by Campaign Administration. Wishing you a safe and accepted pilgrimage.',
    ],
];

$pageTitle = 'Customer Support Inbox';
$activeNav = 'admin_support';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Pilgrim Customer Support Inbox</h1>
        <p class="page-subtitle">Review incoming pilgrim support messages, inspect assigned logistics, and send official responses.</p>
    </div>
    <div class="actions-inline">
        <a href="/admin/dashboard.php" class="btn btn-outline">&larr; Dashboard</a>
        <a href="/admin/allocate.php" class="btn btn-sec">Allocate Bus / Tent</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="grid-4" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));">
    <div class="kpi <?= $openCount > 0 ? 'accent' : '' ?>">
        <div class="kpi-label">Open Requests</div>
        <div class="kpi-value"><?= $openCount ?></div>
        <div class="kpi-sub">Awaiting initial reply</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">In Progress</div>
        <div class="kpi-value"><?= $inProgressCount ?></div>
        <div class="kpi-sub">Being handled by staff</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Resolved Tickets</div>
        <div class="kpi-value"><?= $resolvedCount ?></div>
        <div class="kpi-sub"><?= count($allTickets) ?> total requests logged</div>
    </div>
</div>

<div class="card">
    <form method="GET" action="/admin/support.php" class="filter-bar" style="margin-bottom:0;">
        <div class="form-group">
            <label for="status">Filter by Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
        </div>
        <div class="form-group">
            <label for="category">Filter by Topic</label>
            <select id="category" name="category">
                <option value="">All Topics</option>
                <?php foreach (SupportTicket::ALLOWED_CATEGORIES as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions-inline">
            <button type="submit" class="btn">Apply Filter</button>
            <a href="/admin/support.php" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Support Messages (<?= count($tickets) ?>)</h2>

    <?php if (empty($tickets)): ?>
        <p style="color:var(--muted);margin:0;">No customer support tickets match the selected criteria.</p>
    <?php else: ?>
        <?php foreach ($tickets as $t):
            $tid    = (int) $t['ticket_id'];
            $status = (string) $t['status'];
            $statusBadge = match ($status) {
                'resolved'    => 'badge-ok',
                'in_progress' => 'badge-umrah',
                default       => 'badge-hajj',
            };
            $statusLabel = strtoupper(str_replace('_', ' ', $status));
            $hasReply    = !empty($t['admin_reply']);
        ?>
            <div class="ticket-card <?= $status === 'resolved' ? 'resolved' : '' ?>" id="ticket-<?= $tid ?>">
                <div class="ticket-meta">
                    <div>
                        <strong>Ticket #<?= $tid ?></strong> &bull;
                        <span><?= e($t['category']) ?></span>
                        <span class="badge <?= $statusBadge ?>" style="margin-left:6px;"><?= e($statusLabel) ?></span>
                    </div>
                    <div><?= e($t['created_at']) ?></div>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:13px;">
                    <div>
                        <strong><?= e($t['full_name']) ?></strong>
                        (<code><?= e($t['national_id']) ?></code> &bull;
                        <a href="tel:<?= e($t['phone']) ?>"><?= e($t['phone']) ?></a>) &bull;
                        <span class="badge <?= $t['campaign_type'] === 'Hajj' ? 'badge-hajj' : 'badge-umrah' ?>"><?= e($t['campaign_type']) ?></span>
                        &bull; Bus: <strong><?= e($t['bus_number'] ?? 'Unassigned') ?></strong>
                        &bull; Tent: <strong><?= e($t['tent_number'] ?? 'Unassigned') ?></strong>
                    </div>
                    <a href="/admin/allocate.php?attendee_id=<?= (int) $t['attendee_id'] ?>" class="btn btn-sm btn-outline">
                        Reallocate Bus / Tent
                    </a>
                </div>

                <div class="chat-thread">
                    <div class="chat-bubble pilgrim">
                        <div class="chat-sender">
                            <span>Pilgrim Message &mdash; <?= e($t['full_name']) ?></span>
                            <span><?= e($t['category']) ?></span>
                        </div>
                        <div style="font-weight:700;font-size:15px;color:var(--text);margin-bottom:4px;"><?= e($t['subject']) ?></div>
                        <div style="white-space:pre-line;"><?= e($t['message']) ?></div>
                    </div>

                    <?php if ($hasReply): ?>
                        <div class="chat-bubble admin">
                            <div class="chat-sender">
                                <span>Campaign Administration Official Reply</span>
                                <span><?= e($t['updated_at']) ?></span>
                            </div>
                            <div style="white-space:pre-line;"><?= e((string) $t['admin_reply']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($status === 'resolved' && $hasReply): ?>
                <details class="reply-details">
                    <summary>Edit Official Reply or Change Ticket Status</summary>
                <?php endif; ?>

                    <form method="POST" action="/admin/support.php" class="composer-box">
                        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                        <input type="hidden" name="action" value="respond">
                        <input type="hidden" name="ticket_id" value="<?= $tid ?>">

                        <label for="reply-<?= $tid ?>" style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:#0f172a;">
                            <?= $hasReply ? 'Update Official Reply' : 'Write Reply to Pilgrim' ?>
                            <span style="font-weight:400;color:var(--muted);font-size:12px;">(tap a quick preset or type below)</span>
                        </label>

                        <div class="quick-chips">
                            <?php foreach ($quickReplies as $qr): ?>
                                <button type="button" class="chip-btn"
                                        data-target="reply-<?= $tid ?>"
                                        data-status-target="status-<?= $tid ?>"
                                        data-status="<?= e($qr['status']) ?>"
                                        data-text="<?= e($qr['text']) ?>">
                                    + <?= e($qr['label']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <textarea id="reply-<?= $tid ?>" name="admin_reply" class="composer-textarea" rows="4" maxlength="1500"
                                  placeholder="Type a clear response or instructions for <?= e($t['full_name']) ?>..."><?= e((string) ($t['admin_reply'] ?? '')) ?></textarea>

                        <div class="composer-footer">
                            <div class="status-row" style="display:flex;align-items:center;gap:8px;">
                                <label for="status-<?= $tid ?>" style="font-size:13px;font-weight:700;white-space:nowrap;">Ticket Status:</label>
                                <select id="status-<?= $tid ?>" name="status" style="width:auto;min-width:145px;">
                                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-send-reply">Send Reply &amp; Update Status</button>
                        </div>
                    </form>

                <?php if ($status === 'resolved' && $hasReply): ?>
                </details>
                <?php endif; ?>

                <form method="POST" action="/admin/support.php" class="ticket-delete-row" style="margin-top:8px;text-align:right;" onsubmit="return confirm('Delete Support Ticket #<?= $tid ?>?');">
                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete_ticket">
                    <input type="hidden" name="ticket_id" value="<?= $tid ?>">
                    <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);border-color:#fca5a5;">Delete Ticket</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
