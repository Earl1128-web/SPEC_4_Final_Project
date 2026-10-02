<?php
/**
 * includes/header.php
 * Expects an optional $pageTitle variable to be set before including.
 * Assumes auth.php has already been included (session started).
 */
$pageTitle = $pageTitle ?? "Barangay Pahina's Online Reservation of Brgy. Certificate";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<link rel="stylesheet" href="/barangay-system/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="/barangay-system/login.php">🏛️ Barangay Pahina's Online Reservation</a>
        <nav class="main-nav">
            <?php if (isLoggedIn()): ?>
                <?php if (currentRole() === 'resident'): ?>
                    <a href="/barangay-system/resident/dashboard.php">Dashboard</a>
                    <a href="/barangay-system/resident/reservation.php">Make Reservation</a>
                    <a href="/barangay-system/resident/reservations.php">Reservations</a>
                    <a href="/barangay-system/resident/certificates.php">Certificates</a>
                    <a href="/barangay-system/resident/profile.php">Profile</a>
                <?php elseif (currentRole() === 'staff'): ?>
                    <a href="/barangay-system/staff/dashboard.php">Dashboard</a>
                    <a href="/barangay-system/staff/reservations.php">Reservations</a>
                    <a href="/barangay-system/staff/residents.php">Residents</a>
                <?php elseif (currentRole() === 'admin'): ?>
                    <a href="/barangay-system/admin/dashboard.php">Dashboard</a>
                    <a href="/barangay-system/admin/residents.php">Residents</a>
                    <a href="/barangay-system/admin/staff.php">Staff</a>
                    <a href="/barangay-system/admin/appointments.php">Appointments</a>
                    <a href="/barangay-system/admin/reservations.php">Reservations</a>
                    <a href="/barangay-system/admin/audit-logs.php">Audit Logs</a>
                <?php endif; ?>
                <span class="nav-user">Hi, <?= h($_SESSION['display_name'] ?? 'User') ?></span>
                <a href="/barangay-system/logout.php" class="btn-link">Logout</a>
            <?php else: ?>
                <a href="/barangay-system/login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="page-container">
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= h($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error"><?= h($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
