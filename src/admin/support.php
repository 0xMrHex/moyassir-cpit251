<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db           = Database::getInstance();
$supportModel = new SupportTicket($db);
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf       = $_POST['csrf_token'] ?? '';
    $action     = trim((string) ($_POST['action'] ?? 'respond'));
    $ticketId   = (int) ($_POST['ticket_id'] ?? 0);

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
$priorityFilter = trim((string) ($_GET['priority'] ?? ''));

$tickets    = $supportModel->getAll($statusFilter, $priorityFilter);
$allTickets = ($statusFilter === '' && $priorityFilter === '') ? $tickets : $supportModel->getAll();

$openCount     = count(array_filter($allTickets, static fn(array $t): bool => in_array($t['status'], ['open', 'in_progress'], true)));
$urgentCount   = count(array_filter($allTickets, static fn(array $t): bool => in_array($t['status'], ['open', 'in_progress'], true) && $t['priority'] === 'urgent'));
$resolvedCount = count(array_filter($allTickets, static fn(array $t): bool => $t['status'] === 'resolved'));

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
        <div class="kpi-label">Active Support Tickets</div>
        <div class="kpi-value"><?= $openCount ?></div>
        <div class="kpi-sub">Open or In Progress</div>
    </div>
    <div class="kpi <?= $urgentCount > 0 ? 'danger' : '' ?>">
        <div class="kpi-label">Urgent Field Requests</div>
        <div class="kpi-value"><?= $urgentCount ?></div>
        <div class="kpi-sub">Immediate attention needed</div>
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
            <label for="priority">Filter by Priority</label>
            <select id="priority" name="priority">
                <option value="">All Priorities</option>
                <option value="urgent" <?= $priorityFilter === 'urgent' ? 'selected' : '' ?>>Urgent Only</option>
                <option value="normal" <?= $priorityFilter === 'normal' ? 'selected' : '' ?>>Normal Only</option>
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
            $status = (string) $t['status'];
            $statusBadge = match ($status) {
                'resolved'    => 'badge-ok',
                'in_progress' => 'badge-umrah',
                default       => 'badge-hajj',
            };
            $statusLabel = strtoupper(str_replace('_', ' ', $status));
            $isUrgent = ($t['priority'] === 'urgent');
        ?>
            <div class="ticket-card <?= $status === 'resolved' ? 'resolved' : ($isUrgent ? 'urgent' : '') ?>" id="ticket-<?= (int) $t['ticket_id'] ?>">
                <div class="ticket-meta">
                    <div>
                        <strong>Ticket #<?= (int) $t['ticket_id'] ?></strong> &bull;
                        <span><?= e($t['category']) ?></span>
                        <?php if ($isUrgent): ?>
                            <span class="badge badge-full" style="margin-left:6px;">URGENT</span>
                        <?php endif; ?>
                        <span class="badge <?= $statusBadge ?>" style="margin-left:6px;"><?= e($statusLabel) ?></span>
                    </div>
                    <div>Submitted: <?= e($t['created_at']) ?></div>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:13px;">
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

                <h3 style="margin:0 0 6px;"><?= e($t['subject']) ?></h3>
                <p style="margin:0 0 12px;font-size:14px;white-space:pre-line;"><?= e($t['message']) ?></p>

                <form method="POST" action="/admin/support.php" style="border-top:1px solid #e2e8f0;padding-top:12px;">
                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                    <input type="hidden" name="ticket_id" value="<?= (int) $t['ticket_id'] ?>">

                    <div class="form-grid" style="align-items:end;">
                        <div class="form-group" style="margin-bottom:0;grid-column:1 / -1;">
                            <label for="reply-<?= (int) $t['ticket_id'] ?>">Admin Reply to Pilgrim</label>
                            <textarea id="reply-<?= (int) $t['ticket_id'] ?>" name="admin_reply" rows="2" maxlength="1500"
                                      placeholder="Write official response or instructions visible on the pilgrim's support page..."><?= e((string) ($t['admin_reply'] ?? '')) ?></textarea>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-top:10px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <label for="status-<?= (int) $t['ticket_id'] ?>" style="font-size:13px;font-weight:600;">Status:</label>
                            <select id="status-<?= (int) $t['ticket_id'] ?>" name="status" style="width:auto;">
                                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm">Save Reply &amp; Update Status</button>
                    </div>
                </form>
                <form method="POST" action="/admin/support.php" style="margin-top:8px;text-align:right;" onsubmit="return confirm('Delete Support Ticket #<?= (int) $t['ticket_id'] ?>?');">
                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete_ticket">
                    <input type="hidden" name="ticket_id" value="<?= (int) $t['ticket_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);border-color:#fca5a5;">Delete Ticket</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
