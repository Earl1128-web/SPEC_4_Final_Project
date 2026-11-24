<?php
/**
 * about/officials.php — Barangay Officials Directory (Placeholder Layout)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = "Barangay Officials | Barangay Pahina Central";
$activeTab = "officials";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="about-page-wrapper">
    <?php require_once __DIR__ . '/_tabs.php'; ?>

    <div class="about-content-card">
        <!-- Coming Soon Notice Banner -->
        <div class="about-coming-soon-banner">
            <div class="banner-icon-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="banner-content">
                <div class="banner-tag">Directory Under Preparation</div>
                <h2 class="banner-title">Officials Content Coming Soon</h2>
                <p class="banner-desc">
                    The official roster of elected and appointed leaders of Barangay Pahina Central is currently being updated. Official photographs, designations, committee assignments, and contact channels will appear here once officially validated.
                </p>
            </div>
        </div>

        <!-- Clean Placeholder Grid (Ready for future official data without fake names) -->
        <div class="officials-layout-section">
            <h3 class="officials-section-title">Barangay Council & Administration</h3>
            <p class="officials-section-subtitle">Layout ready for future roster implementation</p>

            <div class="officials-grid">
                <!-- Barangay Captain Placeholder -->
                <div class="official-card-placeholder highlight-card">
                    <div class="avatar-placeholder leader-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span class="role-badge leader-badge">Executive Office</span>
                    <h4 class="official-role-title">Punong Barangay (Barangay Captain)</h4>
                    <p class="placeholder-label">Profile Information Coming Soon</p>
                </div>

                <!-- Kagawad Placeholders -->
                <?php for ($i = 1; $i <= 7; $i++): ?>
                    <div class="official-card-placeholder">
                        <div class="avatar-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <span class="role-badge">Legislative Council</span>
                        <h4 class="official-role-title">Barangay Kagawad <?= $i ?></h4>
                        <p class="placeholder-label">Profile Information Coming Soon</p>
                    </div>
                <?php endfor; ?>

                <!-- SK Chairperson Placeholder -->
                <div class="official-card-placeholder">
                    <div class="avatar-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span class="role-badge">Youth Council</span>
                    <h4 class="official-role-title">SK Chairperson</h4>
                    <p class="placeholder-label">Profile Information Coming Soon</p>
                </div>

                <!-- Barangay Secretary Placeholder -->
                <div class="official-card-placeholder">
                    <div class="avatar-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <span class="role-badge">Administration</span>
                    <h4 class="official-role-title">Barangay Secretary</h4>
                    <p class="placeholder-label">Profile Information Coming Soon</p>
                </div>

                <!-- Barangay Treasurer Placeholder -->
                <div class="official-card-placeholder">
                    <div class="avatar-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </div>
                    <span class="role-badge">Administration</span>
                    <h4 class="official-role-title">Barangay Treasurer</h4>
                    <p class="placeholder-label">Profile Information Coming Soon</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
