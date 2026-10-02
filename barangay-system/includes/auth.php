<?php
/**
 * includes/auth.php
 * -----------------------------------------------------------------
 * Session bootstrap + role-based access control helpers.
 * Include this file at the TOP of every page (before any HTML output).
 * -----------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns true if a user is currently logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Returns the role of the logged-in user, or null.
 */
function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/**
 * Force the user to be logged in. Otherwise redirect to login page.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /barangay-system/login.php');
        exit;
    }
}

/**
 * Force the user to have one of the given roles.
 * Example: requireRole(['staff', 'admin']);
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    if (!in_array(currentRole(), $allowedRoles, true)) {
        http_response_code(403);
        die('You are not authorized to access this page.');
    }
}

/**
 * Writes an entry to the audit_logs table.
 */
function logAction(PDO $pdo, ?int $userId, string $action, string $description = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $description]);
}

/**
 * Simple CSRF token helpers.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid request (CSRF check failed). Please go back and try again.');
    }
}
