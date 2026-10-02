<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ? AND resident_id = ?');
$stmt->execute([$id, $resident['id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header('Location: /barangay-system/resident/dashboard.php');
    exit;
}

$pageTitle = "Reservation Successful | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:520px;margin:0 auto;">
    <h2 style="color:#1e7e34;">Reservation Successful!</h2>
    <table>
        <tr><th>Reference Number</th><td><?= h($reservation['reference_number']) ?></td></tr>
        <tr><th>Resident</th><td><?= h($resident['first_name'] . ' ' . $resident['last_name']) ?></td></tr>
        <tr><th>Certificate</th><td><?= h($reservation['certificate_type']) ?></td></tr>
        <tr><th>Purpose</th><td><?= h($reservation['purpose']) ?></td></tr>
        <tr><th>Appointment</th><td><?= formatDate($reservation['appointment_date']) ?></td></tr>
        <tr><th>Time</th><td><?= formatTime($reservation['appointment_time']) ?></td></tr>
        <tr><th>Status</th><td><?= statusBadge($reservation['status']) ?></td></tr>
    </table>
    <div class="btn-group" style="margin-top:16px;">
        <a class="btn" href="/barangay-system/resident/dashboard.php">Back to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
