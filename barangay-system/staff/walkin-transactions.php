<?php
/**
 * staff/walkin-transactions.php
 * Walk-in Transaction Management System
 * Allows staff and admins to record, manage, update, release, and print receipts for in-person transactions.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['staff', 'admin']);

$currentStaff = getStaffByUserId($pdo, $_SESSION['user_id']);
$staffId = $currentStaff['id'] ?? 1;

// ---------------------------------------------------------------------
// 1. POST ACTION HANDLERS
// ---------------------------------------------------------------------
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // A. CREATE NEW WALK-IN TRANSACTION
    if ($action === 'create') {
        $residentMode = $_POST['resident_mode'] ?? 'new';
        $residentId   = !empty($_POST['resident_id']) ? (int)$_POST['resident_id'] : null;
        $residentName = trim($_POST['resident_name'] ?? '');
        $contactNum   = trim($_POST['contact_number'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $certType     = trim($_POST['certificate_type'] ?? 'Barangay Certificate');
        $purpose      = trim($_POST['purpose'] ?? '');
        $requestDate  = trim($_POST['request_date'] ?? date('Y-m-d'));
        $assignedStaff= !empty($_POST['assigned_staff_id']) ? (int)$_POST['assigned_staff_id'] : $staffId;
        $amountPaid   = (float)($_POST['amount_paid'] ?? 0.00);
        $paymentStatus= trim($_POST['payment_status'] ?? 'Free');
        $receiptNum   = trim($_POST['receipt_number'] ?? '');
        $status       = trim($_POST['status'] ?? 'Pending');
        $remarks      = trim($_POST['remarks'] ?? '');

        // Validation
        if ($residentName === '') {
            $errors[] = 'Resident full name is required.';
        }
        if ($purpose === '') {
            $errors[] = 'Purpose of request is required.';
        }
        if ($requestDate === '' || !strtotime($requestDate)) {
            $errors[] = 'A valid request date is required.';
        }
        if ($amountPaid < 0) {
            $errors[] = 'Amount paid cannot be negative.';
        }

        $validCertTypes = [
            'Barangay Certificate',
            'Barangay Clearance',
            'Certificate of Residency',
            'Certificate of Indigency'
        ];
        if (!in_array($certType, $validCertTypes, true)) {
            $certType = 'Barangay Certificate';
        }

        $validPaymentStatuses = ['Free', 'Paid', 'Unpaid', 'Waived'];
        if (!in_array($paymentStatus, $validPaymentStatuses, true)) {
            $paymentStatus = 'Free';
        }

        $validStatuses = ['Pending', 'Processing', 'Completed', 'Released', 'Cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'Pending';
        }

        // If registered resident chosen, verify existence
        if ($residentId) {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, address, contact_number FROM residents WHERE id = ?");
            $stmt->execute([$residentId]);
            $resRow = $stmt->fetch();
            if (!$resRow) {
                $residentId = null; // Fallback to walk-in details
            }
        }

        if (!$errors) {
            try {
                $refNumber = generateWalkinReference($pdo);
                $releasedAt = ($status === 'Released') ? date('Y-m-d H:i:s') : null;
                $releasedBy = ($status === 'Released') ? $staffId : null;

                $stmt = $pdo->prepare(
                    "INSERT INTO walkin_transactions
                        (reference_number, resident_id, resident_name, contact_number, email, address,
                         certificate_type, purpose, request_date, assigned_staff_id, amount_paid,
                         payment_status, receipt_number, status, remarks, released_at, released_by)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $refNumber, $residentId, $residentName, $contactNum, $email, $address,
                    $certType, $purpose, $requestDate, $assignedStaff, $amountPaid,
                    $paymentStatus, $receiptNum, $status, $remarks, $releasedAt, $releasedBy
                ]);

                logAction($pdo, $_SESSION['user_id'], 'CREATE_WALKIN_TRANSACTION',
                    "Recorded walk-in transaction $refNumber for $residentName ($certType)");

                $_SESSION['flash_success'] = "Walk-in transaction $refNumber recorded successfully.";
                header('Location: ' . appUrl('/staff/walkin-transactions.php'));
                exit;
            } catch (PDOException $e) {
                error_log("Failed to create walk-in transaction: " . $e->getMessage());
                $errors[] = 'A database error occurred while saving the walk-in transaction. Please try again.';
            }
        }
    }

    // B. UPDATE STATUS / DETAILS
    elseif ($action === 'update_status') {
        $transId       = (int)($_POST['transaction_id'] ?? 0);
        $newStatus     = trim($_POST['new_status'] ?? '');
        $paymentStatus = trim($_POST['payment_status'] ?? 'Free');
        $amountPaid    = (float)($_POST['amount_paid'] ?? 0.00);
        $remarks       = trim($_POST['remarks'] ?? '');

        $validStatuses = ['Pending', 'Processing', 'Completed', 'Released', 'Cancelled'];
        if ($transId > 0 && in_array($newStatus, $validStatuses, true)) {
            $stmt = $pdo->prepare("SELECT * FROM walkin_transactions WHERE id = ?");
            $stmt->execute([$transId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $releasedAt = $existing['released_at'];
                $releasedBy = $existing['released_by'];

                if ($newStatus === 'Released' && empty($releasedAt)) {
                    $releasedAt = date('Y-m-d H:i:s');
                    $releasedBy = $staffId;
                }

                $stmt = $pdo->prepare(
                    "UPDATE walkin_transactions
                     SET status = ?, payment_status = ?, amount_paid = ?, remarks = ?, released_at = ?, released_by = ?
                     WHERE id = ?"
                );
                $stmt->execute([$newStatus, $paymentStatus, $amountPaid, $remarks, $releasedAt, $releasedBy, $transId]);

                logAction($pdo, $_SESSION['user_id'], 'UPDATE_WALKIN_STATUS',
                    "Updated walk-in {$existing['reference_number']} status to $newStatus");

                $_SESSION['flash_success'] = "Transaction {$existing['reference_number']} updated successfully.";
                header('Location: ' . appUrl('/staff/walkin-transactions.php'));
                exit;
            }
        }
    }

    // C. QUICK CERTIFICATE RELEASE ACTION
    elseif ($action === 'quick_release') {
        $transId = (int)($_POST['transaction_id'] ?? 0);
        if ($transId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM walkin_transactions WHERE id = ?");
            $stmt->execute([$transId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare(
                    "UPDATE walkin_transactions
                     SET status = 'Released', released_at = NOW(), released_by = ?
                     WHERE id = ?"
                );
                $stmt->execute([$staffId, $transId]);

                logAction($pdo, $_SESSION['user_id'], 'RELEASE_WALKIN_CERTIFICATE',
                    "Released certificate for walk-in {$existing['reference_number']}");

                $_SESSION['flash_success'] = "Certificate release recorded for {$existing['reference_number']}.";
                header('Location: ' . appUrl('/staff/walkin-transactions.php'));
                exit;
            }
        }
    }
}

// ---------------------------------------------------------------------
// 2. STATISTICS (Derived 100% from actual database records)
// ---------------------------------------------------------------------
$statTotal      = (int)$pdo->query("SELECT COUNT(*) FROM walkin_transactions")->fetchColumn();
$statPending    = (int)$pdo->query("SELECT COUNT(*) FROM walkin_transactions WHERE status = 'Pending'")->fetchColumn();
$statProcessing = (int)$pdo->query("SELECT COUNT(*) FROM walkin_transactions WHERE status = 'Processing'")->fetchColumn();
$statCompleted  = (int)$pdo->query("SELECT COUNT(*) FROM walkin_transactions WHERE status = 'Completed'")->fetchColumn();
$statReleased   = (int)$pdo->query("SELECT COUNT(*) FROM walkin_transactions WHERE status = 'Released'")->fetchColumn();

// ---------------------------------------------------------------------
// 3. SEARCH & FILTERS
// ---------------------------------------------------------------------
$search   = trim($_GET['search'] ?? '');
$certType = trim($_GET['certificate_type'] ?? '');
$status   = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

$sql = "SELECT w.*, 
               s.first_name AS staff_fname, s.last_name AS staff_lname,
               r.first_name AS reg_fname, r.last_name AS reg_lname
        FROM walkin_transactions w
        LEFT JOIN staff s ON s.id = w.assigned_staff_id
        LEFT JOIN residents r ON r.id = w.resident_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (w.reference_number LIKE ? OR w.resident_name LIKE ? OR w.contact_number LIKE ? OR w.purpose LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($certType !== '') {
    $sql .= " AND w.certificate_type = ?";
    $params[] = $certType;
}
if ($status !== '') {
    $sql .= " AND w.status = ?";
    $params[] = $status;
}
if ($dateFrom !== '') {
    $sql .= " AND w.request_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $sql .= " AND w.request_date <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY w.id DESC LIMIT 250";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// ---------------------------------------------------------------------
// 4. PRELOAD REGISTERED RESIDENTS & STAFF FOR FORM
// ---------------------------------------------------------------------
$residentsList = $pdo->query(
    "SELECT r.id, r.first_name, r.middle_name, r.last_name, r.contact_number, r.address, u.email
     FROM residents r
     JOIN users u ON u.id = r.user_id
     ORDER BY r.last_name ASC, r.first_name ASC"
)->fetchAll();

$staffList = $pdo->query(
    "SELECT s.id, s.first_name, s.last_name, s.position
     FROM staff s
     JOIN users u ON u.id = s.user_id
     WHERE u.status = 'active'
     ORDER BY s.first_name ASC"
)->fetchAll();

$pageTitle = "Walk-in Transactions | Barangay Pahina Central";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="walkin-management-header">
    <div class="walkin-title-block">
        <span class="walkin-tag">Counter Operations</span>
        <h1 class="walkin-page-title">Walk-in Transaction Management</h1>
        <p class="walkin-page-sub">Record, track, and issue manual certificate requests submitted personally by residents at the barangay hall.</p>
    </div>
    <div class="walkin-actions-top">
        <button type="button" class="btn btn-primary" id="btnToggleAddForm">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Add Walk-in Transaction</span>
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     SUMMARY STATISTICS CARDS (Real Database Records)
     ═══════════════════════════════════════════════════════════════════ -->
<div class="walkin-stats-grid">
    <div class="walkin-stat-card card-total">
        <div class="stat-icon-badge">📋</div>
        <div class="stat-meta">
            <span class="stat-number"><?= $statTotal ?></span>
            <span class="stat-label">Total Walk-ins</span>
        </div>
    </div>
    <div class="walkin-stat-card card-pending">
        <div class="stat-icon-badge">⏳</div>
        <div class="stat-meta">
            <span class="stat-number"><?= $statPending ?></span>
            <span class="stat-label">Pending</span>
        </div>
    </div>
    <div class="walkin-stat-card card-processing">
        <div class="stat-icon-badge">⚙️</div>
        <div class="stat-meta">
            <span class="stat-number"><?= $statProcessing ?></span>
            <span class="stat-label">Processing</span>
        </div>
    </div>
    <div class="walkin-stat-card card-completed">
        <div class="stat-icon-badge">✅</div>
        <div class="stat-meta">
            <span class="stat-number"><?= $statCompleted ?></span>
            <span class="stat-label">Completed</span>
        </div>
    </div>
    <div class="walkin-stat-card card-released">
        <div class="stat-icon-badge">🎉</div>
        <div class="stat-meta">
            <span class="stat-number"><?= $statReleased ?></span>
            <span class="stat-label">Released</span>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     NEW WALK-IN TRANSACTION FORM (Collapsible / Modal Panel)
     ═══════════════════════════════════════════════════════════════════ -->
<div class="card walkin-form-card" id="addWalkinCard" style="<?= !empty($errors) ? 'display:block;' : 'display:none;' ?>">
    <div class="form-card-header">
        <div class="header-icon-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
        </div>
        <div>
            <h2 class="form-card-title">Record New Walk-in Transaction</h2>
            <p class="form-card-sub">Manually log a certificate request for an in-person resident.</p>
        </div>
        <button type="button" class="btn-close-form" id="btnCloseAddForm" title="Close form">&times;</button>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= h($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= h(appUrl('/staff/walkin-transactions.php')) ?>" id="walkinForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">

        <!-- Section 1: Resident Identification -->
        <div class="form-section-title">
            <span>1. Resident Information</span>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="resident_selector">Select Existing Resident (Optional)</label>
                <select id="resident_selector" class="form-control">
                    <option value="">-- Non-Registered / Walk-in Only (Enter details below) --</option>
                    <?php foreach ($residentsList as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"
                                data-name="<?= h($r['first_name'] . ' ' . ($r['middle_name'] ? $r['middle_name'] . ' ' : '') . $r['last_name']) ?>"
                                data-contact="<?= h($r['contact_number']) ?>"
                                data-email="<?= h($r['email']) ?>"
                                data-address="<?= h($r['address']) ?>">
                            <?= h($r['last_name'] . ', ' . $r['first_name'] . ' (' . $r['contact_number'] . ' - ' . $r['address'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Selecting an existing resident connects this transaction to their account history.</span>
            </div>
            <input type="hidden" name="resident_id" id="hidden_resident_id" value="">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="resident_name">Resident Full Name <span class="required">*</span></label>
                <input type="text" id="resident_name" name="resident_name" required placeholder="e.g. Maria Santos" class="form-control">
            </div>
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" placeholder="09XXXXXXXXX" class="form-control">
            </div>
            <div class="form-group">
                <label for="email">Email Address (Optional)</label>
                <input type="email" id="email" name="email" placeholder="Optional email" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="address">Residential Address</label>
            <input type="text" id="address" name="address" placeholder="Sitio / Street, Barangay Pahina Central" class="form-control">
        </div>

        <!-- Section 2: Certificate & Purpose -->
        <div class="form-section-title">
            <span>2. Certificate Details</span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="certificate_type">Certificate Type <span class="required">*</span></label>
                <select id="certificate_type" name="certificate_type" class="form-control" required>
                    <option value="Barangay Certificate">Barangay Certificate</option>
                    <option value="Barangay Clearance">Barangay Clearance</option>
                    <option value="Certificate of Residency">Certificate of Residency</option>
                    <option value="Certificate of Indigency">Certificate of Indigency</option>
                </select>
            </div>
            <div class="form-group" style="flex: 2;">
                <label for="purpose">Purpose of Request <span class="required">*</span></label>
                <input type="text" id="purpose" name="purpose" required placeholder="e.g. Local Employment, School Requirement, Medical Assistance" class="form-control">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="request_date">Date Requested <span class="required">*</span></label>
                <input type="date" id="request_date" name="request_date" value="<?= date('Y-m-d') ?>" required class="form-control">
            </div>
            <div class="form-group">
                <label for="assigned_staff_id">Assigned Staff</label>
                <select id="assigned_staff_id" name="assigned_staff_id" class="form-control">
                    <?php foreach ($staffList as $st): ?>
                        <option value="<?= (int)$st['id'] ?>" <?= $st['id'] == $staffId ? 'selected' : '' ?>>
                            <?= h($st['first_name'] . ' ' . $st['last_name'] . ' (' . ($st['position'] ?: 'Staff') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Initial Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="Pending">Pending</option>
                    <option value="Processing" selected>Processing</option>
                    <option value="Completed">Completed</option>
                    <option value="Released">Released</option>
                </select>
            </div>
        </div>

        <!-- Section 3: Payment & Remarks -->
        <div class="form-section-title">
            <span>3. Payment & Additional Remarks</span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="payment_status">Payment Status</label>
                <select id="payment_status" name="payment_status" class="form-control">
                    <option value="Free" selected>Free (No Fee)</option>
                    <option value="Paid">Paid</option>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Waived">Waived</option>
                </select>
            </div>
            <div class="form-group">
                <label for="amount_paid">Amount Paid (₱)</label>
                <input type="number" step="0.01" min="0" id="amount_paid" name="amount_paid" value="0.00" class="form-control">
            </div>
            <div class="form-group">
                <label for="receipt_number">Official Receipt / Ref No. (Optional)</label>
                <input type="text" id="receipt_number" name="receipt_number" placeholder="e.g. OR-98214" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="remarks">Staff Remarks / Notes</label>
            <textarea id="remarks" name="remarks" rows="2" placeholder="Any specific requirements presented, remarks, or notes..." class="form-control"></textarea>
        </div>

        <div class="form-actions-bar">
            <button type="submit" class="btn btn-primary" id="btnSaveWalkin">Save Walk-in Transaction</button>
            <button type="button" class="btn btn-secondary" id="btnCancelAdd">Cancel</button>
        </div>
    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     SEARCH & FILTER BAR
     ═══════════════════════════════════════════════════════════════════ -->
<div class="card walkin-filter-card">
    <form method="get" action="<?= h(appUrl('/staff/walkin-transactions.php')) ?>" class="filter-form-grid">
        <div class="filter-field">
            <label for="filter_search">Search Records</label>
            <input type="text" id="filter_search" name="search" value="<?= h($search) ?>" placeholder="Ref #, resident name, contact, purpose..." class="form-control">
        </div>
        <div class="filter-field">
            <label for="filter_cert">Certificate Type</label>
            <select id="filter_cert" name="certificate_type" class="form-control">
                <option value="">All Certificate Types</option>
                <option value="Barangay Certificate" <?= $certType === 'Barangay Certificate' ? 'selected' : '' ?>>Barangay Certificate</option>
                <option value="Barangay Clearance" <?= $certType === 'Barangay Clearance' ? 'selected' : '' ?>>Barangay Clearance</option>
                <option value="Certificate of Residency" <?= $certType === 'Certificate of Residency' ? 'selected' : '' ?>>Certificate of Residency</option>
                <option value="Certificate of Indigency" <?= $certType === 'Certificate of Indigency' ? 'selected' : '' ?>>Certificate of Indigency</option>
            </select>
        </div>
        <div class="filter-field">
            <label for="filter_status">Status</label>
            <select id="filter_status" name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Processing" <?= $status === 'Processing' ? 'selected' : '' ?>>Processing</option>
                <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Released" <?= $status === 'Released' ? 'selected' : '' ?>>Released</option>
                <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="filter-field">
            <label for="filter_date_from">Date From</label>
            <input type="date" id="filter_date_from" name="date_from" value="<?= h($dateFrom) ?>" class="form-control">
        </div>
        <div class="filter-field">
            <label for="filter_date_to">Date To</label>
            <input type="date" id="filter_date_to" name="date_to" value="<?= h($dateTo) ?>" class="form-control">
        </div>
        <div class="filter-field filter-buttons">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?= h(appUrl('/staff/walkin-transactions.php')) ?>" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     TRANSACTIONS DATA TABLE
     ═══════════════════════════════════════════════════════════════════ -->
<div class="card walkin-table-card">
    <div class="table-meta-bar">
        <h3>Transactions (<?= count($transactions) ?> <?= count($transactions) === 1 ? 'record' : 'records' ?> found)</h3>
    </div>

    <div class="table-wrapper">
        <table class="walkin-data-table">
            <thead>
                <tr>
                    <th>Reference #</th>
                    <th>Resident</th>
                    <th>Certificate</th>
                    <th>Date Requested</th>
                    <th>Assigned Staff</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="8" class="empty-table-cell">
                        <div class="empty-state-wrap">
                            <span class="empty-icon">📭</span>
                            <p>No walk-in transactions found matching your criteria.</p>
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('btnToggleAddForm').click();">Record New Walk-in</button>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td>
                            <strong class="trans-ref-code"><?= h($t['reference_number']) ?></strong>
                        </td>
                        <td>
                            <div class="resident-cell">
                                <span class="resident-cell-name"><?= h($t['resident_name']) ?></span>
                                <?php if (!empty($t['resident_id'])): ?>
                                    <span class="resident-tag-badge registered" title="Linked to registered resident account">Registered</span>
                                <?php else: ?>
                                    <span class="resident-tag-badge walkin" title="Unregistered walk-in citizen">Walk-in</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($t['contact_number'])): ?>
                                <small class="resident-cell-sub"><?= h($t['contact_number']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cert-name-label"><?= h($t['certificate_type']) ?></span>
                            <small class="cert-purpose-label">For: <?= h($t['purpose']) ?></small>
                        </td>
                        <td>
                            <?= formatDate($t['request_date']) ?>
                        </td>
                        <td>
                            <?= h(($t['staff_fname'] ? $t['staff_fname'] . ' ' . $t['staff_lname'] : 'Staff')) ?>
                        </td>
                        <td>
                            <?= paymentStatusBadge($t['payment_status']) ?>
                            <?php if ($t['amount_paid'] > 0): ?>
                                <small class="amount-sub">₱<?= number_format($t['amount_paid'], 2) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= walkinStatusBadge($t['status']) ?>
                            <?php if ($t['status'] === 'Released' && !empty($t['released_at'])): ?>
                                <small class="release-date-sub"><?= formatDate($t['released_at']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btn-group">
                                <!-- Print Receipt Button -->
                                <a href="<?= h(appUrl('/staff/walkin-receipt.php?id=' . $t['id'])) ?>" target="_blank" class="btn btn-sm btn-secondary action-icon-btn" title="Print Transaction Receipt">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                </a>

                                <!-- Update Status Button -->
                                <button type="button" class="btn btn-sm btn-secondary btn-update-status" 
                                        data-id="<?= (int)$t['id'] ?>"
                                        data-ref="<?= h($t['reference_number']) ?>"
                                        data-status="<?= h($t['status']) ?>"
                                        data-payment="<?= h($t['payment_status']) ?>"
                                        data-amount="<?= (float)$t['amount_paid'] ?>"
                                        data-remarks="<?= h($t['remarks']) ?>"
                                        title="Update Status & Payment">
                                    Status
                                </button>

                                <!-- Quick Release Button -->
                                <?php if ($t['status'] !== 'Released' && $t['status'] !== 'Cancelled'): ?>
                                    <form method="post" action="<?= h(appUrl('/staff/walkin-transactions.php')) ?>" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="quick_release">
                                        <input type="hidden" name="transaction_id" value="<?= (int)$t['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" data-confirm="Confirm release of certificate for <?= h($t['reference_number']) ?>?" title="Mark certificate as released">
                                            Release
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     STATUS UPDATE MODAL
     ═══════════════════════════════════════════════════════════════════ -->
<div class="bp-modal-backdrop" id="updateStatusModal" aria-hidden="true">
    <div class="bp-modal-dialog">
        <div class="bp-modal-header">
            <div class="bp-modal-icon-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div>
                <h3 class="bp-modal-title">Update Transaction Status</h3>
                <p class="bp-modal-subtitle" id="modalRefTitle">Reference: -</p>
            </div>
            <button type="button" class="bp-modal-close" id="btnCloseUpdateModal">&times;</button>
        </div>

        <form method="post" action="<?= h(appUrl('/staff/walkin-transactions.php')) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="transaction_id" id="modal_trans_id" value="">

            <div class="bp-modal-body">
                <div class="form-group" style="margin-bottom:14px;">
                    <label for="modal_new_status">Transaction Status</label>
                    <select id="modal_new_status" name="new_status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Completed">Completed</option>
                        <option value="Released">Released (Issues Certificate)</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:14px;">
                    <div class="form-group">
                        <label for="modal_payment_status">Payment Status</label>
                        <select id="modal_payment_status" name="payment_status" class="form-control">
                            <option value="Free">Free</option>
                            <option value="Paid">Paid</option>
                            <option value="Unpaid">Unpaid</option>
                            <option value="Waived">Waived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_amount_paid">Amount Paid (₱)</label>
                        <input type="number" step="0.01" min="0" id="modal_amount_paid" name="amount_paid" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="modal_remarks">Remarks / Notes</label>
                    <textarea id="modal_remarks" name="remarks" rows="2" class="form-control"></textarea>
                </div>
            </div>

            <div class="bp-modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Toggle Add Walk-in Form
    var btnToggle = document.getElementById('btnToggleAddForm');
    var btnClose  = document.getElementById('btnCloseAddForm');
    var btnCancel = document.getElementById('btnCancelAdd');
    var formCard  = document.getElementById('addWalkinCard');

    function toggleForm() {
        if (formCard.style.display === 'none') {
            formCard.style.display = 'block';
            formCard.scrollIntoView({ behavior: 'smooth' });
        } else {
            formCard.style.display = 'none';
        }
    }

    if (btnToggle) btnToggle.addEventListener('click', toggleForm);
    if (btnClose)  btnClose.addEventListener('click', function() { formCard.style.display = 'none'; });
    if (btnCancel) btnCancel.addEventListener('click', function() { formCard.style.display = 'none'; });

    // 2. Existing Resident Selector Auto-fill
    var residentSelect = document.getElementById('resident_selector');
    var hiddenResId    = document.getElementById('hidden_resident_id');
    var inputName      = document.getElementById('resident_name');
    var inputContact   = document.getElementById('contact_number');
    var inputEmail     = document.getElementById('email');
    var inputAddress   = document.getElementById('address');

    if (residentSelect) {
        residentSelect.addEventListener('change', function() {
            var selectedOpt = residentSelect.options[residentSelect.selectedIndex];
            var val = selectedOpt.value;

            if (val) {
                hiddenResId.value  = val;
                inputName.value    = selectedOpt.getAttribute('data-name') || '';
                inputContact.value = selectedOpt.getAttribute('data-contact') || '';
                inputEmail.value   = selectedOpt.getAttribute('data-email') || '';
                inputAddress.value = selectedOpt.getAttribute('data-address') || '';
            } else {
                hiddenResId.value  = '';
            }
        });
    }

    // 3. Status Update Modal
    var modal         = document.getElementById('updateStatusModal');
    var modalCloseBtn = document.getElementById('btnCloseUpdateModal');
    var modalCancel   = document.getElementById('btnCancelModal');
    var modalRefTitle = document.getElementById('modalRefTitle');
    var modalTransId  = document.getElementById('modal_trans_id');
    var modalStatus   = document.getElementById('modal_new_status');
    var modalPayment  = document.getElementById('modal_payment_status');
    var modalAmount   = document.getElementById('modal_amount_paid');
    var modalRemarks  = document.getElementById('modal_remarks');

    function closeModal() {
        modal.classList.remove('is-active');
        modal.setAttribute('aria-hidden', 'true');
    }

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);

    document.querySelectorAll('.btn-update-status').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id      = btn.getAttribute('data-id');
            var ref     = btn.getAttribute('data-ref');
            var st      = btn.getAttribute('data-status');
            var pay     = btn.getAttribute('data-payment');
            var amt     = btn.getAttribute('data-amount');
            var rem     = btn.getAttribute('data-remarks');

            modalTransId.value         = id;
            modalRefTitle.textContent  = 'Reference: ' + ref;
            modalStatus.value          = st;
            modalPayment.value         = pay;
            modalAmount.value          = amt;
            modalRemarks.value         = rem;

            modal.classList.add('is-active');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    // Close on click backdrop
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
