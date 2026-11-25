<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db        = Database::getInstance();
$attModel  = new Attendee($db);
$busModel  = new Bus($db);
$tentModel = new Tent($db);

$error       = '';
$selectedId  = (int) ($_GET['attendee_id'] ?? $_POST['attendee_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $attId  = (int) ($_POST['attendee_id'] ?? 0);
        $busId  = isset($_POST['bus_id']) && $_POST['bus_id'] !== '' ? (int) $_POST['bus_id'] : null;
        $tentId = isset($_POST['tent_id']) && $_POST['tent_id'] !== '' ? (int) $_POST['tent_id'] : null;

        $res = $attModel->assignLogistics($attId, $busId, $tentId);
        if ($res['ok']) {
            setFlash('success', 'Bus and tent allocation updated successfully! Attendee portal reflects changes immediately.');
            header('Location: /admin/allocate.php?attendee_id=' . $attId);
            exit;
        }
        $error = $res['error'];
        $selectedId = $attId;
    }
}

$attendees = $attModel->getAll();
$buses     = $busModel->getAllWithOccupancy();
$tents     = $tentModel->getAllWithOccupancy();
$selected  = $selectedId > 0 ? $attModel->findById($selectedId) : ($attendees[0] ?? null);

$pageTitle = 'Bus & Tent Allocation';
$activeNav = 'allocate';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Bus &amp; Tent Allocation (Capacity Protected)</h1>
        <p class="page-subtitle">Assign pilgrims to transport and accommodation. Server-side validation blocks overbooking automatically.</p>
    </div>
    <a href="/admin/attendees.php" class="btn btn-outline">View Directory</a>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Assign / Reassign Attendee</h2>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/allocate.php">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

            <div class="form-group">
                <label for="attendee_id">Select Attendee *</label>
                <select id="attendee_id" name="attendee_id" onchange="if(this.value) window.location='/admin/allocate.php?attendee_id='+this.value;">
                    <?php foreach ($attendees as $a): ?>
                        <option value="<?= (int) $a['attendee_id'] ?>" <?= ($selected && (int) $selected['attendee_id'] === (int) $a['attendee_id']) ? 'selected' : '' ?>>
                            <?= e($a['full_name']) ?> (<?= e($a['national_id']) ?>) &mdash;
                            Bus: <?= e($a['bus_number'] ?? 'None') ?> | Tent: <?= e($a['tent_number'] ?? 'None') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bus_id">Assigned Bus</label>
                <select id="bus_id" name="bus_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($buses as $b):
                        $isFull = (int) $b['assigned_count'] >= (int) $b['max_capacity'];
                        $isOos  = ($b['status'] ?? 'active') === 'out_of_service';
                        $isCurr = $selected && (int) ($selected['bus_id'] ?? 0) === (int) $b['bus_id'];
                    ?>
                        <option value="<?= (int) $b['bus_id'] ?>" <?= $isCurr ? 'selected' : '' ?>>
                            <?= e($b['bus_number']) ?> &mdash; <?= e($b['driver_name']) ?>
                            (<?= (int) $b['assigned_count'] ?>/<?= (int) $b['max_capacity'] ?><?= $isOos ? ' — OUT OF SERVICE' : ($isFull ? ' — FULL' : '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tent_id">Assigned Tent / Accommodation</label>
                <select id="tent_id" name="tent_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($tents as $t):
                        $isFull = (int) $t['assigned_count'] >= (int) $t['max_capacity'];
                        $isCurr = $selected && (int) ($selected['tent_id'] ?? 0) === (int) $t['tent_id'];
                    ?>
                        <option value="<?= (int) $t['tent_id'] ?>" <?= $isCurr ? 'selected' : '' ?>>
                            <?= e($t['tent_number']) ?> &mdash; <?= e($t['location_zone']) ?>
                            (<?= (int) $t['assigned_count'] ?>/<?= (int) $t['max_capacity'] ?><?= $isFull ? ' — FULL' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn">Validate Capacity &amp; Save Allocation</button>
        </form>
    </div>

    <div class="card">
        <h2>Live Capacity Status</h2>
        <h3>Buses</h3>
        <?php foreach ($buses as $b):
            $assigned = (int) $b['assigned_count'];
            $cap      = (int) $b['max_capacity'];
            $full     = $assigned >= $cap;
            $oos      = ($b['status'] ?? 'active') === 'out_of_service';
        ?>
            <div class="detail-row">
                <span class="detail-key"><strong><?= e($b['bus_number']) ?></strong> (<?= e($b['driver_name']) ?>)</span>
                <span class="detail-val">
                    <?= $assigned ?>/<?= $cap ?>
                    <?php if ($oos): ?>
                        <span class="badge badge-full">OUT OF SERVICE</span>
                    <?php else: ?>
                        <span class="badge <?= $full ? 'badge-full' : 'badge-ok' ?>"><?= $full ? 'AT MAX CAPACITY' : 'AVAILABLE' ?></span>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>

        <h3 style="margin-top:20px;">Tents &amp; Housing</h3>
        <?php foreach ($tents as $t):
            $assigned = (int) $t['assigned_count'];
            $cap      = (int) $t['max_capacity'];
            $full     = $assigned >= $cap;
        ?>
            <div class="detail-row">
                <span class="detail-key"><strong><?= e($t['tent_number']) ?></strong> (<?= e($t['location_zone']) ?>)</span>
                <span class="detail-val">
                    <?= $assigned ?>/<?= $cap ?>
                    <span class="badge <?= $full ? 'badge-full' : 'badge-ok' ?>"><?= $full ? 'AT MAX CAPACITY' : 'AVAILABLE' ?></span>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
