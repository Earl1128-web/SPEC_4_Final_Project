<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    verifyCsrf();
    $cancelId = (int)$_POST['cancel_id'];

    // Only allow cancelling reservations that belong to this resident and are still Pending/Confirmed
    $stmt = $pdo->prepare(
        "UPDATE reservations SET status = 'Cancelled'
         WHERE id = ? AND resident_id = ? AND status IN ('Pending','Confirmed')"
    );
    $stmt->execute([$cancelId, $resident['id']]);

    $_SESSION['flash_success'] = $stmt->rowCount() > 0
        ? 'Reservation cancelled.'
        : 'That reservation could not be cancelled.';
    header('Location: /barangay-system/resident/reservations.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM reservations WHERE resident_id = ? ORDER BY appointment_date DESC, appointment_time DESC'
);
$stmt->execute([$resident['id']]);
$reservations = $stmt->fetchAll();

$pageTitle = "My Reservations | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Reservation History</h1>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Reference</th><th>Certificate</th><th>Purpose</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (!$reservations): ?>
                <tr><td colspan="7">You have no reservations yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= h($r['reference_number']) ?></td>
                    <td><?= h($r['certificate_type']) ?></td>
                    <td><?= h($r['purpose']) ?></td>
                    <td><?= formatDate($r['appointment_date']) ?></td>
                    <td><?= formatTime($r['appointment_time']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td>
                        <?php if (in_array($r['status'], ['Pending', 'Confirmed'], true)): ?>
                            <form method="post" style="display:inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="cancel_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Are you sure you want to cancel this reservation?">
                                    Cancel
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
