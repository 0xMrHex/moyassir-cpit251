<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/attendees.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Security check failed (CSRF token mismatch).');
    header('Location: /admin/attendees.php');
    exit;
}

$attendeeId = (int) ($_POST['attendee_id'] ?? 0);
$attModel   = new Attendee(Database::getInstance());
$existing   = $attModel->findById($attendeeId);

if (!$existing) {
    setFlash('error', 'Attendee record not found.');
} elseif ($attModel->delete($attendeeId)) {
    setFlash('success', "Deleted attendee '{$existing['full_name']}' and removed their login account.");
} else {
    setFlash('error', 'Unable to delete attendee record.');
}

header('Location: /admin/attendees.php');
exit;
