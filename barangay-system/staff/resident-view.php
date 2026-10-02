<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['staff', 'admin']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM residents WHERE id = ?');
$stmt->execute([$id]);
$resident = $stmt->fetch();

if (!$resident) {
    die('Resident not found.');
}

$stmt = $pdo->prepare('SELECT * FROM certificates WHERE resident_id = ? ORDER BY issued_at DESC');
$stmt->execute([$id]);
$certificates = $stmt->fetchAll();

$lastCertDate = getLastCertificateDate($pdo, $id);

$stmt = $pdo->prepare('SELECT * FROM reservations WHERE resident_id = ? ORDER BY appointment_date DESC LIMIT 10');
$stmt->execute([$id]);
$reservations = $stmt->fetchAll();

$pageTitle = "Resident Profile | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Resident Information</h1>

<div class="card">
    <table>
        <tr><th>Name</th><td><?= h($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']) ?></td></tr>
        <tr><th>Date of Birth</th><td><?= formatDate($resident['date_of_birth']) ?></td></tr>
        <tr><th>Address</th><td><?= h($resident['address']) ?></td></tr>
        <tr><th>Contact</th><td><?= h($resident['contact_number']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h2>LAST BARANGAY CERTIFICATE</h2>
    <p style="font-size:1.2rem;"><strong><?= $lastCertDate ? formatDate($lastCertDate) : 'No previous certificate record.' ?></strong></p>
</div>

<div class="card">
    <h2>Certificate History</h2>
    <?php if (!$certificates): ?>
        <p>No previous certificate record.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Date</th><th>Certificate</th><th>Purpose</th><th>Reference</th></tr></thead>
                <tbody>
                <?php foreach ($certificates as $c): ?>
                    <tr>
                        <td><?= formatDate($c['issued_at']) ?></td>
                        <td><?= h($c['certificate_type']) ?></td>
                        <td><?= h($c['purpose']) ?></td>
                        <td><?= h($c['reference_number']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Recent Reservations</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Reference</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$reservations): ?>
                <tr><td colspan="4">No reservations found.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= h($r['reference_number']) ?></td>
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
