<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$errors = [];

// ---- Create staff account ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    verifyCsrf();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $position  = trim($_POST['position'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($firstName === '') $errors[] = 'First name is required.';
    if ($lastName === '') $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email.';
    if (strlen($username) < 4) $errors[] = 'Username must be at least 4 characters.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) $errors[] = 'That email or username is already in use.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'staff', 'active')"
            );
            $stmt->execute([$username, $email, $hash]);
            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO staff (user_id, first_name, last_name, position) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $firstName, $lastName, $position ?: null]);

            logAction($pdo, $_SESSION['user_id'], 'CREATE_STAFF', "Created staff account for $firstName $lastName");
            $pdo->commit();

            $_SESSION['flash_success'] = 'Staff account created successfully.';
            header('Location: /barangay-system/admin/staff.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Create staff failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while creating the staff account.';
        }
    }
}

// ---- Enable/disable staff account ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    verifyCsrf();
    $userId = (int)$_POST['toggle_user_id'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'staff'");
    $stmt->execute([$userId]);
    $status = $stmt->fetchColumn();
    if ($status) {
        $newStatus = $status === 'active' ? 'disabled' : 'active';
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
        logAction($pdo, $_SESSION['user_id'], 'TOGGLE_STAFF_STATUS', "Staff user #$userId set to $newStatus");
        $_SESSION['flash_success'] = "Staff account status updated to $newStatus.";
    }
    header('Location: /barangay-system/admin/staff.php');
    exit;
}

$stmt = $pdo->query(
    "SELECT s.*, u.email, u.status, u.username
     FROM staff s JOIN users u ON u.id = s.user_id
     WHERE u.role = 'staff' ORDER BY s.last_name ASC"
);
$staffList = $stmt->fetchAll();

$pageTitle = "Staff Management | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Staff Management</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Create Staff Account</h2>
    <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="create_staff" value="1">
        <div class="form-row">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name"></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name"></div>
            <div class="form-group"><label>Position</label><input type="text" name="position" placeholder="e.g. Barangay Clerk"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
            <div class="form-group"><label>Username</label><input type="text" name="username"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password"></div>
        </div>
        <button type="submit" class="btn">Create Staff Account</button>
    </form>
</div>

<div class="card">
    <h2>Current Staff</h2>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Name</th><th>Position</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$staffList): ?>
                <tr><td colspan="5">No staff accounts found.</td></tr>
            <?php endif; ?>
            <?php foreach ($staffList as $s): ?>
                <tr>
                    <td><?= h($s['first_name'] . ' ' . $s['last_name']) ?></td>
                    <td><?= h($s['position'] ?? '') ?></td>
                    <td><?= h($s['email']) ?></td>
                    <td><span class="badge <?= $s['status'] === 'active' ? 'badge-completed' : 'badge-cancelled' ?>"><?= h(ucfirst($s['status'])) ?></span></td>
                    <td>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="toggle_user_id" value="<?= (int)$s['user_id'] ?>">
                            <button class="btn btn-sm <?= $s['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>" type="submit"
                                data-confirm="Are you sure you want to <?= $s['status'] === 'active' ? 'disable' : 'enable' ?> this staff account?">
                                <?= $s['status'] === 'active' ? 'Disable' : 'Enable' ?>
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
