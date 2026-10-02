<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['staff', 'admin']);

// ---- Handle status update actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    verifyCsrf();
    $id = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    $allowedActions = [
        'confirm'  => 'Confirmed',
        'reject'   => 'Rejected',
        'noshow'   => 'No-show',
    ];

    if (isset($allowedActions[$action])) {
        $newStatus = $allowedActions[$action];
        $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);
        logAction($pdo, $_SESSION['user_id'], strtoupper($action) . '_RESERVATION', "Reservation #$id set to $newStatus");
        $_SESSION['flash_success'] = "Reservation marked as $newStatus.";
    }
    header('Location: /barangay-system/staff/reservations.php');
    exit;
}

// ---- Filters ----
$search = trim($_GET['search'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT r.*, res.first_name, res.last_name, res.contact_number
        FROM reservations r
        JOIN residents res ON res.id = r.resident_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (r.reference_number LIKE ? OR res.first_name LIKE ? OR res.last_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($dateFilter !== '') {
    $sql .= " AND r.appointment_date = ?";
    $params[] = $dateFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY r.appointment_date DESC, r.appointment_time DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$pageTitle = "Manage Reservations | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Manage Reservations</h1>

<div class="card">
    <form method="get" class="form-row" style="align-items:flex-end;">
        <div class="form-group">
            <label for="search">Search (reference or name)</label>
            <input type="text" id="search" name="search" value="<?= h($search) ?>">
        </div>
        <div class="form-group">
            <label for="date">Filter by Date</label>
            <input type="date" id="date" name="date" value="<?= h($dateFilter) ?>">
        </div>
        <div class="form-group">
            <label for="status">Filter by Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <?php foreach (['Pending','Confirmed','Completed','Cancelled','Rejected','No-show'] as $s): ?>
                    <option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:0;">
            <button type="submit" class="btn">Search</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Reference</th><th>Resident</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$reservations): ?>
                <tr><td colspan="7">No reservations found.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= h($r['reference_number']) ?></td>
                    <td><?= h($r['first_name'] . ' ' . $r['last_name']) ?><br><small><?= h($r['contact_number']) ?></small></td>
                    <td><?= formatDate($r['appointment_date']) ?></td>
                    <td><?= formatTime($r['appointment_time']) ?></td>
                    <td><?= h($r['purpose']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td>
                        <div class="btn-group">
                        <?php if ($r['status'] === 'Pending'): ?>
                            <form method="post"><?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button class="btn btn-success btn-sm" type="submit">Confirm</button>
                            </form>
                            <form method="post"><?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-danger btn-sm" type="submit"
                                    data-confirm="Reject this reservation?">Reject</button>
                            </form>
                        <?php elseif ($r['status'] === 'Confirmed'): ?>
                            <a class="btn btn-sm" href="/barangay-system/staff/issue-certificate.php?reservation_id=<?= (int)$r['id'] ?>">Issue Certificate</a>
                            <form method="post"><?= csrfField() ?>
                                <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="noshow">
                                <button class="btn btn-secondary btn-sm" type="submit"
                                    data-confirm="Mark this reservation as No-show?">No-show</button>
                            </form>
                        <?php else: ?>
                            <span class="form-hint">No actions available</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
