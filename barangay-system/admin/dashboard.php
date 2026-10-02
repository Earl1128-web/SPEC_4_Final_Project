<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$totalResidents = (int)$pdo->query("SELECT COUNT(*) FROM residents")->fetchColumn();
$totalReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$todayReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE appointment_date = CURDATE()")->fetchColumn();
$completedCertificates = (int)$pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$pendingReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Pending'")->fetchColumn();

$pageTitle = "Admin Dashboard | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Administrator Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-number"><?= $totalResidents ?></div><div class="stat-label">Total Residents</div></div>
    <div class="stat-card"><div class="stat-number"><?= $totalReservations ?></div><div class="stat-label">Total Reservations</div></div>
    <div class="stat-card"><div class="stat-number"><?= $todayReservations ?></div><div class="stat-label">Today's Reservations</div></div>
    <div class="stat-card"><div class="stat-number"><?= $completedCertificates ?></div><div class="stat-label">Completed Certificates</div></div>
    <div class="stat-card"><div class="stat-number"><?= $pendingReservations ?></div><div class="stat-label">Pending Reservations</div></div>
</div>

<div class="card">
    <h2>Manage</h2>
    <div class="btn-group">
        <a class="btn btn-secondary" href="/barangay-system/admin/residents.php">Resident Management</a>
        <a class="btn btn-secondary" href="/barangay-system/admin/staff.php">Staff Management</a>
        <a class="btn btn-secondary" href="/barangay-system/admin/appointments.php">Appointment Management</a>
        <a class="btn btn-secondary" href="/barangay-system/admin/reservations.php">Reservation Management</a>
        <a class="btn btn-secondary" href="/barangay-system/admin/audit-logs.php">Audit Logs</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
