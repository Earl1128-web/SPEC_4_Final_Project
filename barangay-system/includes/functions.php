<?php
/**
 * includes/functions.php
 * -----------------------------------------------------------------
 * Reusable helper functions used across the application.
 * -----------------------------------------------------------------
 */

/** Shortcut for escaping output to prevent XSS. */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Formats a MySQL date (Y-m-d) into "August 31, 2026". */
function formatDate(?string $date): string
{
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : $date;
}

/** Formats a MySQL time (H:i:s) into "9:00 AM". */
function formatTime(?string $time): string
{
    if (!$time) return '';
    $ts = strtotime($time);
    return $ts ? date('g:i A', $ts) : $time;
}

/** Formats a datetime into "January 15, 2026 10:00 AM". */
function formatDateTime(?string $dt): string
{
    if (!$dt) return '';
    $ts = strtotime($dt);
    return $ts ? date('F j, Y g:i A', $ts) : $dt;
}

/**
 * Generates the next reservation reference number in the format
 * BRG-YYYY-00001, based on the highest existing number for the year.
 */
function generateReservationReference(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare(
        "SELECT reference_number FROM reservations
         WHERE reference_number LIKE ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute(["BRG-$year-%"]);
    $last = $stmt->fetchColumn();

    $nextNumber = 1;
    if ($last) {
        $parts = explode('-', $last);
        $nextNumber = (int)end($parts) + 1;
    }
    return sprintf('BRG-%s-%05d', $year, $nextNumber);
}

/**
 * Generates the next certificate reference number: CERT-YYYY-00001
 */
function generateCertificateReference(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare(
        "SELECT reference_number FROM certificates
         WHERE reference_number LIKE ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute(["CERT-$year-%"]);
    $last = $stmt->fetchColumn();

    $nextNumber = 1;
    if ($last) {
        $parts = explode('-', $last);
        $nextNumber = (int)end($parts) + 1;
    }
    return sprintf('CERT-%s-%05d', $year, $nextNumber);
}

/** Returns a small colored badge (as HTML) for a reservation/certificate status. */
function statusBadge(string $status): string
{
    $map = [
        'Pending'   => 'badge-pending',
        'Confirmed' => 'badge-confirmed',
        'Completed' => 'badge-completed',
        'Cancelled' => 'badge-cancelled',
        'Rejected'  => 'badge-rejected',
        'No-show'   => 'badge-noshow',
    ];
    $class = $map[$status] ?? 'badge-pending';
    return '<span class="badge ' . $class . '">' . h($status) . '</span>';
}

/** Fetches the resident row (with name) that belongs to a given user_id. */
function getResidentByUserId(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM residents WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Fetches the staff row that belongs to a given user_id. */
function getStaffByUserId(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM staff WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Returns the most recent issued certificate date for a resident, or null. */
function getLastCertificateDate(PDO $pdo, int $residentId): ?string
{
    $stmt = $pdo->prepare(
        'SELECT issued_at FROM certificates WHERE resident_id = ? ORDER BY issued_at DESC LIMIT 1'
    );
    $stmt->execute([$residentId]);
    $val = $stmt->fetchColumn();
    return $val ?: null;
}
