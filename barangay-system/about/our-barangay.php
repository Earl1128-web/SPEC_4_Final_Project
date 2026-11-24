<?php
/**
 * about/our-barangay.php — Overview & Community Profile of Barangay Pahina Central
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = "Our Barangay | Barangay Pahina Central";
$activeTab = "our-barangay";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="about-page-wrapper">
    <?php require_once __DIR__ . '/_tabs.php'; ?>

    <div class="about-content-card">
        <!-- Hero Photo & Intro -->
        <div class="about-hero-grid">
            <div class="about-hero-image-wrap">
                <img
                    src="<?= h(appUrl('/assets/images/barangay_hall.jpg')) ?>"
                    alt="Barangay Pahina Central Hall"
                    class="about-hall-photo"
                >
                <div class="about-image-caption">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Barangay Pahina Central Hall, Cebu City</span>
                </div>
            </div>

            <div class="about-hero-body">
                <div class="about-badge-headline">Community Profile</div>
                <h2 class="about-section-heading">Dedicated to Public Service & Unity</h2>
                <p class="about-lead-paragraph">
                    Barangay Pahina is a community dedicated to providing accessible public services, promoting unity, and improving the welfare of its residents.
                </p>
                <p class="about-secondary-paragraph">
                    Through digital modernization and transparent local governance, Barangay Pahina Central aims to streamline public transactions, certificate issuances, and community engagements for all residents, youth, senior citizens, and business stakeholders.
                </p>

                <div class="about-quick-stats">
                    <div class="quick-stat-item">
                        <span class="stat-icon-wrap">🏛️</span>
                        <div class="stat-detail">
                            <span class="stat-title">Municipality</span>
                            <span class="stat-value">Cebu City, District 1</span>
                        </div>
                    </div>
                    <div class="quick-stat-item">
                        <span class="stat-icon-wrap">📍</span>
                        <div class="stat-detail">
                            <span class="stat-title">Island Group</span>
                            <span class="stat-value">Visayas (Region VII)</span>
                        </div>
                    </div>
                    <div class="quick-stat-item">
                        <span class="stat-icon-wrap">💻</span>
                        <div class="stat-detail">
                            <span class="stat-title">Online System</span>
                            <span class="stat-value">24/7 Digital Portal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Columns -->
        <div class="about-sections-grid">
            <div class="about-info-box">
                <div class="info-box-header">
                    <span class="info-box-icon">🎯</span>
                    <h3>Mission</h3>
                </div>
                <p>
                    To deliver prompt, honest, and accessible public service; promote peace, order, and disaster preparedness; and empower residents through responsive community programs and digital access.
                </p>
            </div>

            <div class="about-info-box">
                <div class="info-box-header">
                    <span class="info-box-icon">🌟</span>
                    <h3>Vision</h3>
                </div>
                <p>
                    A vibrant, safe, and progressive Barangay Pahina Central where every resident enjoys quality public services, active community participation, and a peaceful environment.
                </p>
            </div>

            <div class="about-info-box">
                <div class="info-box-header">
                    <span class="info-box-icon">🏢</span>
                    <h3>Barangay Hall Information</h3>
                </div>
                <ul class="about-info-list">
                    <li><strong>Location:</strong> Barangay Pahina Central Hall, Cebu City, Philippines</li>
                    <li><strong>Office Hours:</strong> Monday – Friday, 8:00 AM – 5:00 PM</li>
                    <li><strong>Digital Services:</strong> Online certificate requests & appointment bookings available anytime</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
