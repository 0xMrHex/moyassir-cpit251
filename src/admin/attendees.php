<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db        = Database::getInstance();
$attModel  = new Attendee($db);
$busModel  = new Bus($db);
$tentModel = new Tent($db);

$search       = trim((string) ($_GET['q'] ?? ''));
$busFilter    = isset($_GET['bus_id']) && $_GET['bus_id'] !== '' ? (int) $_GET['bus_id'] : null;
$tentFilter   = isset($_GET['tent_id']) && $_GET['tent_id'] !== '' ? (int) $_GET['tent_id'] : null;
$campaignType = trim((string) ($_GET['campaign_type'] ?? ''));

$attendees = $attModel->getAll($search, $busFilter, $tentFilter, $campaignType);
$buses     = $busModel->getAllWithOccupancy();
$tents     = $tentModel->getAllWithOccupancy();

$pageTitle = 'Manage Attendees';
$activeNav = 'attendees';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Attendee Directory (CRUD &amp; Search)</h1>
        <p class="page-subtitle">Search by National ID, name, or phone, and filter by assigned bus, tent, or campaign type.</p>
    </div>
    <a href="/admin/add_attendee.php" class="btn">+ Add New Attendee</a>
</div>

<div class="card">
    <form method="GET" action="/admin/attendees.php" class="filter-bar">
        <div class="form-group" style="flex:2;">
            <label for="q">Search (Name / National ID / Phone)</label>
            <input type="text" id="q" name="q" value="<?= e($search) ?>" placeholder="e.g., 1098765431 or Abdullah">
        </div>
        <div class="form-group">
            <label for="bus_id">Filter by Bus</label>
            <select id="bus_id" name="bus_id">
                <option value="">All Buses</option>
                <?php foreach ($buses as $b): ?>
                    <option value="<?= (int) $b['bus_id'] ?>" <?= $busFilter === (int) $b['bus_id'] ? 'selected' : '' ?>>
                        <?= e($b['bus_number']) ?> (<?= (int) $b['assigned_count'] ?>/<?= (int) $b['max_capacity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="tent_id">Filter by Tent</label>
            <select id="tent_id" name="tent_id">
                <option value="">All Tents</option>
                <?php foreach ($tents as $t): ?>
                    <option value="<?= (int) $t['tent_id'] ?>" <?= $tentFilter === (int) $t['tent_id'] ? 'selected' : '' ?>>
                        <?= e($t['tent_number']) ?> (<?= (int) $t['assigned_count'] ?>/<?= (int) $t['max_capacity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="campaign_type">Campaign</label>
            <select id="campaign_type" name="campaign_type">
                <option value="">All Types</option>
                <option value="Hajj" <?= $campaignType === 'Hajj' ? 'selected' : '' ?>>Hajj</option>
                <option value="Umrah" <?= $campaignType === 'Umrah' ? 'selected' : '' ?>>Umrah</option>
            </select>
        </div>
        <div class="actions-inline">
            <button type="submit" class="btn">Apply Filter</button>
            <a href="/admin/attendees.php" class="btn btn-outline">Reset</a>
        </div>
    </form>

    <div style="font-size:13px;color:var(--muted);margin-bottom:10px;">
        Showing <strong><?= count($attendees) ?></strong> matching attendee(s).
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>National ID</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Assigned Bus</th>
                    <th>Assigned Tent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendees)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px;">No attendees match the selected criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($attendees as $a): ?>
                    <tr>
                        <td>#<?= (int) $a['attendee_id'] ?></td>
                        <td><strong><?= e($a['full_name']) ?></strong></td>
                        <td><code><?= e($a['national_id']) ?></code></td>
                        <td><?= e($a['phone']) ?></td>
                        <td><span class="badge <?= $a['campaign_type'] === 'Hajj' ? 'badge-hajj' : 'badge-umrah' ?>"><?= e($a['campaign_type']) ?></span></td>
                        <td><?= $a['bus_number'] ? e($a['bus_number']) : '<span class="badge badge-muted">Unassigned</span>' ?></td>
                        <td><?= $a['tent_number'] ? e($a['tent_number']) : '<span class="badge badge-muted">Unassigned</span>' ?></td>
                        <td>
                            <div class="actions-inline">
                                <a href="/admin/edit_attendee.php?id=<?= (int) $a['attendee_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="/admin/allocate.php?attendee_id=<?= (int) $a['attendee_id'] ?>" class="btn btn-sm btn-sec">Allocate</a>
                                <form method="POST" action="/admin/delete_attendee.php" onsubmit="return confirm('Delete <?= e($a['full_name']) ?> and their portal account?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <input type="hidden" name="attendee_id" value="<?= (int) $a['attendee_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
