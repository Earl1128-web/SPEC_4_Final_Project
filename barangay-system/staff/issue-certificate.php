<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['staff', 'admin']);

$staff = getStaffByUserId($pdo, $_SESSION['user_id']);
if (!$staff) {
    die('Staff profile not found.');
}

$reservationId = (int)($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT r.*, res.first_name, res.last_name, res.id AS resident_id
     FROM reservations r JOIN residents res ON res.id = r.resident_id
     WHERE r.id = ?"
);
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    die('Reservation not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Verify the reservation is actually eligible (must be Confirmed, not already completed)
    if ($reservation['status'] !== 'Confirmed') {
        $errors[] = 'Only confirmed reservations can be issued a certificate.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $certRef = generateCertificateReference($pdo);

            // A certificate record is only created here, at the moment of actual issuance.
            $stmt = $pdo->prepare(
                "INSERT INTO certificates
                    (resident_id, reservation_id, certificate_type, purpose, issued_by, issued_at, reference_number)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?)"
            );
            $stmt->execute([
                $reservation['resident_id'], $reservation['id'], $reservation['certificate_type'],
                $reservation['purpose'], $staff['id'], $certRef,
            ]);

            // Mark the reservation as Completed
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'Completed' WHERE id = ?");
            $stmt->execute([$reservation['id']]);

            logAction($pdo, $_SESSION['user_id'], 'ISSUE_CERTIFICATE',
                "Issued certificate $certRef to resident #{$reservation['resident_id']}");

            $pdo->commit();

            $_SESSION['flash_success'] = "Certificate $certRef issued successfully.";
            header('Location: /barangay-system/staff/reservations.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Certificate issuance failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while issuing the certificate. Please try again.';
        }
    }
}

$pageTitle = "Issue Certificate | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Issue Certificate</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endforeach; ?>

<div class="card" style="max-width:520px;">
    <table>
        <tr><th>Reference</th><td><?= h($reservation['reference_number']) ?></td></tr>
        <tr><th>Resident</th><td><?= h($reservation['first_name'] . ' ' . $reservation['last_name']) ?></td></tr>
        <tr><th>Certificate</th><td><?= h($reservation['certificate_type']) ?></td></tr>
        <tr><th>Purpose</th><td><?= h($reservation['purpose']) ?></td></tr>
        <tr><th>Appointment</th><td><?= formatDate($reservation['appointment_date']) ?> at <?= formatTime($reservation['appointment_time']) ?></td></tr>
        <tr><th>Status</th><td><?= statusBadge($reservation['status']) ?></td></tr>
    </table>

    <?php if ($reservation['status'] === 'Confirmed'): ?>
        <form method="post" style="margin-top:16px;">
            <?= csrfField() ?>
            <input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>">
            <button type="submit" class="btn btn-success"
                data-confirm="Confirm that the certificate has actually been printed and handed to the resident?">
                Issue Certificate
            </button>
        </form>
    <?php else: ?>
        <p class="form-hint">This reservation is not eligible for certificate issuance.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
