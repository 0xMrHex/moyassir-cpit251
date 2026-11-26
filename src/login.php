<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';

if (isLoggedIn()) {
    header('Location: ' . homePathForRole(currentRole()));
    exit;
}

$error = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = trim((string) ($_POST['username'] ?? ''));
    $password      = (string) ($_POST['password'] ?? '');
    $csrf          = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security token expired. Please try signing in again.';
    } elseif ($usernameInput === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $userModel = new User(Database::getInstance());
        $user = $userModel->authenticate($usernameInput, $password);

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int) $user['user_id'];
            $_SESSION['username'] = (string) $user['username'];
            $_SESSION['role']     = (string) $user['role'];

            header('Location: ' . homePathForRole($user['role']));
            exit;
        }
        $error = 'Invalid credentials. Please check your username/National ID and password.';
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-shell">
    <div class="login-logo">
        <svg width="64" height="64" viewBox="0 0 72 72" fill="none" aria-hidden="true" style="display:block;margin:0 auto 10px;">
            <path d="M13 61V35C13 19.5 36 8 36 8C36 8 59 19.5 59 35V61" stroke="#0F766E" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M20 61V36C20 24 36 15.5 36 15.5C36 15.5 52 24 52 36V61" stroke="#14B8A6" stroke-width="2" stroke-opacity="0.55" stroke-linecap="round"/>
            <line x1="9" y1="61" x2="63" y2="61" stroke="#0F766E" stroke-width="3.5" stroke-linecap="round"/>
            <rect x="24" y="29" width="24" height="26" rx="2.5" fill="#0F172A"/>
            <rect x="24" y="34.5" width="24" height="4.2" fill="#FBBF24"/>
            <rect x="38.5" y="43" width="4.8" height="12" rx="1" fill="#F59E0B"/>
        </svg>
        <h1>Moyassir &bull; ميسر</h1>
        <p>Hajj &amp; Umrah Campaign Registration &amp; Logistics</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

        <div class="form-group">
            <label for="username">Username (Admin / Driver) or National ID (Attendee)</label>
            <input type="text" id="username" name="username" value="<?= e($usernameInput) ?>"
                   placeholder="e.g., admin, driver1, or 1098765431" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn" style="width: 100%; margin-top: 6px;">Sign In</button>
    </form>

    <div class="demo-box">
        <strong>CPIT251 Demo Credentials:</strong><br>
        Campaign Manager: <code>admin</code> / <code>Admin@2026</code><br>
        Bus Driver: <code>driver1</code> / <code>Driver@2026</code><br>
        Sample Attendee: <code>1098765431</code> / <code>Pilgrim@2026</code>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
