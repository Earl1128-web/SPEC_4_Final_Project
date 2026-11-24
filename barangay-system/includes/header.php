<?php
/**
 * includes/header.php
 * Expects an optional $pageTitle variable to be set before including.
 * Assumes auth.php has already been included (session started).
 */
$pageTitle = $pageTitle ?? "Barangay Pahina Central – Online Reservation System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= h(appUrl('/css/style.css?v=' . filemtime(__DIR__ . '/../css/style.css'))) ?>">
</head>
<body class="app-body">

<?php if (isLoggedIn()): ?>
<div class="app-layout" id="appLayout">
    <!-- Left Vertical Sidebar Navigation -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Mobile Drawer Overlay Backdrop -->
    <div class="app-sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Viewport on the Right -->
    <div class="app-main-viewport">
        <!-- Top Navigation Header -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle navigation menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar-breadcrumbs">
                    <span class="breadcrumb-root">Pahina Central Portal</span>
                    <span class="breadcrumb-sep">&rsaquo;</span>
                    <span class="breadcrumb-current"><?= h($pageTitle) ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <span class="topbar-role-badge role-<?= h(strtolower(currentRole())) ?>"><?= ucfirst(h(currentRole())) ?></span>
                <span class="topbar-user-greeting">Hi, <strong><?= h($_SESSION['display_name'] ?? 'User') ?></strong></span>
                <a href="<?= h(appUrl('/logout.php')) ?>" class="topbar-logout-btn" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="app-content-body page-container">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success"><?= h($_SESSION['flash_success']) ?></div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-error"><?= h($_SESSION['flash_error']) ?></div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
<?php else: ?>
    <!-- Fallback container for non-authenticated pages -->
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="<?= h(appUrl('/login.php')) ?>">Barangay Pahina Central</a>
            <nav class="main-nav">
                <a href="<?= h(appUrl('/login.php')) ?>" class="btn-link">Login</a>
            </nav>
        </div>
    </header>
    <main class="page-container">
<?php endif; ?>
