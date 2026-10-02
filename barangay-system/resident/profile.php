<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $email   = trim($_POST['email'] ?? '');

    if ($address === '') $errors[] = 'Address is required.';
    if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $contact)) $errors[] = 'Please provide a valid contact number.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address.';

    if (!$errors) {
        // Ensure email isn't already used by someone else
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'That email address is already used by another account.';
        }
    }

    if (!$errors) {
        $pdo->prepare('UPDATE residents SET address = ?, contact_number = ? WHERE id = ?')
            ->execute([$address, $contact, $resident['id']]);
        $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')
            ->execute([$email, $user['id']]);

        $_SESSION['flash_success'] = 'Profile updated successfully.';
        header('Location: /barangay-system/resident/profile.php');
        exit;
    }
}

// Refresh values after possible update
$resident = getResidentByUserId($pdo, $_SESSION['user_id']);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$pageTitle = "My Profile | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>My Profile</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endforeach; ?>

<div class="card" style="max-width:560px;">
    <form method="post">
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" value="<?= h($resident['first_name']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" value="<?= h($resident['last_name']) ?>" disabled>
            </div>
        </div>
        <div class="form-group">
            <label>Date of Birth</label>
            <input type="text" value="<?= formatDate($resident['date_of_birth']) ?>" disabled>
        </div>
        <p class="form-hint">To change your name or date of birth, please visit the barangay office.</p>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= h($resident['address']) ?>">
        </div>
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="tel" id="contact_number" name="contact_number" value="<?= h($resident['contact_number']) ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= h($user['email']) ?>">
        </div>

        <button type="submit" class="btn">Update Profile</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
