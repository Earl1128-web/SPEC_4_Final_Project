<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin', 'staff']);

$errors = [];

// ---- Create a new appointment slot ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_slot'])) {
    verifyCsrf();

    $date = trim($_POST['appointment_date'] ?? '');
    $start = trim($_POST['start_time'] ?? '');
    $end = trim($_POST['end_time'] ?? '');
    $max = (int)($_POST['max_reservations'] ?? 0);

    if (!strtotime($date)) $errors[] = 'Please provide a valid date.';
    if (!$start || !$end) $errors[] = 'Please provide both a start and end time.';
    if ($start && $end && strtotime($start) >= strtotime($end)) $errors[] = 'End time must be after start time.';
    if ($max < 1) $errors[] = 'Maximum reservations must be at least 1.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO appointment_slots (appointment_date, start_time, end_time, max_reservations, status)
                 VALUES (?, ?, ?, ?, "open")'
            );
            $stmt->execute([$date, $start, $end, $max]);
            $_SESSION['flash_success'] = 'Appointment slot created.';
        } catch (PDOException $e) {
            $errors[] = 'That exact slot already exists for this date.';
        }
    }

    if (!$errors) {
        header('Location: /barangay-system/admin/appointments.php');
        exit;
    }
}

// ---- Close/open a slot ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_slot_id'])) {
    verifyCsrf();
    $slotId = (int)$_POST['toggle_slot_id'];
    $stmt = $pdo->prepare('SELECT status FROM appointment_slots WHERE id = ?');
    $stmt->execute([$slotId]);
    $status = $stmt->fetchColumn();
    if ($status) {
        $newStatus = $status === 'open' ? 'closed' : 'open';
        $pdo->prepare('UPDATE appointment_slots SET status = ? WHERE id = ?')->execute([$newStatus, $slotId]);
        $_SESSION['flash_success'] = "Slot marked as $newStatus.";
    }
    header('Location: /barangay-system/admin/appointments.php');
    exit;
}

$stmt = $pdo->query(
    "SELECT s.*,
        (SELECT COUNT(*) FROM reservations r
            WHERE r.appointment_date = s.appointment_date AND r.appointment_time = s.start_time
              AND r.status IN ('Pending','Confirmed','Completed')) AS taken_count
     FROM appointment_slots s
     ORDER BY s.appointment_date ASC, s.start_time ASC"
);
$slots = $stmt->fetchAll();

$pageTitle = "Appointment Management | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Appointment Slot Management</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Create New Slot</h2>
    <form method="post" class="form-row" style="align-items:flex-end;">
        <?= csrfField() ?>
        <input type="hidden" name="create_slot" value="1">
        <div class="form-group"><label>Date</label><input type="date" name="appointment_date" required></div>
        <div class="form-group"><label>Start Time</label><input type="time" name="start_time" required></div>
        <div class="form-group"><label>End Time</label><input type="time" name="end_time" required></div>
        <div class="form-group"><label>Max Reservations</label><input type="text" name="max_reservations" value="5"></div>
        <div class="form-group" style="flex:0;"><button class="btn" type="submit">Create Slot</button></div>
    </form>
</div>

<div class="card">
    <h2>All Slots</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date</th><th>Time</th><th>Booked / Max</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$slots): ?>
                <tr><td colspan="5">No appointment slots created yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($slots as $s): ?>
                <tr>
                    <td><?= formatDate($s['appointment_date']) ?></td>
                    <td><?= formatTime($s['start_time']) ?> - <?= formatTime($s['end_time']) ?></td>
                    <td><?= (int)$s['taken_count'] ?> / <?= (int)$s['max_reservations'] ?></td>
                    <td><span class="badge <?= $s['status'] === 'open' ? 'badge-completed' : 'badge-cancelled' ?>"><?= h(ucfirst($s['status'])) ?></span></td>
                    <td>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="toggle_slot_id" value="<?= (int)$s['id'] ?>">
                            <button class="btn btn-sm <?= $s['status'] === 'open' ? 'btn-danger' : 'btn-success' ?>" type="submit">
                                <?= $s['status'] === 'open' ? 'Close' : 'Reopen' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
