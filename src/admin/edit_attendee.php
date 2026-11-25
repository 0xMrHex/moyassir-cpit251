<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db       = Database::getInstance();
$attModel = new Attendee($db);
$id       = (int) ($_GET['id'] ?? $_POST['attendee_id'] ?? 0);
$attendee = $attModel->findById($id);

if (!$attendee) {
    setFlash('error', 'Attendee not found.');
    header('Location: /admin/attendees.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $payload = [
            'full_name'     => trim((string) ($_POST['full_name'] ?? '')),
            'national_id'   => trim((string) ($_POST['national_id'] ?? '')),
            'phone'         => trim((string) ($_POST['phone'] ?? '')),
            'campaign_type' => trim((string) ($_POST['campaign_type'] ?? 'Hajj')),
            'new_password'  => (string) ($_POST['new_password'] ?? ''),
        ];
        $res = $attModel->update($id, $payload);
        if ($res['ok']) {
            setFlash('success', "Updated attendee record for '{$payload['full_name']}'.");
            header('Location: /admin/attendees.php');
            exit;
        }
        $error = $res['error'];
        $attendee = array_merge($attendee, $payload);
    }
}

$pageTitle = 'Edit Attendee';
$activeNav = 'attendees';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Edit Attendee #<?= (int) $attendee['attendee_id'] ?></h1>
        <p class="page-subtitle">Update personal details or reset portal password. Use Allocate to change bus/tent with capacity validation.</p>
    </div>
    <a href="/admin/attendees.php" class="btn btn-outline">&larr; Back to Directory</a>
</div>

<div class="card" style="max-width: 720px;">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/edit_attendee.php?id=<?= (int) $attendee['attendee_id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
        <input type="hidden" name="attendee_id" value="<?= (int) $attendee['attendee_id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($attendee['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="national_id">National ID * (Synced with Login Username)</label>
                <input type="text" id="national_id" name="national_id" value="<?= e($attendee['national_id']) ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="text" id="phone" name="phone" value="<?= e($attendee['phone']) ?>" required>
            </div>
            <div class="form-group">
                <label for="campaign_type">Campaign Type *</label>
                <select id="campaign_type" name="campaign_type">
                    <option value="Hajj" <?= $attendee['campaign_type'] === 'Hajj' ? 'selected' : '' ?>>Hajj</option>
                    <option value="Umrah" <?= $attendee['campaign_type'] === 'Umrah' ? 'selected' : '' ?>>Umrah</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="new_password">Reset Portal Password (Leave blank to keep existing password)</label>
            <input type="password" id="new_password" name="new_password" placeholder="Optional new password">
        </div>

        <div class="actions-inline">
            <button type="submit" class="btn">Save Changes</button>
            <a href="/admin/allocate.php?attendee_id=<?= (int) $attendee['attendee_id'] ?>" class="btn btn-sec">Change Bus / Tent</a>
            <a href="/admin/attendees.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
