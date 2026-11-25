<?php
/** @var string $pageTitle */
/** @var string $activeNav */
$pageTitle = $pageTitle ?? 'Moyassir (ميسر)';
$activeNav = $activeNav ?? '';
$role      = currentRole();
$username  = $_SESSION['username'] ?? '';
$flash     = getFlash();
$openSupportCount = 0;
if ($role === 'admin') {
    $openSupportCount = (new SupportTicket(Database::getInstance()))->countActive();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Moyassir (ميسر)</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php if (isLoggedIn()): ?>
<nav class="topbar no-print">
    <div class="topbar-inner">
        <a class="brand" href="<?= e(homePathForRole($role)) ?>">
            <span>Moyassir &bull; ميسر</span>
        </a>

        <?php if ($role === 'admin'): ?>
        <div class="nav-links">
            <a href="/admin/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="/admin/attendees.php" class="<?= $activeNav === 'attendees' ? 'active' : '' ?>">Attendees</a>
            <a href="/admin/allocate.php" class="<?= $activeNav === 'allocate' ? 'active' : '' ?>">Allocate Bus/Tent</a>
            <a href="/admin/drivers.php" class="<?= $activeNav === 'drivers' ? 'active' : '' ?>">Bus Drivers</a>
            <a href="/admin/logistics.php" class="<?= $activeNav === 'logistics' ? 'active' : '' ?>">Buses &amp; Itineraries</a>
            <a href="/admin/reports.php" class="<?= $activeNav === 'reports' ? 'active' : '' ?>">Manifests &amp; Reports</a>
            <a href="/admin/support.php" class="<?= $activeNav === 'admin_support' ? 'active' : '' ?>">
                Support<?php if ($openSupportCount > 0): ?><span class="nav-badge"><?= $openSupportCount ?></span><?php endif; ?>
            </a>
        </div>
        <?php elseif ($role === 'driver'): ?>
        <div class="nav-links">
            <a href="/driver_portal.php" class="<?= $activeNav === 'driver_portal' ? 'active' : '' ?>">My Bus &amp; Schedule</a>
        </div>
        <?php else: ?>
        <div class="nav-links">
            <a href="/portal.php" class="<?= $activeNav === 'portal' ? 'active' : '' ?>">My Pilgrim Portal</a>
            <a href="/support.php#support-form" class="<?= $activeNav === 'support' ? 'active' : '' ?>">Customer Support</a>
        </div>
        <?php endif; ?>

        <div class="nav-user">
            <span class="pill pill-db"><?= e(strtoupper((string) $role)) ?></span>
            <span class="user-label"><?= e($username) ?></span>
            <a href="/logout.php" class="btn btn-sm btn-outline-light">Sign Out</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> no-print" role="alert">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
