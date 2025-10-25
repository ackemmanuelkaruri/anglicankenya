<?php
/**
 * ============================================
 * DATABASE CONNECTION
 * Now supports both MySQL and PostgreSQL (Supabase)
 * ============================================
 */

// Load environment configuration
require_once __DIR__ . '/config.php';

try {
    $config = get_db_config();
    
    // ✅ Build DSN based on driver (MySQL or PostgreSQL)
    if ($config['driver'] === 'pgsql') {
        // PostgreSQL connection for Supabase
        // ✅ CRITICAL: Add sslmode=require for Supabase
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['name']};sslmode=require";
        
        // Log connection attempt
        error_log("🔄 Connecting to PostgreSQL: {$config['host']}:{$config['port']} as {$config['user']}");
        
    } else {
        // MySQL connection for local development
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    }
    
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false, // Supabase works better without persistent connections
        PDO::ATTR_TIMEOUT => 10, // 10 second timeout for cloud connections
    ]);
    
    // ✅ Set search_path for PostgreSQL (important for Supabase)
    if ($config['driver'] === 'pgsql') {
        $pdo->exec("SET search_path TO public");
    }
    
    // ✅ Test the connection with a simple query
    $pdo->query('SELECT 1');
    
    // Log successful connection
    error_log("✅ Database connected: {$config['name']} on {$config['host']}:{$config['port']} using {$config['driver']}");
    
} catch (PDOException $e) {
    $error_msg = $e->getMessage();
    error_log("❌ Database connection failed: " . $error_msg);
    error_log("   Host: " . ($config['host'] ?? 'unknown'));
    error_log("   Port: " . ($config['port'] ?? 'unknown'));
    error_log("   User: " . ($config['user'] ?? 'unknown'));
    error_log("   Database: " . ($config['name'] ?? 'unknown'));
    
    if (is_development()) {
        die("Database Connection Error: " . $error_msg . "<br><br>Host: {$config['host']}:{$config['port']}<br>User: {$config['user']}<br>Database: {$config['name']}");
    } else {
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

/**
 * Legacy function for backward compatibility
 */
function is_admin($username = null) {
    global $pdo;
    
    if ($username !== null && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT role_level FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            return $user && in_array($user['role_level'], ['super_admin', 'diocese_admin', 'archdeaconry_admin']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    return isset($_SESSION['role_level']) && in_array($_SESSION['role_level'], ['super_admin', 'diocese_admin', 'archdeaconry_admin']);
}

// ✅ NO CLOSING TAG - Prevents whitespace/BOM issues
