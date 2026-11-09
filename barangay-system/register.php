<?php
/**
 * register.php — Compatibility redirect.
 *
 * Registration is now handled directly on the login page (login.php).
 * This file is kept so that any bookmarks or external links that still
 * point to register.php continue to work — they are silently forwarded
 * to the login page with the registration panel pre-opened.
 *
 * Do NOT delete this file; it is a safe compatibility shim.
 */
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . appUrl('/login.php?panel=register'));
exit;
