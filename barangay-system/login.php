<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * login.php — Public landing page & unified authentication portal.
 *
 * This file serves two purposes on a single page:
 *   1. Login — validates credentials, starts session, redirects to dashboard.
 *   2. Register — creates a resident account (user + resident rows in a transaction).
 *
 * Mode switching:
 *   • Login mode (default) vs. Registration mode
 *   • Switched smoothly via client-side JavaScript (no page reload)
 *   • Left branding and right form update seamlessly in sync
 *   • Full server-side validation, CSRF verification, password hashing, and role routing preserved
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

/* ── Already logged in → go straight to dashboard ────────────────── */
if (isLoggedIn()) {
    header('Location: ' . appUrl('/' . currentRole() . '/dashboard.php'));
    exit;
}

/* ── Panel & state flags ──────────────────────────────────────────── */
// Show register panel if explicitly requested via URL or if registration failed
$showRegister = ($_GET['panel'] ?? '') === 'register';

// True when redirected back after a successful registration.
$registered   = ($_GET['registered'] ?? '') === '1';

/* ── Login form state ─────────────────────────────────────────────── */
$loginErrors = [];
$emailValue  = '';

/* ── Register form state ──────────────────────────────────────────── */
$registerErrors = [];
$old = [
    'first_name'     => '',
    'middle_name'    => '',
    'last_name'      => '',
    'date_of_birth'  => '',
    'address'        => '',
    'contact_number' => '',
    'email'          => '',
    'username'       => '',
];

