<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

header('Location: ' . homePathForRole(currentRole()));
exit;
