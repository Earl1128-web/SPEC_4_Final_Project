<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['staff', 'admin']);

// Today's stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE appointment_date = CURDATE()");
$stmt->execute();
$todayTotal = (int)$stmt->fetchColumn();

$statusCounts = [];
foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled'] as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE appointment_date = CURDATE() AND status = ?");
    $stmt->execute([$status]);
    $statusCounts[$status] = (int)$stmt->fetchColumn();
}

// Today's reservation list
$stmt = $pdo->prepare(
    "SELECT r.*, res.first_name, res.last_name
     FROM reservations r
     JOIN residents res ON res.id = r.resident_id
     WHERE r.appointment_date = CURDATE()
     ORDER BY r.appointment_time ASC"
);
$stmt->execute();
$todayReservations = $stmt->fetchAll();

// Upcoming reservations (next 7 days, excluding today)
$stmt = $pdo->prepare(
    "SELECT r.*, res.first_name, res.last_name
     FROM reservations r
     JOIN residents res ON res.id = r.resident_id
     WHERE r.appointment_date > CURDATE() AND r.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
       AND r.status IN ('Pending','Confirmed')
     ORDER BY r.appointment_date ASC, r.appointment_time ASC
     LIMIT 20"
);
$stmt->execute();
$upcomingReservations = $stmt->fetchAll();

$pageTitle = "Staff Dashboard | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Staff Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-number"><?= $todayTotal ?></div><div class="stat-label">Today's Reservations</div></div>
    <div class="stat-card"><div class="stat-number"><?= $statusCounts['Pending'] ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-number"><?= $statusCounts['Confirmed'] ?></div><div class="stat-label">Confirmed</div></div>
    <div class="stat-card"><div class="stat-number"><?= $statusCounts['Completed'] ?></div><div class="stat-label">Completed</div></div>
    <div class="stat-card"><div class="stat-number"><?= $statusCounts['Cancelled'] ?></div><div class="stat-label">Cancelled</div></div>
</div>

<div class="card">
    <h2>Today's Reservations</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Reference</th><th>Resident</th><th>Time</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$todayReservations): ?>
                <tr><td colspan="5">No reservations for today.</td></tr>
            <?php endif; ?>
            <?php foreach ($todayReservations as $r): ?>
                <tr>
                    <td><?= h($r['reference_number']) ?></td>
                    <td><?= h($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= formatTime($r['appointment_time']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td><a href="/barangay-system/staff/reservations.php?search=<?= urlencode($r['reference_number']) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Upcoming Reservations (Next 7 Days)</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Reference</th><th>Resident</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$upcomingReservations): ?>
                <tr><td colspan="5">No upcoming reservations.</td></tr>
            <?php endif; ?>
            <?php foreach ($upcomingReservations as $r): ?>
                <tr>
                    <td><?= h($r['reference_number']) ?></td>
                    <td><?= h($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= formatDate($r['appointment_date']) ?></td>
                    <td><?= formatTime($r['appointment_time']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
