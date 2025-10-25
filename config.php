<?php
/**
 * ============================================
 * ENVIRONMENT CONFIGURATION MANAGER
 * Handles dev/staging/production/supabase environments
 * Safe for both CLI and Web modes
 * ============================================
 */

// Detect environment (supports both web + CLI)
function get_environment() {
    // ✅ CHECK FOR SUPABASE ENVIRONMENT FIRST
    // If you want to force Supabase mode, set this environment variable
    if (getenv('USE_SUPABASE') === 'true' || file_exists(__DIR__ . '/.env.supabase')) {
        return 'supabase';
    }
    
    // ✅ Detect if running from CLI (like php migrate.php)
    if (php_sapi_name() === 'cli') {
        return 'development';
    }
    
    // For web requests, use HTTP_HOST safely
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Check for localhost (development)
    if (in_array($host, ['localhost', '127.0.0.1', 'localhost:8080', 'localhost:80'])) {
        return 'development';
    }
    
    // Check for staging domains
    if (strpos($host, 'staging.') === 0) {
        return 'staging';
    }
    
    // Everything else = production
    return 'production';
}

// Load environment-specific configuration
function load_config() {
    $env = get_environment();
    
    // ✅ PRIORITY: Check if running on Render (environment variables set)
    if (getenv('DB_HOST') !== false) {
        // Running on Render or similar platform - use environment variables
        define('DB_HOST', getenv('DB_HOST'));
        define('DB_PORT', getenv('DB_PORT') ?: '6543');
        define('DB_NAME', getenv('DB_NAME'));
        define('DB_USER', getenv('DB_USER'));
        define('DB_PASS', getenv('DB_PASS'));
        define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8');
        define('APP_ENV', getenv('APP_ENV') ?: 'supabase');
        define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
        define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: '');
        
        error_log("✅ Loaded config from environment variables");
        return;
    }
    
    // Otherwise, load from .env file (local development)
    $config_file = __DIR__ . "/.env.{$env}";
    
    if (!file_exists($config_file)) {
        die("Configuration file not found: .env.{$env}");
    }
    
    // Parse .env file
    $lines = file($config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as constant if not already defined
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Load the configuration
load_config();

// Database configuration helper
function get_db_config() {
    $env = get_environment();
    
    // ✅ Special handling for Supabase (PostgreSQL)
    if ($env === 'supabase') {
        return [
            'host' => defined('DB_HOST') ? DB_HOST : 'aws-0-ap-southeast-1.pooler.supabase.com',
            'port' => defined('DB_PORT') ? DB_PORT : '6543',
            'name' => defined('DB_NAME') ? DB_NAME : 'postgres',
            'user' => defined('DB_USER') ? DB_USER : 'postgres.iyztzrvjcdqotcqqekkw',
            'pass' => defined('DB_PASS') ? DB_PASS : '',
            'charset' => 'utf8',
            'driver' => 'pgsql' // PostgreSQL for Supabase
        ];
    }
    
    // Default MySQL config for other environments
    return [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'name' => defined('DB_NAME') ? DB_NAME : 'anglicankenya',
        'user' => defined('DB_USER') ? DB_USER : 'root',
        'pass' => defined('DB_PASS') ? DB_PASS : '',
        'charset' => 'utf8mb4',
        'driver' => 'mysql'
    ];
}

// Environment check helpers
function current_environment() {
    return get_environment();
}

function is_development() {
    return get_environment() === 'development';
}

function is_production() {
    return get_environment() === 'production';
}

function is_supabase() {
    return get_environment() === 'supabase';
}

// Error reporting (show all errors in development only)
if (is_development()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

// ✅ NO CLOSING TAG - Prevents whitespace/BOM issues
