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
