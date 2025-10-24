<?php
/**
 * ============================================
 * SECURE LOGOUT
 * Properly destroys session and clears cookies
 * ============================================
 */

define('DB_INCLUDED', true); // ✅ Allow security.php to load safely

require_once __DIR__ . '/includes/init.php';


// Start secure session
start_secure_session();

// Log the logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    log_activity('LOGOUT_SUCCESS');
}

// Use the secure destroy_session function from security.php
destroy_session('User initiated logout');

// Delete "Remember Me" cookie if exists
setcookie(
    'remember_user',
    '',
    time() - 3600,
    "/",
    $_SERVER['HTTP_HOST'] ?? '',
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
    true
);

// Add a short-lived cookie to show confirmation message
setcookie(
    'logout_success',
    '1',
    time() + 5,
    "/",
    $_SERVER['HTTP_HOST'] ?? '',
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
    true
);

// Redirect to login page
header('Location: login.php?logout=success');
exit;
?>
