<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /barangay-system/resident/reservation.php');
    exit;
}
verifyCsrf();

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);

$certificateType = trim($_POST['certificate_type'] ?? 'Barangay Certificate');
$purpose = trim($_POST['purpose'] ?? '');
$otherPurpose = trim($_POST['other_purpose'] ?? '');
$slotId = (int)($_POST['selected_slot_id'] ?? 0);

if ($purpose === 'Other') {
    $purpose = $otherPurpose !== '' ? $otherPurpose : 'Other';
}

// --- Server-side validation (never trust the client) ---
if ($purpose === '') {
    $_SESSION['flash_error'] = 'Please select the purpose of your certificate.';
    header('Location: /barangay-system/resident/reservation.php');
    exit;
}
if ($slotId <= 0) {
    $_SESSION['flash_error'] = 'Please select an appointment date.';
    header('Location: /barangay-system/resident/reservation.php');
    exit;
}

// Re-check: resident must not already have an active reservation
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM reservations WHERE resident_id = ? AND status IN ('Pending','Confirmed')"
);
$stmt->execute([$resident['id']]);
if ((int)$stmt->fetchColumn() > 0) {
    $_SESSION['flash_error'] = 'You already have an active reservation.';
    header('Location: /barangay-system/resident/reservation.php');
    exit;
}

// Fetch the slot and re-check availability directly against the database.
// We never trust JavaScript's idea of "available" — PHP checks again here.
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM appointment_slots WHERE id = ? AND status = "open"');
    $stmt->execute([$slotId]);
    $slot = $stmt->fetch();

    if (!$slot || strtotime($slot['appointment_date']) < strtotime(date('Y-m-d'))) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'This appointment slot is no longer available.';
        header('Location: /barangay-system/resident/reservation.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM reservations
         WHERE appointment_date = ? AND appointment_time = ?
           AND status IN ('Pending','Confirmed','Completed')"
    );
    $stmt->execute([$slot['appointment_date'], $slot['start_time']]);
    $takenCount = (int)$stmt->fetchColumn();

    if ($takenCount >= $slot['max_reservations']) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'This appointment slot is already full.';
        header('Location: /barangay-system/resident/reservation.php');
        exit;
    }

    $reference = generateReservationReference($pdo);

    $stmt = $pdo->prepare(
        "INSERT INTO reservations
            (reference_number, resident_id, certificate_type, purpose, appointment_date, appointment_time, status)
         VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
    );
    $stmt->execute([
        $reference, $resident['id'], $certificateType, $purpose,
        $slot['appointment_date'], $slot['start_time'],
    ]);
    $reservationId = (int)$pdo->lastInsertId();

    $pdo->commit();

    header('Location: /barangay-system/resident/reservation-success.php?id=' . $reservationId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Reservation failed: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Something went wrong while saving your reservation. Please try again.';
    header('Location: /barangay-system/resident/reservation.php');
    exit;
}
