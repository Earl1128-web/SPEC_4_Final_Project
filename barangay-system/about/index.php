<?php
/**
 * about/index.php — Entry redirect to Our Barangay
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Location: ' . appUrl('/about/our-barangay.php'));
exit;
