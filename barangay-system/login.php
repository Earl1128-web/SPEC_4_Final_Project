<?php
/**
 * login.php — Public landing page & unified authentication portal.
 *
 * This file serves two purposes on a single page:
 *   1. Login  — validates credentials, starts session, redirects to dashboard.
 *   2. Register — creates a resident account (user + resident rows in a transaction).
 *
 * Which form was submitted is determined by the hidden field:
 *   <input type="hidden" name="form" value="login|register">
 *
 * Security preserved:
 *   • CSRF tokens (one per form, same session token)
 *   • prepared statements / PDO everywhere
 *   • password_hash / password_verify
 *   • session_regenerate_id on successful login
 *   • server-side validation (JS validation is cosmetic only)
 *   • role-based dashboard redirect after login
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

/* ── Already logged in → go straight to dashboard ────────────────── */
if (isLoggedIn()) {
    header('Location: /barangay-system/' . currentRole() . '/dashboard.php');
    exit;
}

/* ── Panel & state flags ──────────────────────────────────────────── */
// Show register panel if explicitly requested via URL or if registration
// failed (so errors appear in the right panel without the page feeling broken).
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
       Moved from register.php — identical logic, same prepared statements.
    ───────────────────────────────────────────────────────────────── */
    if ($form === 'register') {
        $showRegister = true; // stay on register panel if errors occur

        // Collect and sanitise submitted values (repopulate on error)
        foreach ($old as $key => $_) {
            $old[$key] = trim($_POST[$key] ?? '');
        }
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // ── Server-side validation (always runs, even when JS already ran) ──
        if ($old['first_name'] === '')  $registerErrors[] = 'First name is required.';
        if ($old['last_name']  === '')  $registerErrors[] = 'Last name is required.';
        if ($old['date_of_birth'] === '') {
            $registerErrors[] = 'Date of birth is required.';
        } elseif (!strtotime($old['date_of_birth'])) {
            $registerErrors[] = 'Please provide a valid date of birth.';
        }
        if ($old['address'] === '') $registerErrors[] = 'Address is required.';
        if ($old['contact_number'] === '' || !preg_match('/^[0-9+()\-\s]{7,20}$/', $old['contact_number'])) {
            $registerErrors[] = 'Please provide a valid contact number.';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $registerErrors[] = 'Please provide a valid email address.';
        }
        if ($old['username'] === '' || strlen($old['username']) < 4) {
            $registerErrors[] = 'Username must be at least 4 characters.';
        }
        if (strlen($password) < 8) {
            $registerErrors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $registerErrors[] = 'Passwords do not match.';
        }

        // ── Duplicate email / username check ────────────────────────
        if (!$registerErrors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
            $stmt->execute([$old['email'], $old['username']]);
            if ($stmt->fetch()) {
                $registerErrors[] = 'An account with that email or username already exists.';
            }
        }

        // ── Insert user + resident in a transaction ──────────────────
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

                // Redirect to login panel with a success flag (no session flash needed).
                header('Location: /barangay-system/login.php?registered=1');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Registration failed: ' . $e->getMessage());
                $registerErrors[] = 'Something went wrong while creating your account. Please try again.';
            }
        }

    /* ─────────────────────────────────────────────────────────────────
       LOGIN
       Original login logic — completely unchanged.
    ───────────────────────────────────────────────────────────────── */
    } else {
        $emailValue = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';

        if ($emailValue === '' || $password === '') {
            $loginErrors[] = 'Please enter both email/username and password.';
        } else {
            // Allow login using either email or username
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->execute([$emailValue, $emailValue]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $loginErrors[] = 'Invalid email/username or password.';
            } elseif ($user['status'] !== 'active') {
                $loginErrors[] = 'This account has been disabled. Please contact the barangay office.';
            } else {
                // Regenerate session ID to prevent session fixation
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

                header('Location: /barangay-system/' . $user['role'] . '/dashboard.php');
                exit;
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
    <title>Barangay Pahina's Online Reservation of Brgy. Certificate</title>
    <meta name="description" content="Barangay Pahina's Online Reservation of Brgy. Certificate — reserve appointments and access local government services online.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/barangay-system/css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="/barangay-system/css/login.css?v=<?= filemtime(__DIR__ . '/css/login.css') ?>">
</head>
<body class="login-body">

<div class="authentication-layout authentication-page<?= $showRegister ? ' register-active' : '' ?>" id="authContainer">

    <!-- ═══════════════════════════════════════════════════════════════
         LEFT PANEL — Fixed Blue Branding Panel (Never changes)
         ═══════════════════════════════════════════════════════════════ -->
    <aside class="branding-panel" aria-label="Barangay Pahina Official Portal">

        <!-- Decorative background shapes (strictly behind text at z-index: 0) -->
        <svg class="bp-deco bp-deco-1" viewBox="0 0 500 500" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="250" cy="250" r="250" fill="white" fill-opacity="0.04"/>
        </svg>
        <svg class="bp-deco bp-deco-2" viewBox="0 0 320 320" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="160" cy="160" r="160" fill="white" fill-opacity="0.04"/>
        </svg>
        <svg class="bp-deco bp-deco-arc" viewBox="0 0 600 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M0 120 Q300 10 600 120" stroke="white" stroke-opacity="0.06" stroke-width="2" fill="none"/>
        </svg>
        <svg class="bp-deco bp-deco-dots" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <pattern id="bp-dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.5" fill="white" fill-opacity="0.10"/>
            </pattern>
            <rect width="200" height="200" fill="url(#bp-dots)"/>
        </svg>

        <!-- Main branding flow -->
        <div class="bp-inner">

            <!-- Top Logo Row: Two official placeholders -->
            <div class="bp-logos">
                <div class="bp-logo-box" title="Place Barangay Logo at assets/images/barangay-logo.png">
                    <img
                        src="/barangay-system/assets/images/barangay-logo.png"
                        alt="Barangay Pahina Logo"
                        class="bp-logo-img"
                        onerror="this.classList.add('is-hidden');"
                    >
                    <div class="bp-logo-fallback">
                        <span class="bp-logo-icon" aria-hidden="true">🏛️</span>
                        <span class="bp-logo-tag">BARANGAY<br>LOGO</span>
                    </div>
                </div>

                <div class="bp-logo-divider" aria-hidden="true"></div>

                <div class="bp-logo-box" title="Place City Logo at assets/images/city-logo.png">
                    <img
                        src="/barangay-system/assets/images/city-logo.png"
                        alt="City / Municipality Logo"
                        class="bp-logo-img"
                        onerror="this.classList.add('is-hidden');"
                    >
                    <div class="bp-logo-fallback">
                        <span class="bp-logo-icon" aria-hidden="true">🏙️</span>
                        <span class="bp-logo-tag">CITY<br>LOGO</span>
                    </div>
                </div>
            </div>

            <!-- Branding Content -->
            <div class="bp-content">
                <div class="bp-badge">
                    <span class="bp-badge-dot" aria-hidden="true"></span>
                    Digital Government Services
                </div>

                <h1 class="bp-title">
                    <span class="bp-title-highlight">Barangay Pahina's</span><br>
                    Online Reservation<br>
                    <span class="bp-title-sub">of Brgy. Certificate</span>
                </h1>

                <p class="bp-description">
                    Reserve your Barangay Certificate appointment online — anytime, anywhere,
                    without waiting in line at the barangay hall.
                </p>

                <div class="bp-features">
                    <div class="bp-feature-item">
                        <span class="bp-feature-icon" aria-hidden="true">📄</span>
                        <span>Request barangay certificates online</span>
                    </div>
                    <div class="bp-feature-item">
                        <span class="bp-feature-icon" aria-hidden="true">📅</span>
                        <span>Book appointments without waiting in line</span>
                    </div>
                    <div class="bp-feature-item">
                        <span class="bp-feature-icon" aria-hidden="true">🗂️</span>
                        <span>Track your document history anytime</span>
                    </div>
                </div>
            </div>

        </div><!-- /.bp-inner -->

        <!-- Bottom community message -->
        <p class="bp-footer-message">
            Serving Barangay Pahina's community with accessible digital services.
        </p>

    </aside><!-- /.branding-panel -->


    <!-- ═══════════════════════════════════════════════════════════════
         RIGHT PANEL — Switchable Authentication Area (Login / Register)
         ═══════════════════════════════════════════════════════════════ -->
    <main class="auth-panel" aria-label="Authentication">

        <!-- Mobile header (shows on smaller screens where layout stacks) -->
        <div class="bp-mobile-header" aria-hidden="true">
            <div class="bp-mobile-logo-row">
                <div class="bp-mobile-logo-box">🏛️</div>
                <div class="bp-mobile-logo-box">🏙️</div>
            </div>
            <p class="bp-mobile-title">Barangay Pahina's Online Reservation</p>
        </div>

        <div class="auth-forms-wrapper">

            <!-- ─────────────────────────────────────────────────────────
                 VIEW 1: LOGIN FORM (Default view on initial page load)
                 ───────────────────────────────────────────────────────── -->
            <section class="auth-form login-form" id="loginView" aria-label="Resident Login">

                <!-- Small branding badge on desktop -->
                <div class="auth-desktop-brand">
                    <span class="auth-site-pill">Barangay Pahina Portal</span>
                </div>

                <h2 class="auth-heading">Welcome Back!</h2>
                <p class="auth-subheading">Log in to access your barangay services and appointments.</p>

                <!-- Registration success banner -->
                <?php if ($registered): ?>
                    <div class="auth-alert auth-alert-success" role="alert">
                        <span class="auth-alert-icon" aria-hidden="true">✅</span>
                        <div>Registration successful! You may now log in with your new account.</div>
                    </div>
                <?php endif; ?>

                <!-- PHP Login Error Alerts -->
                <?php foreach ($loginErrors as $error): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <span class="auth-alert-icon" aria-hidden="true">⚠️</span>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endforeach; ?>

                <!-- Login Form (action, method, CSRF, and inputs fully preserved) -->
                <form method="post" action="/barangay-system/login.php" id="loginForm" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="login">

                    <div class="auth-field">
                        <label for="email">Email or Username</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input
                                type="text"
                                id="email"
                                name="email"
                                value="<?= h($emailValue) ?>"
                                placeholder="Enter your email or username"
                                required
                                autocomplete="username"
                                aria-label="Email or Username"
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                aria-label="Password"
                            >
                            <button
                                type="button"
                                class="auth-pw-toggle"
                                id="pwToggle"
                                aria-label="Toggle password visibility"
                                title="Show / hide password"
                            >
                                <svg id="pwIconShow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="pwIconHide" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn auth-btn-login" id="loginSubmitBtn">
                        <span>Login Now</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>

                <!-- Switch to Register view (client-side only, no reload) -->
                <p class="auth-switch-text">
                    Don't have an account?
                    <button type="button" id="btnShowRegister" class="auth-link-btn">Register here</button>
                </p>

                <!-- Demo Accounts Box -->
                <details class="lf-demo-box">
                    <summary class="lf-demo-summary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Demo Accounts
                    </summary>
                    <div class="lf-demo-content">
                        <p class="lf-demo-note">All demo accounts use password: <code>Password123!</code></p>
                        <table class="lf-demo-table">
                            <thead>
                                <tr><th>Role</th><th>Email</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><span class="lf-role-badge lf-role-admin">Admin</span></td><td>admin@barangay.gov.ph</td></tr>
                                <tr><td><span class="lf-role-badge lf-role-staff">Staff</span></td><td>staff1@barangay.gov.ph</td></tr>
                                <tr><td><span class="lf-role-badge lf-role-resident">Resident</span></td><td>juan.delacruz@example.com</td></tr>
                            </tbody>
                        </table>
                    </div>
                </details>

            </section><!-- /.login-form -->


            <!-- ─────────────────────────────────────────────────────────
                 VIEW 2: REGISTER FORM (Appears in place upon clicking Register)
                 ───────────────────────────────────────────────────────── -->
            <section class="auth-form register-form" id="registerView" aria-label="Create Account">

                <div class="auth-desktop-brand">
                    <span class="auth-site-pill">Barangay Pahina Registration</span>
                </div>

                <h2 class="auth-heading">Create Account</h2>
                <p class="auth-subheading">
                    Join Barangay Pahina's online portal to reserve barangay certificate appointments.
                </p>

                <!-- PHP Register Error Alerts -->
                <?php foreach ($registerErrors as $error): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <span class="auth-alert-icon" aria-hidden="true">⚠️</span>
                        <div><?= h($error) ?></div>
                    </div>
                <?php endforeach; ?>

                <form method="post" action="/barangay-system/login.php" id="registerForm" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="register">

                    <!-- Efficient 2-Column Desktop Grid -->
                    <div class="auth-grid-row auth-cols-2">
                        <!-- Row 1: First Name | Middle Name -->
                        <div class="auth-field">
                            <label for="reg_first_name">First Name <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                id="reg_first_name"
                                name="first_name"
                                class="auth-input-clean"
                                value="<?= h($old['first_name']) ?>"
                                placeholder="Juan"
                                autocomplete="given-name"
                            >
                        </div>
                        <div class="auth-field">
                            <label for="reg_middle_name">Middle Name</label>
                            <input
                                type="text"
                                id="reg_middle_name"
                                name="middle_name"
                                class="auth-input-clean"
                                value="<?= h($old['middle_name']) ?>"
                                placeholder="(optional)"
                            >
                        </div>

                        <!-- Row 2: Last Name | Date of Birth -->
                        <div class="auth-field">
                            <label for="reg_last_name">Last Name <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                id="reg_last_name"
                                name="last_name"
                                class="auth-input-clean"
                                value="<?= h($old['last_name']) ?>"
                                placeholder="Dela Cruz"
                                autocomplete="family-name"
                            >
                        </div>
                        <div class="auth-field">
                            <label for="reg_date_of_birth">Date of Birth <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="date"
                                id="reg_date_of_birth"
                                name="date_of_birth"
                                class="auth-input-clean"
                                value="<?= h($old['date_of_birth']) ?>"
                            >
                        </div>

                        <!-- Row 3: Contact Number | Email -->
                        <div class="auth-field">
                            <label for="reg_contact_number">Contact Number <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="tel"
                                id="reg_contact_number"
                                name="contact_number"
                                class="auth-input-clean"
                                value="<?= h($old['contact_number']) ?>"
                                placeholder="09XXXXXXXXX"
                                autocomplete="tel"
                            >
                        </div>
                        <div class="auth-field">
                            <label for="reg_email">Email Address <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="email"
                                id="reg_email"
                                name="email"
                                class="auth-input-clean"
                                value="<?= h($old['email']) ?>"
                                placeholder="you@email.com"
                                autocomplete="email"
                            >
                        </div>

                        <!-- Row 4: Username | Password -->
                        <div class="auth-field">
                            <label for="reg_username">Username <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                id="reg_username"
                                name="username"
                                class="auth-input-clean"
                                value="<?= h($old['username']) ?>"
                                placeholder="min. 4 characters"
                                autocomplete="username"
                            >
                        </div>
                        <div class="auth-field">
                            <label for="reg_password">Password <span class="auth-req" aria-hidden="true">*</span></label>
                            <div class="auth-input-with-toggle">
                                <input
                                    type="password"
                                    id="reg_password"
                                    name="password"
                                    class="auth-input-clean"
                                    placeholder="min. 8 characters"
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    class="auth-pw-toggle"
                                    id="regPwToggle"
                                    aria-label="Toggle password visibility"
                                    title="Show / hide password"
                                >
                                    <svg id="regPwIconShow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg id="regPwIconHide" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Row 5: Confirm Password | Address -->
                        <div class="auth-field">
                            <label for="reg_confirm_password">Confirm Password <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="password"
                                id="reg_confirm_password"
                                name="confirm_password"
                                class="auth-input-clean"
                                placeholder="Re-enter password"
                                autocomplete="new-password"
                            >
                        </div>
                        <div class="auth-field">
                            <label for="reg_address">Address <span class="auth-req" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                id="reg_address"
                                name="address"
                                class="auth-input-clean"
                                value="<?= h($old['address']) ?>"
                                placeholder="Street, Brgy Pahina, City"
                                autocomplete="street-address"
                            >
                        </div>
                    </div>

                    <button type="submit" class="auth-btn auth-btn-register" id="registerSubmitBtn">
                        <span>Create Account</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>

                <!-- Switch back to Login view (client-side only, no reload) -->
                <p class="auth-switch-text">
                    Already have an account?
                    <button type="button" id="btnShowLogin" class="auth-link-btn">Login here</button>
                </p>

            </section><!-- /.register-form -->

        </div><!-- /.auth-forms-wrapper -->

    </main><!-- /.auth-panel -->

</div><!-- /.authentication-layout -->

<script src="/barangay-system/js/script.js?v=<?= filemtime(__DIR__ . '/js/script.js') ?>"></script>
</body>
</html>
