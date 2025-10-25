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
    if (getenv('USE_SUPABASE') === 'true' || file_exists(__DIR__ . '/.env.supabase')) {
        return 'supabase';
    }
    
    // Check if we have Supabase-style env vars
    if (getenv('DB_HOST') && strpos(getenv('DB_HOST'), 'supabase.co') !== false) {
        return 'supabase';
    }
    
    // ✅ Detect if running from CLI
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
        $db_host = getenv('DB_HOST');
        $db_port = getenv('DB_PORT') ?: '6543';
        
        // ✅ FIX: Adjust username format based on port
        $db_user = getenv('DB_USER');
        
        // If using connection pooler (port 6543) and username doesn't have project reference
        if ($db_port == '6543' && strpos($db_user, '.') === false) {
            // Extract project reference from hostname
            // db.iyztzrvjcdqotcqqekkw.supabase.co -> iyztzrvjcdqotcqqekkw
            if (preg_match('/db\.([^.]+)\.supabase\.co/', $db_host, $matches)) {
                $project_ref = $matches[1];
                $db_user = "postgres.{$project_ref}";
                error_log("✅ Adjusted username for connection pooler: {$db_user}");
            }
        }
        
        // ✅ Try alternative hosts if primary fails
        define('DB_HOST_PRIMARY', $db_host);
        define('DB_HOST_POOLER', preg_replace('/^db\./', 'aws-0-ap-southeast-1.pooler.', $db_host));
        
        define('DB_HOST', $db_host);
        define('DB_PORT', $db_port);
        define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
        define('DB_USER', $db_user);
        define('DB_PASS', getenv('DB_PASS'));
        define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8');
        define('APP_ENV', getenv('APP_ENV') ?: 'supabase');
        define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
        define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: '');
        
        // ✅ SSL Mode for Supabase
        define('DB_SSL_MODE', 'require');
        
        error_log("✅ Loaded config from environment variables");
        error_log("   Host: " . DB_HOST);
        error_log("   Port: " . DB_PORT);
        error_log("   User: " . DB_USER);
        error_log("   Database: " . DB_NAME);
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

// Database configuration helper with fallback logic
function get_db_config() {
    $env = get_environment();
    
    // ✅ Special handling for Supabase (PostgreSQL)
    if ($env === 'supabase') {
        return [
            'host' => defined('DB_HOST') ? DB_HOST : 'db.iyztzrvjcdqotcqqekkw.supabase.co',
            'port' => defined('DB_PORT') ? DB_PORT : '6543',
            'name' => defined('DB_NAME') ? DB_NAME : 'postgres',
            'user' => defined('DB_USER') ? DB_USER : 'postgres.iyztzrvjcdqotcqqekkw',
            'pass' => defined('DB_PASS') ? DB_PASS : '',
            'charset' => 'utf8',
            'driver' => 'pgsql',
            'ssl_mode' => defined('DB_SSL_MODE') ? DB_SSL_MODE : 'require',
            // ✅ Connection options for reliability
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 15, // Longer timeout for cloud connections
                PDO::ATTR_PERSISTENT => false
            ]
        ];
    }
    
    // Default MySQL config for other environments
    return [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'name' => defined('DB_NAME') ? DB_NAME : 'anglicankenya',
        'user' => defined('DB_USER') ? DB_USER : 'root',
        'pass' => defined('DB_PASS') ? DB_PASS : '',
        'charset' => 'utf8mb4',
        'driver' => 'mysql',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ];
}

// ✅ Create PDO connection with automatic fallback
function get_pdo_connection() {
    $config = get_db_config();
    
    if ($config['driver'] === 'pgsql') {
        // Try connection with multiple strategies
        $connection_attempts = [
            // Attempt 1: Connection Pooler (IPv4 preferred)
            [
                'host' => $config['host'],
                'port' => '6543',
                'user' => $config['user']
            ],
            // Attempt 2: Direct connection
            [
                'host' => $config['host'],
                'port' => '5432',
                'user' => str_replace('.iyztzrvjcdqotcqqekkw', '', $config['user']) // Remove project ref for direct
            ],
            // Attempt 3: Alternative pooler endpoint
            [
                'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
                'port' => '6543',
                'user' => $config['user']
            ]
        ];
        
        $last_error = null;
        
        foreach ($connection_attempts as $attempt) {
            try {
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
                    $attempt['host'],
                    $attempt['port'],
                    $config['name'],
                    $config['ssl_mode']
                );
                
                error_log("🔄 Attempting connection: {$attempt['host']}:{$attempt['port']} as {$attempt['user']}");
                
                $pdo = new PDO($dsn, $attempt['user'], $config['pass'], $config['options']);
                
                // Test the connection
                $pdo->query('SELECT 1');
                
                error_log("✅ Database connected successfully via {$attempt['host']}:{$attempt['port']}");
                return $pdo;
                
            } catch (PDOException $e) {
                $last_error = $e;
                error_log("❌ Connection attempt failed: " . $e->getMessage());
                continue;
            }
        }
        
        // All attempts failed
        error_log("❌ All database connection attempts failed. Last error: " . $last_error->getMessage());
        throw new Exception("Unable to connect to database: " . $last_error->getMessage());
        
    } else {
        // MySQL connection (standard)
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $config['host'],
            $config['name'],
            $config['charset']
        );
        
        return new PDO($dsn, $config['user'], $config['pass'], $config['options']);
    }
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
