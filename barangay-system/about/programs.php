<?php
/**
 * about/programs.php — Community Programs & Projects (Clean Empty State)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = "Barangay Programs | Barangay Pahina Central";
$activeTab = "programs";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="about-page-wrapper">
    <?php require_once __DIR__ . '/_tabs.php'; ?>

    <div class="about-content-card">
        <!-- Clean Empty State Container -->
        <div class="about-empty-state-card">
            <div class="empty-state-graphic">
                <div class="empty-state-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="12" y1="7" x2="12" y2="13"/><line x1="9" y1="10" x2="15" y2="10"/></svg>
                </div>
                <span class="empty-state-tag">Upcoming Initiatives</span>
            </div>

            <h2 class="empty-state-title">Programs Coming Soon</h2>
            <p class="empty-state-message">
                Barangay Pahina Central community programs, healthcare missions, environmental drives, youth sports activities, and social welfare schedules will be published here as they are officially launched.
            </p>

            <div class="empty-state-info-pills">
                <div class="info-pill">
                    <span class="pill-dot"></span>
                    <span>Community Health & Wellness</span>
                </div>
                <div class="info-pill">
                    <span class="pill-dot"></span>
                    <span>Clean & Green Initiatives</span>
                </div>
                <div class="info-pill">
                    <span class="pill-dot"></span>
                    <span>Youth & Sports Development</span>
                </div>
                <div class="info-pill">
                    <span class="pill-dot"></span>
                    <span>Livelihood & Skills Training</span>
                </div>
            </div>

            <p class="empty-state-footnote">
                Stay tuned for official announcements and schedules.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
