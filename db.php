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
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['name']};options='--client_encoding={$config['charset']}'";
    } else {
        // MySQL connection for local development
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    }
    
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false, // Supabase works better without persistent connections
    ]);
    
    // ✅ Set search_path for PostgreSQL (important for Supabase)
    if ($config['driver'] === 'pgsql') {
        $pdo->exec("SET search_path TO public");
    }
    
    // Log successful connection in development only
    if (is_development() || is_supabase()) {
        error_log("✅ Database connected: {$config['name']} on {$config['host']} using {$config['driver']}");
    }
    
} catch (PDOException $e) {
    error_log("❌ Database connection failed: " . $e->getMessage());
    
    if (is_development()) {
        die("Database Connection Error: " . $e->getMessage());
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
