<?php
/**
 * config/database.php
 * -----------------------------------------------------------------
 * Creates one PDO connection ($pdo) shared by the whole application.
 * Edit the constants below if your MySQL username/password differ
 * from the default XAMPP settings.
 * -----------------------------------------------------------------
 */

// ---- Database credentials (change if needed) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'barangay_reservation');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP MySQL root password is empty

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        // Throw exceptions on error instead of silently failing
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Return rows as associative arrays
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Use real prepared statements (safer against SQL injection)
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Never show raw database errors to the user.
    error_log('Database connection failed: ' . $e->getMessage());
    die('A system error occurred. Please try again later.');
}
