<?php
/**
 * about/_tabs.php — Shared Horizontal Sub-Navigation for About Barangay
 * Expects $activeTab ('our-barangay', 'officials', 'services', 'programs')
 */
$activeTab = $activeTab ?? 'our-barangay';
?>
<div class="about-header-block">
    <div class="about-header-text">
        <span class="about-category-pill">About Barangay</span>
        <h1 class="about-main-title">Barangay Pahina Central</h1>
        <p class="about-main-subtext">Community information, public services, officials directory, and ongoing programs.</p>
    </div>
</div>

<nav class="about-tabs-container" aria-label="About Barangay Navigation">
    <div class="about-tabs-nav">
        <a href="<?= h(appUrl('/about/our-barangay.php')) ?>" class="about-tab-link <?= $activeTab === 'our-barangay' ? 'active' : '' ?>">
            <svg class="tab-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Our Barangay</span>
        </a>
        <a href="<?= h(appUrl('/about/officials.php')) ?>" class="about-tab-link <?= $activeTab === 'officials' ? 'active' : '' ?>">
            <svg class="tab-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Officials</span>
        </a>
        <a href="<?= h(appUrl('/about/services.php')) ?>" class="about-tab-link <?= $activeTab === 'services' ? 'active' : '' ?>">
            <svg class="tab-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            <span>Services</span>
        </a>
        <a href="<?= h(appUrl('/about/programs.php')) ?>" class="about-tab-link <?= $activeTab === 'programs' ? 'active' : '' ?>">
            <svg class="tab-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <span>Programs</span>
        </a>
    </div>
</nav>
