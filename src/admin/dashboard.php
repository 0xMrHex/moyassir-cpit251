<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db           = Database::getInstance();
$reportModel  = new ReportGenerator($db);
$busModel     = new Bus($db);
$tentModel    = new Tent($db);
$attModel     = new Attendee($db);
$supportModel = new SupportTicket($db);

$stats         = $reportModel->getSummaryStats();
$buses         = $busModel->getAllWithOccupancy();
$tents         = $tentModel->getAllWithOccupancy();
$recent        = array_slice($attModel->getAll(), 0, 5);
$recentTickets = array_slice($supportModel->getAll(), 0, 4);

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Campaign Operations Dashboard</h1>
        <p class="page-subtitle">Real-time overview of Hajj &amp; Umrah registrations, bus drivers, fleet status, and tent occupancy.</p>
    </div>
    <div class="actions-inline">
        <a href="/admin/add_attendee.php" class="btn">+ Register Attendee</a>
        <a href="/admin/support.php" class="btn btn-sec">
            Support Inbox (<?= (int) $stats['open_tickets'] ?> Open)
        </a>
        <a href="/admin/drivers.php" class="btn btn-outline">Manage Bus Drivers</a>
        <a href="/admin/allocate.php" class="btn btn-outline">Allocate Bus / Tent</a>
        <a href="/admin/reports.php" class="btn btn-outline">Print Manifests</a>
    </div>
</div>

