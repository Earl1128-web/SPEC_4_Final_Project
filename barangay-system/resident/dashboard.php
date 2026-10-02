<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);
if (!$resident) {
    die('Resident profile not found.');
}

// Upcoming reservation: the soonest one that is not cancelled/rejected/completed
$stmt = $pdo->prepare(
    "SELECT * FROM reservations
     WHERE resident_id = ? AND status IN ('Pending','Confirmed')
     ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1"
);
$stmt->execute([$resident['id']]);
$upcoming = $stmt->fetch();

$lastCertDate = getLastCertificateDate($pdo, $resident['id']);

$pageTitle = "My Dashboard | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Welcome, <?= h($resident['first_name'] . ' ' . $resident['last_name']) ?></h1>

<div class="card">
    <h2>Upcoming Reservation</h2>
    <?php if ($upcoming): ?>
        <table>
            <tr><th>Reference</th><td><?= h($upcoming['reference_number']) ?></td></tr>
            <tr><th>Certificate</th><td><?= h($upcoming['certificate_type']) ?></td></tr>
            <tr><th>Date</th><td><?= formatDate($upcoming['appointment_date']) ?></td></tr>
            <tr><th>Time</th><td><?= formatTime($upcoming['appointment_time']) ?></td></tr>
            <tr><th>Status</th><td><?= statusBadge($upcoming['status']) ?></td></tr>
        </table>
    <?php else: ?>
        <p>You have no upcoming reservation.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Last Barangay Certificate</h2>
    <p><?= $lastCertDate ? formatDate($lastCertDate) : 'No previous certificate record.' ?></p>
</div>

<div class="btn-group">
    <a class="btn" href="/barangay-system/resident/reservation.php">Make Reservation</a>
    <a class="btn btn-secondary" href="/barangay-system/resident/reservations.php">View Reservations</a>
    <a class="btn btn-secondary" href="/barangay-system/resident/certificates.php">Certificate History</a>
    <a class="btn btn-secondary" href="/barangay-system/resident/profile.php">My Profile</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
