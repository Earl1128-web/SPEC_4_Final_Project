<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['staff', 'admin']);

$search = trim($_GET['search'] ?? '');
$results = [];

if ($search !== '') {
    $like = "%$search%";
    $stmt = $pdo->prepare(
        "SELECT * FROM residents
         WHERE first_name LIKE ? OR last_name LIKE ?
            OR CONCAT(first_name, ' ', last_name) LIKE ?
            OR contact_number LIKE ? OR id = ?
         ORDER BY last_name ASC LIMIT 50"
    );
    $stmt->execute([$like, $like, $like, $like, is_numeric($search) ? (int)$search : 0]);
    $results = $stmt->fetchAll();
}

$pageTitle = "Search Residents | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Search Residents</h1>

<div class="card">
    <form method="get" class="form-row" style="align-items:flex-end;">
        <div class="form-group">
            <label for="search">Search by name, contact number, or resident ID</label>
            <input type="text" id="search" name="search" value="<?= h($search) ?>" placeholder="e.g. Juan Dela Cruz">
        </div>
        <div class="form-group" style="flex:0;">
            <button type="submit" class="btn">Search</button>
        </div>
    </form>
</div>

<?php if ($search !== ''): ?>
<div class="card">
    <h3>Results</h3>
    <?php if (!$results): ?>
        <p>No residents found matching "<?= h($search) ?>".</p>
    <?php endif; ?>
    <?php foreach ($results as $r): ?>
        <div class="card" style="background:#fafcff;">
            <strong><?= h($r['first_name'] . ' ' . $r['last_name']) ?></strong><br>
            Resident ID: <?= (int)$r['id'] ?><br>
            Address: <?= h($r['address']) ?><br>
            Contact: <?= h($r['contact_number']) ?><br>
            <a class="btn btn-sm" style="margin-top:8px;" href="/barangay-system/staff/resident-view.php?id=<?= (int)$r['id'] ?>">View Profile</a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
