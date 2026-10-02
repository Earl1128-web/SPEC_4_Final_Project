<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$stmt = $pdo->query(
    "SELECT a.*, u.username, u.role
     FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT 300"
);
$logs = $stmt->fetchAll();

$pageTitle = "Audit Logs | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Audit Logs</h1>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Date/Time</th><th>User</th><th>Role</th><th>Action</th><th>Description</th></tr></thead>
            <tbody>
            <?php if (!$logs): ?>
                <tr><td colspan="5">No audit log entries yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= formatDateTime($log['created_at']) ?></td>
                    <td><?= h($log['username'] ?? 'System') ?></td>
                    <td><?= h($log['role'] ?? '-') ?></td>
                    <td><?= h($log['action']) ?></td>
                    <td><?= h($log['description']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