/* ── Handle POST submissions ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $form = $_POST['form'] ?? 'login';

    /* ─────────────────────────────────────────────────────────────────
       REGISTRATION
    ───────────────────────────────────────────────────────────────── */
    if ($form === 'register') {
        $showRegister = true; // stay on register panel if errors occur

        // Collect and sanitise submitted values (repopulate on error)
        foreach ($old as $key => $_) {
            $old[$key] = trim($_POST[$key] ?? '');
        }
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Server-side validation
        if ($old['first_name'] === '')  $registerErrors[] = 'First name is required.';
        if ($old['last_name']  === '')  $registerErrors[] = 'Last name is required.';
        if ($old['date_of_birth'] === '') {
            $registerErrors[] = 'Date of birth is required.';
        } elseif (!strtotime($old['date_of_birth'])) {
            $registerErrors[] = 'Please provide a valid date of birth.';
        } elseif (strtotime($old['date_of_birth']) > time()) {
            $registerErrors[] = 'Date of birth cannot be in the future.';
        }
        if ($old['address'] === '') $registerErrors[] = 'Residential address is required.';
        $cleanContact = preg_replace('/[\s\-()]/', '', $old['contact_number']);
        if ($old['contact_number'] === '' || !preg_match('/^(09\d{9}|\+639\d{9}|[0-9+]{7,20})$/', $cleanContact)) {
            $registerErrors[] = 'Please provide a valid Philippine contact number (e.g., 09XXXXXXXXX).';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $registerErrors[] = 'Please provide a valid email address.';
        }
        if ($old['username'] === '' || strlen($old['username']) < 4) {
            $registerErrors[] = 'Username must be at least 4 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9._]+$/', $old['username'])) {
            $registerErrors[] = 'Username can only contain letters, numbers, dots, and underscores.';
        }
        if (strlen($password) < 8) {
            $registerErrors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $registerErrors[] = 'Password must include uppercase, lowercase, and a number.';
        }
        if ($password !== $confirm) {
            $registerErrors[] = 'Passwords do not match.';
        }

        // Duplicate email / username check with specific feedback
        if (!$registerErrors) {
            $stmt = $pdo->prepare('SELECT id, email, username FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->execute([$old['email'], $old['username']]);
            $existingUser = $stmt->fetch();
            if ($existingUser) {
                if (strcasecmp($existingUser['email'], $old['email']) === 0) {
                    $registerErrors[] = 'An account with this email address already exists.';
                }
                if (strcasecmp($existingUser['username'], $old['username']) === 0) {
                    $registerErrors[] = 'This username is already taken. Please choose another.';
                }
            }
        }

        // Insert user + resident in a transaction
        if (!$registerErrors) {
            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, email, password, role, status)
                     VALUES (?, ?, ?, "resident", "active")'
                );
                $stmt->execute([$old['username'], $old['email'], $hash]);
                $userId = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    'INSERT INTO residents
                         (user_id, first_name, middle_name, last_name, date_of_birth, address, contact_number)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $userId,
                    $old['first_name'],
                    ($_POST['middle_name'] ?? '') ?: null,
                    $old['last_name'],
                    $old['date_of_birth'],
                    $old['address'],
                    $old['contact_number'],
                ]);

                $pdo->commit();

                // Redirect to login panel with success flag
                header('Location: ' . appUrl('/login.php?registered=1'));
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Registration failed: ' . $e->getMessage());
                $registerErrors[] = 'Something went wrong while creating your account. Please try again.';
            }
        }

    /* ─────────────────────────────────────────────────────────────────
       LOGIN
    ───────────────────────────────────────────────────────────────── */
    } else {
        $emailValue = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        if ($emailValue === '' && $password === '') {
            $loginErrors[] = 'Please enter your email/username and password.';
        } elseif ($emailValue === '') {
            $loginErrors[] = 'Please enter your email address or username.';
        } elseif ($password === '') {
            $loginErrors[] = 'Please enter your password.';
        } else {
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
            $attemptStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE ip_hash = ? AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)'
            );
            $attemptStmt->execute([$ipHash]);

            if ((int)$attemptStmt->fetchColumn() >= 5) {
                $loginErrors[] = 'Too many sign-in attempts. Please wait 15 minutes before trying again.';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
                $stmt->execute([$emailValue, $emailValue]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($password, $user['password'])) {
                    $pdo->prepare('INSERT INTO login_attempts (ip_hash) VALUES (?)')->execute([$ipHash]);
                    $loginErrors[] = 'Incorrect email/username or password. Please try again.';
                } elseif ($user['status'] !== 'active') {
                    $loginErrors[] = 'Your account has been deactivated. Please contact the barangay hall for assistance.';
                } else {
                    $pdo->prepare('DELETE FROM login_attempts WHERE ip_hash = ?')->execute([$ipHash]);

                    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                            ->execute([$newHash, $user['id']]);
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role']    = $user['role'];

                    if ($user['role'] === 'resident') {
                        $resident = getResidentByUserId($pdo, $user['id']);
                        $_SESSION['display_name'] = $resident
                            ? $resident['first_name'] . ' ' . $resident['last_name']
                            : $user['username'];
                    } else {
                        $staff = getStaffByUserId($pdo, $user['id']);
                        $_SESSION['display_name'] = $staff
                            ? $staff['first_name'] . ' ' . $staff['last_name']
                            : $user['username'];
                    }

                    logAction($pdo, $user['id'], 'LOGIN', 'User logged in.');

                    header('Location: ' . appUrl('/' . $user['role'] . '/dashboard.php'));
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Pahina Central – Online Reservation System</title>
    <meta name="description" content="Barangay Pahina Central – Online Reservation System — fast, easy, and convenient online certificate requests and appointment reservations.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(appUrl('/css/style.css?v=' . filemtime(__DIR__ . '/css/style.css'))) ?>">
    <link rel="stylesheet" href="<?= h(appUrl('/css/login.css?v=' . filemtime(__DIR__ . '/css/login.css'))) ?>">
</head>
<body class="login-body">

<?php
// Automatic background image detection with file modification timestamp (cache busting)
$bgCandidates = ['barangay_hall.jpg', 'barangay_hall.png', 'barangay_hall.jpeg', 'barangay_hall.webp'];
$bgFileName = 'barangay_hall.jpg';
foreach ($bgCandidates as $cand) {
    if (file_exists(__DIR__ . '/assets/images/' . $cand)) {
        $bgFileName = $cand;
        break;
    }
}
$bgFilePath = __DIR__ . '/assets/images/' . $bgFileName;
$bgVersion = file_exists($bgFilePath) ? filemtime($bgFilePath) : time();
$bgImageUrl = appUrl('/assets/images/' . $bgFileName . '?v=' . $bgVersion);

// Cache-busting versions for logos
$logoPahinaPath = __DIR__ . '/assets/images/barangay_pahina_central_logo.png';
$logoPahinaVersion = file_exists($logoPahinaPath) ? filemtime($logoPahinaPath) : time();

$logoCityPath = __DIR__ . '/assets/images/city_logo.png';
$logoCityVersion = file_exists($logoCityPath) ? filemtime($logoCityPath) : time();
?>

<!-- Main Full-Screen Layout Wrapper -->
<div class="auth-page-wrapper">

    <!-- Full-Screen Main Section (Covered by Barangay Hall Background with dynamic cache buster) -->
    <main class="bp-main-content-section" id="mainContentSection" style="background-image: url('<?= h($bgImageUrl) ?>');">

        <!-- Decorative Corner Swooshes (Matching the Reference Mockup) -->
        <div class="bp-corner-swoosh bp-swoosh-top-left" aria-hidden="true">
            <svg viewBox="0 0 540 260" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <!-- Golden Yellow Wave -->
                <path d="M-60 -30 C160 5, 290 85, 410 15 C470 -20, 520 25, 560 60 L560 -60 L-60 -60 Z" fill="#FFB703"/>
                <!-- Navy / Royal Blue Ribbon Wave -->
                <path d="M-60 18 C150 48, 275 125, 430 35 C480 -12, 530 15, 560 45 L560 -25 L-60 -25 Z" fill="#0B3D75" opacity="0.92"/>
            </svg>
        </div>

        <div class="bp-corner-swoosh bp-swoosh-bottom-right" aria-hidden="true">
            <svg viewBox="0 0 580 260" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <!-- Golden Yellow Wave -->
                <path d="M-10 260 C130 215, 270 155, 380 215 C460 260, 520 205, 600 150 L600 280 L-10 280 Z" fill="#FFB703"/>
                <!-- Deep Navy Blue Wave -->
                <path d="M30 260 C170 225, 300 180, 420 230 C480 255, 540 215, 600 185 L600 280 L30 280 Z" fill="#082444"/>
            </svg>
        </div>

        <div class="authentication-layout<?= $showRegister ? ' register-active' : '' ?>" id="authContainer">

        <!-- ═══════════════════════════════════════════════════════════════
             LEFT SECTION — Branding & Introduction (Directly over Background)
             ═══════════════════════════════════════════════════════════════ -->
        <div class="branding-hero-section" id="brandingHero">

            <!-- Horizontally Aligned Official Seals (Enlarged & Prominent) -->
            <div class="bp-official-logos-row">
                <div class="bp-logo-frame" title="Barangay Pahina Central Official Seal">
                    <img
                        src="<?= h(appUrl('/assets/images/barangay_pahina_central_logo.png?v=' . $logoPahinaVersion)) ?>"
                        alt="Barangay Pahina Central Seal"
                        class="bp-seal-img"
                    >
                </div>

                <div class="bp-seals-separator" aria-hidden="true"></div>

                <div class="bp-logo-frame" title="Official Seal of the City of Cebu">
                    <img
                        src="<?= h(appUrl('/assets/images/city_logo.png?v=' . $logoCityVersion)) ?>"
                        alt="City of Cebu Official Seal"
                        class="bp-seal-img"
                    >
                </div>
            </div>

            <!-- DYNAMIC CONTENT: LOGIN MODE (Default) -->
            <div class="bp-intro-block bp-login-intro" id="leftLoginContent">
                <h1 class="bp-hero-title">
                    <span class="bp-title-main">Barangay Pahina Central</span>
                    <span class="bp-title-capsule">Online Reservation System</span>
                </h1>

                <div class="bp-tagline-wrap">
                    <span class="bp-tagline-text">Fast. Easy. Convenient.</span>
                    <div class="bp-tagline-underline" aria-hidden="true"></div>
                </div>

                <p class="bp-hero-summary">
                    Request certificates, book appointments,<br>
                    and track your transactions online.
                </p>
            </div>

            <!-- DYNAMIC CONTENT: REGISTRATION MODE -->
            <div class="bp-intro-block bp-register-intro" id="leftRegisterContent">
                <h1 class="bp-hero-title">
                    <span class="bp-title-main">Barangay Pahina Central</span>
                    <span class="bp-title-capsule">Online Resident Portal</span>
                </h1>

                <div class="bp-tagline-wrap">
                    <span class="bp-tagline-text">A Connected Barangay, A Stronger Community</span>
                    <div class="bp-tagline-underline" aria-hidden="true"></div>
                </div>

                <p class="bp-hero-summary">
                    Join Barangay Pahina's Online Services and enjoy convenient<br>
                    access to barangay transactions, appointments, and records.
                </p>
            </div>

        </div><!-- /.branding-hero-section -->


        <!-- ═══════════════════════════════════════════════════════════════
             RIGHT SECTION — Pure White Elevated Authentication Card
             ═══════════════════════════════════════════════════════════════ -->
        <div class="auth-card-section">

            <div class="auth-floating-card">

                <!-- Circular User Avatar Icon -->
                <div class="auth-avatar-circle" aria-hidden="true">
                    <svg class="avatar-icon-login" xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                    <svg class="avatar-icon-register" xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                        <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>

                <!-- ─────────────────────────────────────────────────────────
                     VIEW 1: LOGIN FORM (Default)
                     ───────────────────────────────────────────────────────── -->
                <div class="auth-form-view auth-login-view" id="loginView">

                    <h2 class="auth-view-heading">Login to Your Account</h2>
                    <p class="auth-view-subtext">Access your account to use our services</p>

                    <!-- Flash Registration Success -->
                    <?php if ($registered): ?>
                        <div class="auth-msg-banner auth-msg-success" role="alert">
                            <span class="auth-msg-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <div>Registration successful! You may now sign in with your account.</div>
                        </div>
                    <?php endif; ?>

                    <!-- Login Errors -->
                    <?php foreach ($loginErrors as $error): ?>
                        <div class="auth-msg-banner auth-msg-error" role="alert">
                            <span class="auth-msg-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </span>
                            <div><?= h($error) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Login Form -->
                    <form method="post" action="<?= h(appUrl('/login.php')) ?>" id="loginForm" class="auth-native-form" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="form" value="login">

                        <!-- Email or Username Field -->
                        <div class="auth-field-row">
                            <div class="auth-field-input-box">
                                <span class="auth-field-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                </span>
                                <input
                                    type="text"
                                    id="email"
                                    name="email"
                                    class="auth-input-element"
                                    value="<?= h($emailValue) ?>"
                                    placeholder="Email Address"
                                    required
                                    autocomplete="username"
                                    aria-label="Email Address or Username"
                                >
                            </div>
                        </div>

                        <!-- Password Field with Show/Hide -->
                        <div class="auth-field-row">
                            <div class="auth-field-input-box">
                                <span class="auth-field-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="auth-input-element"
                                    placeholder="Password"
                                    required
                                    autocomplete="current-password"
                                    aria-label="Password"
                                >
                                <button
                                    type="button"
                                    class="auth-password-toggle-btn"
                                    id="pwToggle"
                                    aria-label="Toggle password visibility"
                                    title="Show / hide password"
                                >
                                    <svg id="pwIconShow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg id="pwIconHide" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Dark Navy-Blue Login Button -->
                        <button type="submit" class="auth-navy-button" id="loginSubmitBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            <span>Login</span>
                        </button>

                        <!-- OR Separator (Matching the Reference Mockup) -->
                        <div class="auth-or-divider">
                            <div class="auth-divider-line"></div>
                            <span class="auth-divider-label">or</span>
                            <div class="auth-divider-line"></div>
                        </div>

                        <!-- Full-Width Prominent "Create an Account" Button -->
                        <button type="button" class="auth-register-button" id="btnShowRegister">
                            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <span>Create an Account</span>
                        </button>

                        <!-- Centered / Uncluttered Forgot Password Action -->
                        <div class="auth-forgot-pw-wrap">
                            <button type="button" class="auth-forgot-pw-btn" id="btnForgotPassword">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                                <span>Forgot Password?</span>
                            </button>
                        </div>
                    </form>

                    <!-- Collapsible Demo Accounts (Discreet for Testing) -->
                    <details class="lf-demo-box">
                        <summary class="lf-demo-summary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>Demo Accounts</span>
                        </summary>
                        <div class="lf-demo-content">
                            <p class="lf-demo-note">Default Password: <code>Password123!</code></p>
                            <table class="lf-demo-table">
                                <thead>
                                    <tr><th>Role</th><th>Email / Username</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="lf-role-badge lf-role-admin">Admin</span></td><td>admin@barangay.gov.ph</td></tr>
                                    <tr><td><span class="lf-role-badge lf-role-staff">Staff</span></td><td>staff1@barangay.gov.ph</td></tr>
                                    <tr><td><span class="lf-role-badge lf-role-resident">Resident</span></td><td>juan.delacruz@example.com</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </details>

                </div><!-- /.auth-login-view -->


                <!-- ─────────────────────────────────────────────────────────
                     VIEW 2: REGISTER FORM
                     ───────────────────────────────────────────────────────── -->
                <div class="auth-form-view auth-register-view" id="registerView">

                    <h2 class="auth-view-heading">Create an Account</h2>
                    <p class="auth-view-subtext">Register to request certificates & book appointments online</p>

                    <!-- Register Errors -->
                    <?php foreach ($registerErrors as $error): ?>
                        <div class="auth-msg-banner auth-msg-error" role="alert">
                            <span class="auth-msg-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </span>
                            <div><?= h($error) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Registration Form -->
                    <form method="post" action="<?= h(appUrl('/login.php')) ?>" id="registerForm" class="auth-native-form" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="form" value="register">

                        <div class="reg-inputs-grid">
                            <!-- First Name & Middle Name -->
                            <div class="reg-field-item">
                                <label for="reg_first_name" class="reg-label">First Name <span class="reg-req">*</span></label>
                                <input
                                    type="text"
                                    id="reg_first_name"
                                    name="first_name"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['first_name']) ?>"
                                    placeholder="Juan"
                                    required
                                    autocomplete="given-name"
                                >
                            </div>
                            <div class="reg-field-item">
                                <label for="reg_middle_name" class="reg-label">Middle Name <span class="reg-opt">(opt)</span></label>
                                <input
                                    type="text"
                                    id="reg_middle_name"
                                    name="middle_name"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['middle_name']) ?>"
                                    placeholder="Optional"
                                    autocomplete="additional-name"
                                >
                            </div>

                            <!-- Last Name & Date of Birth -->
                            <div class="reg-field-item">
                                <label for="reg_last_name" class="reg-label">Last Name <span class="reg-req">*</span></label>
                                <input
                                    type="text"
                                    id="reg_last_name"
                                    name="last_name"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['last_name']) ?>"
                                    placeholder="Dela Cruz"
                                    required
                                    autocomplete="family-name"
                                >
                            </div>
                            <div class="reg-field-item">
                                <label for="reg_date_of_birth" class="reg-label">Date of Birth <span class="reg-req">*</span></label>
                                <input
                                    type="date"
                                    id="reg_date_of_birth"
                                    name="date_of_birth"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['date_of_birth']) ?>"
                                    required
                                >
                            </div>

                            <!-- Contact Number & Email -->
                            <div class="reg-field-item">
                                <label for="reg_contact_number" class="reg-label">Contact Number <span class="reg-req">*</span></label>
                                <input
                                    type="tel"
                                    id="reg_contact_number"
                                    name="contact_number"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['contact_number']) ?>"
                                    placeholder="09XXXXXXXXX"
                                    required
                                    autocomplete="tel"
                                >
                            </div>
                            <div class="reg-field-item">
                                <label for="reg_email" class="reg-label">Email Address <span class="reg-req">*</span></label>
                                <input
                                    type="email"
                                    id="reg_email"
                                    name="email"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['email']) ?>"
                                    placeholder="you@example.com"
                                    required
                                    autocomplete="email"
                                >
                            </div>

                            <!-- Username & Residential Address -->
                            <div class="reg-field-item">
                                <label for="reg_username" class="reg-label">Username <span class="reg-req">*</span></label>
                                <input
                                    type="text"
                                    id="reg_username"
                                    name="username"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['username']) ?>"
                                    placeholder="min. 4 characters"
                                    required
                                    autocomplete="username"
                                >
                            </div>
                            <div class="reg-field-item">
                                <label for="reg_address" class="reg-label">Residential Address <span class="reg-req">*</span></label>
                                <input
                                    type="text"
                                    id="reg_address"
                                    name="address"
                                    class="auth-input-element reg-input"
                                    value="<?= h($old['address']) ?>"
                                    placeholder="Sitio / Street, Brgy Pahina"
                                    required
                                    autocomplete="street-address"
                                >
                            </div>

                            <!-- Password & Confirm Password -->
                            <div class="reg-field-item">
                                <label for="reg_password" class="reg-label">Create Password <span class="reg-req">*</span></label>
                                <div class="reg-pw-box">
                                    <input
                                        type="password"
                                        id="reg_password"
                                        name="password"
                                        class="auth-input-element reg-input"
                                        placeholder="min. 8 characters"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button
                                        type="button"
                                        class="auth-password-toggle-btn"
                                        id="regPwToggle"
                                        aria-label="Toggle password visibility"
                                        title="Show / hide password"
                                    >
                                        <svg id="regPwIconShow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg id="regPwIconHide" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                                <!-- Password Strength Indicator -->
                                <div class="pw-strength-meter" id="pwStrengthMeter" style="display:none;">
                                    <div class="pw-strength-bars">
                                        <div class="pw-bar" id="pwBar1"></div>
                                        <div class="pw-bar" id="pwBar2"></div>
                                        <div class="pw-bar" id="pwBar3"></div>
                                        <div class="pw-bar" id="pwBar4"></div>
                                    </div>
                                    <span class="pw-strength-text" id="pwStrengthText">Weak</span>
                                </div>
                            </div>
                            <div class="reg-field-item">
                                <label for="reg_confirm_password" class="reg-label">Confirm Password <span class="reg-req">*</span></label>
                                <div class="reg-pw-box">
                                    <input
                                        type="password"
                                        id="reg_confirm_password"
                                        name="confirm_password"
                                        class="auth-input-element reg-input"
                                        placeholder="Re-enter password"
                                        required
                                        autocomplete="new-password"
                                    >
                                    <button
                                        type="button"
                                        class="auth-password-toggle-btn"
                                        id="regConfirmPwToggle"
                                        aria-label="Toggle confirm password visibility"
                                        title="Show / hide password"
                                    >
                                        <svg id="regConfirmPwIconShow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg id="regConfirmPwIconHide" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Register Button -->
                        <button type="submit" class="auth-navy-button" id="registerSubmitBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                            <span>Register</span>
                        </button>

                        <!-- Switch back to Login -->
                        <div class="auth-switch-prompt">
                            <span>Already have an account?</span>
                            <button type="button" class="auth-switch-link" id="btnShowLogin">Login here</button>
                        </div>
                    </form>

                </div><!-- /.auth-register-view -->

            </div><!-- /.auth-floating-card -->

        </div><!-- /.auth-card-section -->

    </div><!-- /.authentication-layout -->

    </main><!-- /.bp-main-content-section -->

    <!-- ═══════════════════════════════════════════════════════════════
         FULL-WIDTH NAVY-BLUE FOOTER (Matching Reference Mockup)
         ═══════════════════════════════════════════════════════════════ -->
    <footer class="bp-official-footer">
        <div class="bp-footer-content-wrap">
            <!-- Left: Municipal Heritage Line Art & Official Tagline -->
            <div class="bp-footer-branding">
                <div class="bp-footer-heritage-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.8">
                        <!-- Heritage Municipal Building Line Art -->
                        <path d="M4 56h56M8 56V32h8v24M48 56V32h8v24M16 56V26h32v30"/>
                        <path d="M26 56V42h12v14M24 26l8-14 8 14"/>
                        <circle cx="32" cy="34" r="3"/>
                        <line x1="32" y1="8" x2="32" y2="12"/>
                        <line x1="30" y1="9" x2="34" y2="9"/>
                        <path d="M10 32l6-8 6 8M42 32l6-8 6 8"/>
                    </svg>
                </div>
                <div class="bp-footer-title-block">
                    <span class="bp-footer-city">Barangay Pahina, Cebu City</span>
                    <span class="bp-footer-motto">Serbisyong Tapat, Barangay na Maunlad</span>
                </div>
            </div>

            <!-- Center: Copyright Notice -->
            <div class="bp-footer-center-copy">
                <span>&copy; <?= date('Y') ?> Barangay Pahina. All Rights Reserved.</span>
            </div>

            <!-- Right: Circular Social Media Channels -->
            <div class="bp-footer-social-group">
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="bp-social-bubble" title="Facebook Page" aria-label="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
                </a>
                <a href="https://m.me" target="_blank" rel="noopener noreferrer" class="bp-social-bubble" title="Facebook Messenger" aria-label="Messenger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.43 3.16 7.15V22l3.05-1.68c1.19.33 2.47.51 3.79.51 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm1.09 13.06l-2.73-2.91-5.32 2.91 5.86-6.22 2.78 2.91 5.27-2.91-5.86 6.22z"/></svg>
                </a>
                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="bp-social-bubble" title="YouTube Channel" aria-label="YouTube">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
            </div>
        </div>
    </footer>

