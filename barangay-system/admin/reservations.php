<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$statusFilter = trim($_GET['status'] ?? '');
$sql = "SELECT r.*, res.first_name, res.last_name
        FROM reservations r JOIN residents res ON res.id = r.resident_id WHERE 1=1";
$params = [];
if ($statusFilter !== '') {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY r.appointment_date DESC, r.appointment_time DESC LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$pageTitle = "Reservation Management | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Reservation Management</h1>

<div class="card">
    <form method="get" class="form-row" style="align-items:flex-end;">
        <div class="form-group">
            <label for="status">Filter by Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <?php foreach (['Pending','Confirmed','Completed','Cancelled','Rejected','No-show'] as $s): ?>
                    <option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:0;"><button class="btn" type="submit">Filter</button></div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Reference</th><th>Resident</th><th>Certificate</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$reservations): ?>
                <tr><td colspan="6">No reservations found.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= h($r['reference_number']) ?></td>
                    <td><?= h($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= h($r['certificate_type']) ?></td>
                    <td><?= formatDate($r['appointment_date']) ?></td>
                    <td><?= formatTime($r['appointment_time']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="form-hint">To confirm, reject, or complete reservations, use the <a href="/barangay-system/staff/reservations.php">Staff Reservations</a> page.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
