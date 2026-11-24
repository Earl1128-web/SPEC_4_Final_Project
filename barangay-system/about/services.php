<?php
/**
 * about/services.php — Public Services Catalog (Active & Coming Soon)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = "Barangay Services | Barangay Pahina Central";
$activeTab = "services";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="about-page-wrapper">
    <?php require_once __DIR__ . '/_tabs.php'; ?>

    <div class="about-content-card">
        <!-- Available Services Section -->
        <div class="services-group-block">
            <div class="section-title-wrap">
                <span class="status-indicator-badge live-badge">Available Now Online</span>
                <h2 class="services-group-title">Active Certificate & Reservation Services</h2>
                <p class="services-group-sub">These services are currently functional and accessible through your account.</p>
            </div>

            <div class="services-card-grid">
                <!-- Service 1: Barangay Clearance -->
                <div class="service-detail-card active-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">📄</div>
                        <span class="service-availability-pill live">Available</span>
                    </div>
                    <h3 class="service-name">Barangay Clearance</h3>
                    <p class="service-description">
                        Official certification issued to residents for local employment, job applications, postal ID, police clearance requirements, and legal documentation.
                    </p>
                    <div class="service-footer-action">
                        <?php if (currentRole() === 'resident'): ?>
                            <a href="<?= h(appUrl('/resident/reservation.php')) ?>" class="service-action-btn">Request Certificate &rarr;</a>
                        <?php else: ?>
                            <a href="<?= h(appUrl('/staff/reservations.php')) ?>" class="service-action-btn">View Queue &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Service 2: Certificate of Residency -->
                <div class="service-detail-card active-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">🏠</div>
                        <span class="service-availability-pill live">Available</span>
                    </div>
                    <h3 class="service-name">Certificate of Residency</h3>
                    <p class="service-description">
                        Affirms that an individual is a bona fide resident of Barangay Pahina Central. Required for bank account opening, school enrollment, and government IDs.
                    </p>
                    <div class="service-footer-action">
                        <?php if (currentRole() === 'resident'): ?>
                            <a href="<?= h(appUrl('/resident/reservation.php')) ?>" class="service-action-btn">Request Certificate &rarr;</a>
                        <?php else: ?>
                            <a href="<?= h(appUrl('/staff/reservations.php')) ?>" class="service-action-btn">View Queue &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Service 3: Certificate of Indigency -->
                <div class="service-detail-card active-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">🤝</div>
                        <span class="service-availability-pill live">Available</span>
                    </div>
                    <h3 class="service-name">Certificate of Indigency</h3>
                    <p class="service-description">
                        Assists residents in applying for government social welfare, hospital medical assistance, public attorney services, and educational scholarships.
                    </p>
                    <div class="service-footer-action">
                        <?php if (currentRole() === 'resident'): ?>
                            <a href="<?= h(appUrl('/resident/reservation.php')) ?>" class="service-action-btn">Request Certificate &rarr;</a>
                        <?php else: ?>
                            <a href="<?= h(appUrl('/staff/reservations.php')) ?>" class="service-action-btn">View Queue &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Service 4: Express Online Appointment -->
                <div class="service-detail-card active-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">⏱️</div>
                        <span class="service-availability-pill live">Available</span>
                    </div>
                    <h3 class="service-name">Appointment Reservation</h3>
                    <p class="service-description">
                        Schedule preferred date and time slots online before visiting the barangay hall. Eliminates waiting in long lines and guarantees priority document release.
                    </p>
                    <div class="service-footer-action">
                        <?php if (currentRole() === 'resident'): ?>
                            <a href="<?= h(appUrl('/resident/reservation.php')) ?>" class="service-action-btn">Book Appointment &rarr;</a>
                        <?php else: ?>
                            <a href="<?= h(appUrl('/staff/reservations.php')) ?>" class="service-action-btn">Manage Slots &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Services Section (Coming Soon) -->
        <div class="services-group-block upcoming-block">
            <div class="section-title-wrap">
                <span class="status-indicator-badge coming-soon-badge">Planned Services</span>
                <h2 class="services-group-title">Future Services (Coming Soon)</h2>
                <p class="services-group-sub">These services are currently under design and will be activated in upcoming system phases.</p>
            </div>

            <div class="services-card-grid">
                <div class="service-detail-card upcoming-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">🏪</div>
                        <span class="service-availability-pill coming-soon">Coming Soon</span>
                    </div>
                    <h3 class="service-name">Barangay Business Clearance</h3>
                    <p class="service-description">
                        Commercial clearance required for local business permits and retail operations inside Barangay Pahina Central.
                    </p>
                </div>

                <div class="service-detail-card upcoming-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">⚖️</div>
                        <span class="service-availability-pill coming-soon">Coming Soon</span>
                    </div>
                    <h3 class="service-name">Lupon Tagapamayapa Mediation</h3>
                    <p class="service-description">
                        Online filing and appointment scheduling for barangay conciliation, mediation, and dispute settlement hearings.
                    </p>
                </div>

                <div class="service-detail-card upcoming-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">👵</div>
                        <span class="service-availability-pill coming-soon">Coming Soon</span>
                    </div>
                    <h3 class="service-name">Senior Citizen & PWD Desk</h3>
                    <p class="service-description">
                        Specialized support desk and benefits registration for senior citizens and persons with disabilities.
                    </p>
                </div>

                <div class="service-detail-card upcoming-service-card">
                    <div class="service-card-top">
                        <div class="service-icon-box">🚨</div>
                        <span class="service-availability-pill coming-soon">Coming Soon</span>
                    </div>
                    <h3 class="service-name">Emergency & Calamity Assistance</h3>
                    <p class="service-description">
                        Digital registry for rapid response, relief distribution, and community emergency verification during incidents.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
