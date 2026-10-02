<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

// Enable/disable a resident's user account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    verifyCsrf();
    $userId = (int)$_POST['toggle_user_id'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'resident'");
    $stmt->execute([$userId]);
    $status = $stmt->fetchColumn();
    if ($status) {
        $newStatus = $status === 'active' ? 'disabled' : 'active';
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
        logAction($pdo, $_SESSION['user_id'], 'TOGGLE_RESIDENT_STATUS', "User #$userId set to $newStatus");
        $_SESSION['flash_success'] = "Account status updated to $newStatus.";
    }
    header('Location: /barangay-system/admin/residents.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT r.*, u.email, u.status, u.username
        FROM residents r JOIN users u ON u.id = r.user_id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR u.email LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY r.last_name ASC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();

$pageTitle = "Resident Management | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Resident Management</h1>

<div class="card">
    <form method="get" class="form-row" style="align-items:flex-end;">
        <div class="form-group">
            <label for="search">Search by name or email</label>
            <input type="text" id="search" name="search" value="<?= h($search) ?>">
        </div>
        <div class="form-group" style="flex:0;"><button class="btn" type="submit">Search</button></div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$residents): ?>
                <tr><td colspan="5">No residents found.</td></tr>
            <?php endif; ?>
            <?php foreach ($residents as $r): ?>
                <tr>
                    <td><a href="/barangay-system/staff/resident-view.php?id=<?= (int)$r['id'] ?>"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></a></td>
                    <td><?= h($r['email']) ?></td>
                    <td><?= h($r['contact_number']) ?></td>
                    <td><span class="badge <?= $r['status'] === 'active' ? 'badge-completed' : 'badge-cancelled' ?>"><?= h(ucfirst($r['status'])) ?></span></td>
                    <td>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="toggle_user_id" value="<?= (int)$r['user_id'] ?>">
                            <button class="btn btn-sm <?= $r['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>" type="submit"
                                data-confirm="Are you sure you want to <?= $r['status'] === 'active' ? 'disable' : 'enable' ?> this account?">
                                <?= $r['status'] === 'active' ? 'Disable' : 'Enable' ?>
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
