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
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>
<?php if (isLoggedIn()): ?>
<nav class="topbar no-print">
    <div class="topbar-inner">
        <div class="brand-group">
            <button type="button" class="menu-toggle-btn" id="mobileMenuOpen" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                <?php if ($openSupportCount > 0): ?>
                    <span class="menu-dot" aria-hidden="true"></span>
                <?php endif; ?>
            </button>
            <a class="brand" href="<?= e(homePathForRole($role)) ?>">
                <span>Moyassir &bull; ميسر</span>
            </a>
        </div>

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

<!-- YouTube-Style Mobile Slide-Out Navigation Drawer -->
<div class="drawer-backdrop no-print" id="drawerBackdrop" aria-hidden="true"></div>
<aside class="mobile-drawer no-print" id="mobileDrawer" aria-label="Mobile Navigation" aria-hidden="true">
    <div class="drawer-header">
        <button type="button" class="menu-toggle-btn" id="mobileMenuClose" aria-label="Close navigation menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <a class="brand" href="<?= e(homePathForRole($role)) ?>">
            <span>Moyassir &bull; ميسر</span>
        </a>
    </div>

    <div class="drawer-section">
        <?php if ($role === 'admin'): ?>
            <a href="/admin/dashboard.php" class="drawer-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5.5v-6.5h-5V22H4a1 1 0 0 1-1-1V10.5z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/admin/attendees.php" class="drawer-item <?= $activeNav === 'attendees' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Attendees</span>
            </a>
            <a href="/admin/allocate.php" class="drawer-item <?= $activeNav === 'allocate' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>Allocate Bus / Tent</span>
            </a>
            <a href="/admin/drivers.php" class="drawer-item <?= $activeNav === 'drivers' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M12 3v6.5M4.2 16.5l5.6-3.2M19.8 16.5l-5.6-3.2"/></svg>
                <span>Bus Drivers</span>
            </a>
            <a href="/admin/logistics.php" class="drawer-item <?= $activeNav === 'logistics' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M3 11h18M7 21v-4M17 21v-4"/><circle cx="7.5" cy="14.5" r="1"/><circle cx="16.5" cy="14.5" r="1"/></svg>
                <span>Buses &amp; Itineraries</span>
            </a>
            <a href="/admin/reports.php" class="drawer-item <?= $activeNav === 'reports' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                <span>Manifests &amp; Reports</span>
            </a>
            <a href="/admin/support.php" class="drawer-item <?= $activeNav === 'admin_support' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Support Inbox</span>
                <?php if ($openSupportCount > 0): ?>
                    <span class="nav-badge" style="margin-left:auto;"><?= $openSupportCount ?></span>
                <?php endif; ?>
            </a>
        <?php elseif ($role === 'driver'): ?>
            <a href="/driver_portal.php" class="drawer-item <?= $activeNav === 'driver_portal' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M3 11h18M7 21v-4M17 21v-4"/></svg>
                <span>My Bus &amp; Schedule</span>
            </a>
        <?php else: ?>
            <a href="/portal.php" class="drawer-item <?= $activeNav === 'portal' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5.5v-6.5h-5V22H4a1 1 0 0 1-1-1V10.5z"/></svg>
                <span>My Pilgrim Portal</span>
            </a>
            <a href="/support.php#support-form" class="drawer-item <?= $activeNav === 'support' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Customer Support</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="drawer-divider"></div>

    <div class="drawer-section">
        <div class="drawer-account">
            <span class="pill pill-db"><?= e(strtoupper((string) $role)) ?></span>
            <span class="drawer-username"><?= e($username) ?></span>
        </div>
        <a href="/logout.php" class="drawer-item drawer-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
<?php endif; ?>

<main class="container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> no-print" role="alert">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
