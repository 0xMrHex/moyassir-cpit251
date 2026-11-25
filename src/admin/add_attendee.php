<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db        = Database::getInstance();
$attModel  = new Attendee($db);
$busModel  = new Bus($db);
$tentModel = new Tent($db);

$buses = $busModel->getAllWithOccupancy();
$tents = $tentModel->getAllWithOccupancy();
$error = '';
$form  = [
    'full_name'     => '',
    'national_id'   => '',
    'phone'         => '',
    'campaign_type' => 'Hajj',
    'bus_id'        => '',
    'tent_id'       => '',
    'password'      => 'Pilgrim@2026',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'full_name'     => trim((string) ($_POST['full_name'] ?? '')),
        'national_id'   => trim((string) ($_POST['national_id'] ?? '')),
        'phone'         => trim((string) ($_POST['phone'] ?? '')),
        'campaign_type' => trim((string) ($_POST['campaign_type'] ?? 'Hajj')),
        'bus_id'        => trim((string) ($_POST['bus_id'] ?? '')),
        'tent_id'       => trim((string) ($_POST['tent_id'] ?? '')),
        'password'      => (string) ($_POST['password'] ?? 'Pilgrim@2026'),
    ];

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please retry.';
    } else {
        $res = $attModel->createWithAccount($form, $form['password'] ?: 'Pilgrim@2026');
        if ($res['ok']) {
            setFlash('success', "Attendee '{$form['full_name']}' registered! Portal login username: {$form['national_id']}.");
            header('Location: /admin/attendees.php');
            exit;
        }
        $error = $res['error'];
    }
}

$pageTitle = 'Register Attendee';
$activeNav = 'attendees';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Register New Attendee</h1>
        <p class="page-subtitle">Automatically creates a linked Attendee Portal account using the National ID as username.</p>
    </div>
    <a href="/admin/attendees.php" class="btn btn-outline">&larr; Back to Directory</a>
</div>

<div class="card" style="max-width: 780px;">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/add_attendee.php">
        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="national_id">National ID / Iqama * (Portal Username)</label>
                <input type="text" id="national_id" name="national_id" value="<?= e($form['national_id']) ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Mobile Number *</label>
                <input type="text" id="phone" name="phone" value="<?= e($form['phone']) ?>" placeholder="05XXXXXXXX" required>
            </div>
            <div class="form-group">
                <label for="campaign_type">Campaign Type *</label>
                <select id="campaign_type" name="campaign_type">
                    <option value="Hajj" <?= $form['campaign_type'] === 'Hajj' ? 'selected' : '' ?>>Hajj</option>
                    <option value="Umrah" <?= $form['campaign_type'] === 'Umrah' ? 'selected' : '' ?>>Umrah</option>
                </select>
            </div>
            <div class="form-group">
                <label for="bus_id">Initial Bus Assignment (Optional)</label>
                <select id="bus_id" name="bus_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($buses as $b):
                        $full = (int) $b['assigned_count'] >= (int) $b['max_capacity'];
                        $oos  = ($b['status'] ?? 'active') === 'out_of_service';
                    ?>
                        <option value="<?= (int) $b['bus_id'] ?>" <?= $form['bus_id'] === (string) $b['bus_id'] ? 'selected' : '' ?>>
                            <?= e($b['bus_number']) ?> (<?= (int) $b['assigned_count'] ?>/<?= (int) $b['max_capacity'] ?><?= $oos ? ' — OUT OF SERVICE' : ($full ? ' — FULL' : '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tent_id">Initial Tent Assignment (Optional)</label>
                <select id="tent_id" name="tent_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($tents as $t):
                        $full = (int) $t['assigned_count'] >= (int) $t['max_capacity'];
                    ?>
                        <option value="<?= (int) $t['tent_id'] ?>" <?= $form['tent_id'] === (string) $t['tent_id'] ? 'selected' : '' ?>>
                            <?= e($t['tent_number']) ?> &mdash; <?= e($t['location_zone']) ?> (<?= (int) $t['assigned_count'] ?>/<?= (int) $t['max_capacity'] ?><?= $full ? ' — FULL' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Initial Portal Password *</label>
            <input type="text" id="password" name="password" value="<?= e($form['password']) ?>" required>
        </div>

        <div class="actions-inline">
            <button type="submit" class="btn">Save Attendee &amp; Provision Account</button>
            <a href="/admin/attendees.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
