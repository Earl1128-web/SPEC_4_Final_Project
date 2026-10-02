<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);

// A resident may only have ONE active (Pending/Confirmed) reservation at a time
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM reservations WHERE resident_id = ? AND status IN ('Pending','Confirmed')"
);
$stmt->execute([$resident['id']]);
$hasActiveReservation = (int)$stmt->fetchColumn() > 0;

// Fetch upcoming open slots (today onward) along with how many reservations already exist
$stmt = $pdo->prepare(
    "SELECT s.*, 
        (SELECT COUNT(*) FROM reservations r
            WHERE r.appointment_date = s.appointment_date
              AND r.appointment_time = s.start_time
              AND r.status IN ('Pending','Confirmed','Completed')
        ) AS taken_count
     FROM appointment_slots s
     WHERE s.appointment_date >= CURDATE() AND s.status = 'open'
     ORDER BY s.appointment_date ASC, s.start_time ASC"
);
$stmt->execute();
$slots = $stmt->fetchAll();

// Group slots by date for display
$slotsByDate = [];
foreach ($slots as $slot) {
    $slotsByDate[$slot['appointment_date']][] = $slot;
}

$pageTitle = "Make a Reservation | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Make a Reservation</h1>

<?php if ($hasActiveReservation): ?>
    <div class="alert alert-error">
        You already have an active reservation. Please wait until it is completed or cancel it before booking another.
    </div>
    <a class="btn btn-secondary" href="/barangay-system/resident/reservations.php">View My Reservations</a>
<?php else: ?>

<div class="card">
    <form method="post" action="/barangay-system/resident/reserve.php" id="reservationForm">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="certificate_type">Certificate Type</label>
            <select id="certificate_type" name="certificate_type">
                <option value="Barangay Certificate">Barangay Certificate</option>
            </select>
        </div>

        <div class="form-group">
            <label for="purpose">Purpose</label>
            <select id="purpose" name="purpose">
                <option value="Employment">Employment</option>
                <option value="School">School</option>
                <option value="Business">Business</option>
                <option value="Government Requirement">Government Requirement</option>
                <option value="Financial Assistance">Financial Assistance</option>
                <option value="Personal">Personal</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group" id="otherPurposeWrapper" style="display:none;">
            <label for="other_purpose">Please specify</label>
            <input type="text" id="other_purpose" name="other_purpose" placeholder="Specify your purpose">
        </div>

        <div class="form-group">
            <label>Available Appointment Slots</label>
            <?php if (!$slots): ?>
                <p class="form-hint">No available appointment slots at the moment. Please check back later.</p>
            <?php else: ?>
                <?php foreach ($slotsByDate as $date => $daySlots): ?>
                    <h3 style="margin-bottom:6px;"><?= formatDate($date) ?></h3>
                    <div class="slot-grid">
                        <?php foreach ($daySlots as $slot): ?>
                            <?php $isFull = $slot['taken_count'] >= $slot['max_reservations']; ?>
                            <div class="slot-option <?= $isFull ? 'full' : '' ?>"
                                 data-slot-id="<?= (int)$slot['id'] ?>"
                                 title="<?= $isFull ? 'Fully booked' : 'Available' ?>">
                                <?= formatTime($slot['start_time']) ?> - <?= formatTime($slot['end_time']) ?>
                                <br><small><?= $isFull ? 'Fully booked' : ($slot['max_reservations'] - $slot['taken_count']) . ' slot(s) left' ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <input type="hidden" name="selected_slot_id" id="selected_slot_id" value="">
        </div>

        <button type="submit" class="btn" <?= !$slots ? 'disabled' : '' ?>>Submit Reservation</button>
    </form>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