<div class="grid-4">
    <div class="kpi">
        <div class="kpi-label">Registered Attendees</div>
        <div class="kpi-value"><?= (int) $stats['total_attendees'] ?></div>
        <div class="kpi-sub"><?= (int) $stats['hajj_count'] ?> Hajj &bull; <?= (int) $stats['umrah_count'] ?> Umrah</div>
    </div>
    <div class="kpi <?= (int) $stats['out_of_service_buses'] > 0 ? 'danger' : '' ?>">
        <div class="kpi-label">Campaign Buses</div>
        <div class="kpi-value"><?= (int) $stats['total_buses'] ?></div>
        <div class="kpi-sub"><?= (int) $stats['out_of_service_buses'] ?> out of service</div>
    </div>
    <div class="kpi accent">
        <div class="kpi-label">Registered Drivers</div>
        <div class="kpi-value"><?= (int) $stats['total_drivers'] ?></div>
        <div class="kpi-sub"><?= (int) $stats['out_of_service_drivers'] ?> out of service</div>
    </div>
    <div class="kpi <?= (int) $stats['unassigned'] > 0 ? 'danger' : '' ?>">
        <div class="kpi-label">Pending Allocation</div>
        <div class="kpi-value"><?= (int) $stats['unassigned'] ?></div>
        <div class="kpi-sub"><?= (int) $stats['total_tents'] ?> tents active</div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Bus Fleet Occupancy &amp; Status</h2>
        <?php foreach ($buses as $b):
            $assigned = (int) $b['assigned_count'];
            $cap      = max(1, (int) $b['max_capacity']);
            $pct      = min(100, (int) round(($assigned / $cap) * 100));
            $isFull   = $assigned >= $cap;
            $isOos    = ($b['status'] ?? 'active') === 'out_of_service';
        ?>
        <div class="progress-wrap">
            <div class="progress-top">
                <span>
                    <strong><?= e($b['bus_number']) ?></strong> &mdash; <?= e($b['driver_name']) ?>
                    <?php if ($isOos): ?>
                        <span class="badge badge-full">OUT OF SERVICE</span>
                    <?php endif; ?>
                </span>
                <span>
                    <?= $assigned ?> / <?= $cap ?> seats
                    <span class="badge <?= $isFull ? 'badge-full' : 'badge-ok' ?>">
                        <?= $isFull ? 'FULL' : (($cap - $assigned) . ' FREE') ?>
                    </span>
                </span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill <?= ($isFull || $isOos) ? 'full' : ($pct >= 80 ? 'warn' : '') ?>" style="width: <?= $pct ?>%;"></div>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:3px;">Departure: <?= e($b['departure_time']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Tent &amp; Housing Occupancy</h2>
        <?php foreach ($tents as $t):
            $assigned = (int) $t['assigned_count'];
            $cap      = max(1, (int) $t['max_capacity']);
            $pct      = min(100, (int) round(($assigned / $cap) * 100));
            $isFull   = $assigned >= $cap;
        ?>
        <div class="progress-wrap">
            <div class="progress-top">
                <span><strong><?= e($t['tent_number']) ?></strong> &mdash; <?= e($t['location_zone']) ?></span>
                <span>
                    <?= $assigned ?> / <?= $cap ?> spaces
                    <span class="badge <?= $isFull ? 'badge-full' : 'badge-ok' ?>">
                        <?= $isFull ? 'FULL' : (($cap - $assigned) . ' FREE') ?>
                    </span>
                </span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill <?= $isFull ? 'full' : ($pct >= 80 ? 'warn' : '') ?>" style="width: <?= $pct ?>%;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="margin:0;">Pilgrim Customer Support Requests (<?= (int) $stats['open_tickets'] ?> Active)</h2>
        <a href="/admin/support.php" class="btn btn-sm btn-outline">Open Support Inbox &rarr;</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Pilgrim</th>
                    <th>Category &amp; Subject</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTickets)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--muted);">No support requests submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentTickets as $rt):
                        $st = (string) $rt['status'];
                        $stClass = match ($st) {
                            'resolved'    => 'badge-ok',
                            'in_progress' => 'badge-umrah',
                            default       => 'badge-hajj',
                        };
                    ?>
                    <tr>
                        <td><strong>#<?= (int) $rt['ticket_id'] ?></strong></td>
                        <td>
                            <strong><?= e($rt['full_name']) ?></strong><br>
                            <small style="color:var(--muted);"><?= e($rt['bus_number'] ?? 'No Bus') ?> &bull; <?= e($rt['tent_number'] ?? 'No Tent') ?></small>
                        </td>
                        <td>
                            <small style="color:var(--muted);"><?= e($rt['category']) ?></small><br>
                            <strong><?= e($rt['subject']) ?></strong>
                        </td>
                        <td>
                            <span class="badge <?= $rt['priority'] === 'urgent' ? 'badge-full' : 'badge-muted' ?>">
                                <?= strtoupper(e($rt['priority'])) ?>
                            </span>
                        </td>
                        <td><span class="badge <?= $stClass ?>"><?= strtoupper(str_replace('_', ' ', e($st))) ?></span></td>
                        <td><a href="/admin/support.php#ticket-<?= (int) $rt['ticket_id'] ?>" class="btn btn-sm">Review / Reply</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="margin:0;">Recently Registered Attendees</h2>
        <a href="/admin/attendees.php" class="btn btn-sm btn-outline">View All Attendees &rarr;</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>National ID</th>
                    <th>Campaign</th>
                    <th>Bus</th>
                    <th>Tent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $a): ?>
                <tr>
                    <td><strong><?= e($a['full_name']) ?></strong></td>
                    <td><code><?= e($a['national_id']) ?></code></td>
                    <td><span class="badge <?= $a['campaign_type'] === 'Hajj' ? 'badge-hajj' : 'badge-umrah' ?>"><?= e($a['campaign_type']) ?></span></td>
                    <td><?= $a['bus_number'] ? e($a['bus_number']) : '<span class="badge badge-muted">Unassigned</span>' ?></td>
                    <td><?= $a['tent_number'] ? e($a['tent_number']) : '<span class="badge badge-muted">Unassigned</span>' ?></td>
                    <td>
                        <a href="/admin/allocate.php?attendee_id=<?= (int) $a['attendee_id'] ?>" class="btn btn-sm btn-outline">Allocate</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
