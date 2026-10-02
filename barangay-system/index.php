<?php
/**
 * index.php — Root entry point.
 *
 * The login page is now the public landing page of this system.
 * This file simply redirects:
 *   • Not logged in  → login page (the main portal)
 *   • Already logged in → role-appropriate dashboard
 *
 * The old homepage content (hero section, services cards) is no longer
 * shown before login. All useful functionality behind those features
 * remains intact inside the resident/staff/admin sections.
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /barangay-system/' . currentRole() . '/dashboard.php');
} else {
    header('Location: /barangay-system/login.php');
}
exit;
