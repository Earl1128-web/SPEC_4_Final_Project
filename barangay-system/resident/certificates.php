<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['resident']);

$resident = getResidentByUserId($pdo, $_SESSION['user_id']);

$stmt = $pdo->prepare(
    'SELECT * FROM certificates WHERE resident_id = ? ORDER BY issued_at DESC'
);
$stmt->execute([$resident['id']]);
$certificates = $stmt->fetchAll();

$lastCertDate = getLastCertificateDate($pdo, $resident['id']);

$pageTitle = "Certificate History | Barangay Pahina's Online Reservation";
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Certificate History</h1>

<div class="card">
    <?php if (!$certificates): ?>
        <p>No previous certificate record.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Certificate</th><th>Purpose</th><th>Reference</th><th>Date Issued</th></tr>
                </thead>
                <tbody>
                <?php foreach ($certificates as $c): ?>
                    <tr>
                        <td><?= h($c['certificate_type']) ?></td>
                        <td><?= h($c['purpose']) ?></td>
                        <td><?= h($c['reference_number']) ?></td>
                        <td><?= formatDate($c['issued_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:16px;"><strong>Last Certificate Issued:</strong> <?= formatDate($lastCertDate) ?></p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