</div><!-- /.auth-page-wrapper -->

<!-- Accessible Help / Forgot Password Modal -->
<div class="bp-modal-backdrop" id="forgotPwModal" aria-hidden="true" role="dialog" aria-labelledby="forgotPwModalTitle">
    <div class="bp-modal-dialog">
        <div class="bp-modal-header">
            <div class="bp-modal-icon-badge" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div>
                <h3 class="bp-modal-title" id="forgotPwModalTitle">Password Assistance</h3>
                <p class="bp-modal-subtitle">Barangay Pahina Central Account Security</p>
            </div>
            <button type="button" class="bp-modal-close" id="btnCloseForgotPwModal" aria-label="Close dialog">&times;</button>
        </div>
        <div class="bp-modal-body">
            <p>For resident data protection and identity verification, automated online password resets are currently restricted.</p>
            <div class="bp-modal-notice">
                <strong>How to reset your account password:</strong>
                <ol>
                    <li>Visit the <strong>Barangay Pahina Central Hall</strong> in Cebu City.</li>
                    <li>Proceed to the <strong>IT & Administrative Desk</strong>.</li>
                    <li>Present one valid government-issued ID or proof of residency to verify your account.</li>
                    <li>Office Hours: <strong>Monday to Friday, 8:00 AM – 5:00 PM</strong>.</li>
                </ol>
            </div>
        </div>
        <div class="bp-modal-footer">
            <button type="button" class="auth-navy-button bp-modal-confirm-btn" id="btnDismissForgotPwModal">Understood</button>
        </div>
    </div>
</div>

<script src="<?= h(appUrl('/js/script.js?v=' . filemtime(__DIR__ . '/js/script.js'))) ?>"></script>
</body>
</html>
