<?php
/**
 * ============================================
 * INITIALIZATION FILE
 * Updated to work with database sessions
 * ============================================
 */

// Only define DB_INCLUDED if not already defined
if (!defined('DB_INCLUDED')) {
    define('DB_INCLUDED', true);
}

// Only load these if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../db.php';
}

// Only load session handler if not already loaded
if (!function_exists('init_database_sessions')) {
    require_once __DIR__ . '/../db_session.php';
}

// Only load security functions if not already loaded
if (!function_exists('start_secure_session')) {
    require_once __DIR__ . '/security.php';
}

// DO NOT call start_secure_session() here - it should be called in the main file

// Load other required files
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Error handling for production
if (!is_development()) {
    error_reporting(0);
    ini_set('display_errors', 0);
}
