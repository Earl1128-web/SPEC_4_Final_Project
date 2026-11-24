<?php
/**
 * includes/sidebar.php — Unified Vertical Sidebar Navigation
 * Positioned on the LEFT side of the screen for Resident, Staff, and Admin pages.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$isAbout = (strpos($scriptName, '/about/') !== false);
$currentRole = currentRole();
$userDisplay = $_SESSION['display_name'] ?? 'User';
$userInitial = strtoupper(substr($userDisplay, 0, 1));

// Helper function to check if link is active
if (!function_exists('isNavActive')) {
    function isNavActive($path) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        return (strpos($scriptName, $path) !== false) ? 'active' : '';
    }
}
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Main Navigation">
    <!-- 1. Sidebar Brand Header -->
    <div class="sidebar-brand-block">
        <a href="<?= h(appUrl('/' . $currentRole . '/dashboard.php')) ?>" class="sidebar-brand-link">
            <div class="sidebar-logo-frame">
                <img
                    src="<?= h(appUrl('/assets/images/barangay_pahina_central_logo.png')) ?>"
                    alt="Barangay Pahina Central Seal"
                    class="sidebar-logo-img"
                >
            </div>
            <div class="sidebar-brand-info">
                <span class="sidebar-brand-name">Barangay Pahina</span>
                <span class="sidebar-brand-sub">Central &bull; Cebu City</span>
            </div>
        </a>
    </div>

    <!-- 2. Navigation Items -->
    <div class="sidebar-nav-scroll">
        <!-- ABOUT BARANGAY SECTION (Positioned ABOVE Dashboard) -->
        <div class="sidebar-nav-group">
            <div class="sidebar-group-label">ABOUT BARANGAY</div>
            
            <div class="sidebar-dropdown-wrap <?= $isAbout ? 'open' : '' ?>" id="aboutDropdownWrap">
                <button type="button" class="sidebar-link sidebar-dropdown-toggle <?= $isAbout ? 'active' : '' ?>" id="aboutMenuToggle" aria-expanded="<?= $isAbout ? 'true' : 'false' ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    <span class="sidebar-text">About Barangay</span>
                    <span class="dropdown-chevron">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                </button>

                <!-- Sub-navigation Items -->
                <div class="sidebar-sub-menu" id="aboutSubMenu">
                    <a href="<?= h(appUrl('/about/our-barangay.php')) ?>" class="sidebar-sub-link <?= isNavActive('/about/our-barangay.php') ?>">
                        <span class="sub-bullet"></span>
                        <span>Our Barangay</span>
                    </a>
                    <a href="<?= h(appUrl('/about/officials.php')) ?>" class="sidebar-sub-link <?= isNavActive('/about/officials.php') ?>">
                        <span class="sub-bullet"></span>
                        <span>Officials</span>
                    </a>
                    <a href="<?= h(appUrl('/about/services.php')) ?>" class="sidebar-sub-link <?= isNavActive('/about/services.php') ?>">
                        <span class="sub-bullet"></span>
                        <span>Services</span>
                    </a>
                    <a href="<?= h(appUrl('/about/programs.php')) ?>" class="sidebar-sub-link <?= isNavActive('/about/programs.php') ?>">
                        <span class="sub-bullet"></span>
                        <span>Programs</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN NAVIGATION SECTION -->
        <div class="sidebar-nav-group">
            <div class="sidebar-group-label">MAIN NAVIGATION</div>

            <!-- Role-Specific Links: RESIDENT -->
            <?php if ($currentRole === 'resident'): ?>
                <a href="<?= h(appUrl('/resident/dashboard.php')) ?>" class="sidebar-link <?= isNavActive('/resident/dashboard.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </span>
                    <span class="sidebar-text">Dashboard</span>
                </a>

                <a href="<?= h(appUrl('/resident/reservations.php')) ?>" class="sidebar-link <?= isNavActive('/resident/reservations.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </span>
                    <span class="sidebar-text">My Requests</span>
                </a>

                <a href="<?= h(appUrl('/resident/reservation.php')) ?>" class="sidebar-link <?= isNavActive('/resident/reservation.php') && !isNavActive('/resident/reservations.php') ? 'active' : '' ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
                    </span>
                    <span class="sidebar-text">Appointments</span>
                </a>

                <a href="<?= h(appUrl('/resident/certificates.php')) ?>" class="sidebar-link <?= isNavActive('/resident/certificates.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    </span>
                    <span class="sidebar-text">Certificates</span>
                </a>

                <a href="<?= h(appUrl('/resident/profile.php')) ?>" class="sidebar-link <?= isNavActive('/resident/profile.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span class="sidebar-text">Profile</span>
                </a>

            <!-- Role-Specific Links: STAFF -->
            <?php elseif ($currentRole === 'staff'): ?>
                <a href="<?= h(appUrl('/staff/dashboard.php')) ?>" class="sidebar-link <?= isNavActive('/staff/dashboard.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </span>
                    <span class="sidebar-text">Dashboard</span>
                </a>

                <a href="<?= h(appUrl('/staff/reservations.php')) ?>" class="sidebar-link <?= isNavActive('/staff/reservations.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <span class="sidebar-text">Reservations</span>
                </a>

                <a href="<?= h(appUrl('/staff/walkin-transactions.php')) ?>" class="sidebar-link <?= isNavActive('/staff/walkin-transactions.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    </span>
                    <span class="sidebar-text">Walk-in Transactions</span>
                </a>

                <a href="<?= h(appUrl('/staff/residents.php')) ?>" class="sidebar-link <?= isNavActive('/staff/residents.php') || isNavActive('/staff/resident-view.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <span class="sidebar-text">Residents</span>
                </a>

                <a href="<?= h(appUrl('/staff/issue-certificate.php')) ?>" class="sidebar-link <?= isNavActive('/staff/issue-certificate.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    </span>
                    <span class="sidebar-text">Issue Certificate</span>
                </a>

            <!-- Role-Specific Links: ADMIN -->
            <?php elseif ($currentRole === 'admin'): ?>
                <a href="<?= h(appUrl('/admin/dashboard.php')) ?>" class="sidebar-link <?= isNavActive('/admin/dashboard.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </span>
                    <span class="sidebar-text">Dashboard</span>
                </a>

                <a href="<?= h(appUrl('/admin/residents.php')) ?>" class="sidebar-link <?= isNavActive('/admin/residents.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <span class="sidebar-text">Residents</span>
                </a>

                <a href="<?= h(appUrl('/admin/staff.php')) ?>" class="sidebar-link <?= isNavActive('/admin/staff.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </span>
                    <span class="sidebar-text">Staff Management</span>
                </a>

                <a href="<?= h(appUrl('/admin/appointments.php')) ?>" class="sidebar-link <?= isNavActive('/admin/appointments.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    <span class="sidebar-text">Appointments</span>
                </a>

                <a href="<?= h(appUrl('/admin/reservations.php')) ?>" class="sidebar-link <?= isNavActive('/admin/reservations.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <span class="sidebar-text">Reservations</span>
                </a>

                <a href="<?= h(appUrl('/staff/walkin-transactions.php')) ?>" class="sidebar-link <?= isNavActive('/staff/walkin-transactions.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    </span>
                    <span class="sidebar-text">Walk-in Transactions</span>
                </a>

                <a href="<?= h(appUrl('/admin/audit-logs.php')) ?>" class="sidebar-link <?= isNavActive('/admin/audit-logs.php') ?>">
                    <span class="sidebar-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    <span class="sidebar-text">Audit Logs</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. Sidebar Bottom Area: User Info & Logout -->
    <div class="sidebar-footer-block">
        <div class="sidebar-user-card">
            <div class="sidebar-user-avatar"><?= h($userInitial) ?></div>
            <div class="sidebar-user-meta">
                <span class="sidebar-user-name" title="<?= h($userDisplay) ?>"><?= h($userDisplay) ?></span>
                <span class="sidebar-user-role role-badge-<?= h(strtolower($currentRole)) ?>"><?= ucfirst(h($currentRole)) ?></span>
            </div>
        </div>

        <a href="<?= h(appUrl('/logout.php')) ?>" class="sidebar-logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
