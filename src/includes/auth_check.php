<?php
/**
 * Authentication, Role-Based Access Control (Admin / Attendee / Driver), CSRF & Flash Helpers
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/User.php';
require_once dirname(__DIR__) . '/classes/Driver.php';
require_once dirname(__DIR__) . '/classes/Bus.php';
require_once dirname(__DIR__) . '/classes/Tent.php';
require_once dirname(__DIR__) . '/classes/Attendee.php';
require_once dirname(__DIR__) . '/classes/Itinerary.php';
require_once dirname(__DIR__) . '/classes/SupportTicket.php';
require_once dirname(__DIR__) . '/classes/ReportGenerator.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function homePathForRole(?string $role): string
{
    return match ($role) {
        'admin'  => '/admin/dashboard.php',
        'driver' => '/driver_portal.php',
        default  => '/portal.php',
    };
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please sign in to access that page.');
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (currentRole() !== 'admin') {
        setFlash('error', 'Access denied: Administrative privileges are required.');
        header('Location: ' . homePathForRole(currentRole()));
        exit;
    }
}

function requireAttendee(): void
{
    requireLogin();
    if (currentRole() !== 'attendee') {
        header('Location: ' . homePathForRole(currentRole()));
        exit;
    }
}

function requireDriver(): void
{
    requireLogin();
    if (currentRole() !== 'driver') {
        setFlash('error', 'Access denied: Driver account required.');
        header('Location: ' . homePathForRole(currentRole()));
        exit;
    }
}
