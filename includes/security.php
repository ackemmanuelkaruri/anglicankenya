<?php
/**
 * ============================================
 * MULTI-TENANT SECURITY & AUTHENTICATION
 * Phase 4: Enhanced with Secure Short-Lived Sessions
 * ============================================
 * 
 * Place this file at: /includes/security.php
 */

// Prevent direct access
if (!defined('DB_INCLUDED')) {
    die('Direct access not permitted');
}

/**
 * ============================================
 * SECURE SESSION CONFIGURATION
 * ============================================
 */

/**
 * Configure secure session before starting
 * Must be called BEFORE session_start()
 */
function configure_secure_session() {
    // Session timeout: 30 minutes (1800 seconds) - SHORT-LIVED
    $session_timeout = 1800;
    
    ini_set('session.cookie_lifetime', $session_timeout);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    ini_set('session.gc_maxlifetime', $session_timeout);
    
    // Use SHA-256 for stronger session hashing
    ini_set('session.hash_function', 'sha256');
    ini_set('session.hash_bits_per_character', 5);
    
    // Disable transparent session ID handling
    ini_set('session.use_trans_sid', 0);
    
    // Use only cookies (NO URL-based sessions)
    ini_set('session.use_only_cookies', 1);
    
    // CRITICAL SECURITY: Set cookie parameters
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    
    session_set_cookie_params([
        'lifetime' => $session_timeout,          // 30 minutes
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $secure,                     // HTTPS only in production
        'httponly' => true,                      // NOT accessible to JavaScript (prevents XSS)
        'samesite' => 'Strict'                   // CSRF protection
    ]);
}

/**
 * Start secure session with validation
 */
function start_secure_session() {
    // Configure before starting
    if (session_status() === PHP_SESSION_NONE) {
        configure_secure_session();
        session_start();
        
        // Initialize session security on first access
        if (!isset($_SESSION['_session_initialized'])) {
            session_regenerate_id(true);
            $_SESSION['_session_initialized'] = true;
            $_SESSION['_created'] = time();
            $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['_ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Validate session integrity and expiration
    validate_session_security();
}

/**
 * Validate session hasn't been hijacked or expired
 */
function validate_session_security() {
    $session_timeout = 1800; // 30 minutes
    
    // Check session initialization
    if (!isset($_SESSION['_session_initialized'])) {
        return;
    }
    
    // Validate User-Agent hasn't changed (hijacking detection)
    if (isset($_SESSION['_user_agent'])) {
        if ($_SESSION['_user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            destroy_session('User-Agent mismatch - potential session hijacking');
            exit('Session security validation failed.');
        }
    }
    
    // Validate IP address (warn if changed)
    if (isset($_SESSION['_ip_address'])) {
        if ($_SESSION['_ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            error_log("WARNING: IP change detected for session " . session_id() . 
                     " from " . $_SESSION['_ip_address'] . " to " . $_SERVER['REMOTE_ADDR']);
        }
    }
    
    // Check session expiration (SHORT-LIVED)
    if (isset($_SESSION['_created'])) {
        $elapsed = time() - $_SESSION['_created'];
        
        // Hard session timeout
        if ($elapsed > $session_timeout) {
            destroy_session('Session expired (timeout)');
            header('Location: login.php?expired=1');
            exit;
        }
        
        // Inactivity timeout (15 minutes)
        if (isset($_SESSION['_last_activity'])) {
            $inactivity = time() - $_SESSION['_last_activity'];
            
            if ($inactivity > 900) {
                destroy_session('Session expired (inactivity)');
                header('Location: login.php?inactive=1');
                exit;
            }
        }
        
        // Update last activity timestamp
        $_SESSION['_last_activity'] = time();
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Regenerate session ID (use after privilege changes)
 */
function regenerate_session_id() {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
    $_SESSION['_last_activity'] = time();
}

/**
 * Safely destroy session
 */
function destroy_session($reason = 'User logout') {
    // Log session destruction
    if (is_logged_in()) {
        error_log("Session destroyed - User: " . $_SESSION['user_id'] . 
                 " | Reason: $reason | Session ID: " . session_id());
    }
    
    // Clear session data
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Get remaining session time in seconds
 */
function get_session_remaining_time() {
    if (!isset($_SESSION['_created'])) {
        return 0;
    }
    
    $session_timeout = 1800;
    $elapsed = time() - $_SESSION['_created'];
    $remaining = $session_timeout - $elapsed;
    
    return max(0, $remaining);
}

/**
 * Get session status (for client-side warning)
 */
function get_session_status() {
    $remaining = get_session_remaining_time();
    $total = 1800;
    $percent = ($remaining / $total) * 100;
    
    return [
        'remaining_seconds' => $remaining,
        'remaining_minutes' => floor($remaining / 60),
        'percent' => $percent,
        'is_expiring_soon' => $percent < 10, // Less than 3 minutes
        'is_expired' => $remaining <= 0
    ];
}

/**
 * ============================================
 * EXISTING SECURITY FUNCTIONS
 * ============================================
 */

/**
 * Check user roles
 */
function is_super_admin() {
    return isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1;
}

function is_national_admin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

function is_church_admin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3;
}

/**
 * Check if user has specific permission
 */
function has_permission($permission_name) {
    global $pdo;

    if (!is_logged_in()) return false;
    if (is_super_admin()) return true;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.permission_name = ?
        ");
        $stmt->execute([$_SESSION['role_id'], $permission_name]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Require specific permission
 */
function require_permission($permission_name) {
    if (!has_permission($permission_name)) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied: You do not have permission to perform this action.');
    }
}

/**
 * Organization Helpers
 */
function get_user_org_id() {
    return $_SESSION['org_id'] ?? null;
}

function can_access_org($org_id) {
    if (is_super_admin() || is_national_admin()) {
        return true;
    }
    return get_user_org_id() == $org_id;
}

function get_org_filter() {
    if (is_super_admin() || is_national_admin()) {
        return "";
    }
    $org_id = get_user_org_id();
    return " AND org_id = " . intval($org_id);
}

/**
 * Sanitize input
 */
function sanitize_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Log system activity
 */
function log_activity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    global $pdo;
    if (!is_logged_in()) return;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_audit_log 
            (user_id, org_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            get_user_org_id(),
            $action,
            $table_name,
            $record_id,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

/**
 * Fetch user details
 */
function get_user_details($user_id = null) {
    global $pdo;

    $user_id = $user_id ?? $_SESSION['user_id'] ?? null;
    if (!$user_id) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.*, o.org_name, o.org_code,
                r.role_name, r.role_level
            FROM users u
            LEFT JOIN organizations o ON u.org_id = o.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get user details: " . $e->getMessage());
        return null;
    }
}

/**
 * Organization info
 */
function get_organization($org_id = null) {
    global $pdo;
    $org_id = $org_id ?? get_user_org_id();
    if (!$org_id) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->execute([$org_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to get organization: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all accessible organizations
 */
function get_accessible_organizations() {
    global $pdo;

    try {
        if (is_super_admin() || is_national_admin()) {
            $stmt = $pdo->query("SELECT * FROM organizations ORDER BY org_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $org_id = get_user_org_id();
            $stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ?");
            $stmt->execute([$org_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Failed to get organizations: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate organization access
 */
function validate_org_access($org_id) {
    if (!can_access_org($org_id)) {
        log_activity('UNAUTHORIZED_ACCESS_ATTEMPT', null, null, ['attempted_org' => $org_id]);
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied: You cannot access this organization.');
    }
}

/**
 * Encryption utilities
 */
function encrypt_data($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_data($data, $key) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Rate limiting
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) {
    global $pdo;

    try {
        $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")
            ->execute([$time_window]);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $time_window]);
        $attempts = $stmt->fetchColumn();

        if ($attempts >= $max_attempts) return false;

        $pdo->prepare("INSERT INTO login_attempts (identifier, attempted_at) VALUES (?, NOW())")
            ->execute([$identifier]);

        return true;
    } catch (Exception $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true;
    }
}

/**
 * Generate secure token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Role badges
 */
function get_role_badge() {
    if (is_super_admin()) {
        return '<span class="badge badge-danger">Super Admin</span>';
    } elseif (is_national_admin()) {
        return '<span class="badge badge-warning">National Admin</span>';
    } elseif (is_church_admin()) {
        return '<span class="badge badge-primary">Church Admin</span>';
    } else {
        return '<span class="badge badge-secondary">Member</span>';
    }
}

/**
 * Subscription check
 */
function is_subscription_active($org_id = null) {
    global $pdo;

    $org_id = $org_id ?? get_user_org_id();
    if (is_super_admin()) return true;

    try {
        $stmt = $pdo->prepare("
            SELECT subscription_status, subscription_end_date 
            FROM organizations 
            WHERE id = ?
        ");
        $stmt->execute([$org_id]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$org) return false;
        if ($org['subscription_status'] !== 'active') return false;

        if ($org['subscription_end_date'] && strtotime($org['subscription_end_date']) < time()) {
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Subscription check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert role_level to readable label
 */
function get_role_display_name($role_level) {
    if (is_numeric($role_level)) {
        $roles = [
            1 => 'Super Admin',
            2 => 'National Admin',
            3 => 'Diocese Admin',
            4 => 'Archdeaconry Admin',
            5 => 'Deanery Admin',
            6 => 'Parish Admin',
            7 => 'Member'
        ];
        return $roles[$role_level] ?? 'Unknown Role';
    }
    
    $roles = [
        'super_admin' => 'Super Admin',
        'national_admin' => 'National Admin',
        'diocese_admin' => 'Diocese Admin',
        'archdeaconry_admin' => 'Archdeaconry Admin',
        'deanery_admin' => 'Deanery Admin',
        'parish_admin' => 'Parish Admin',
        'member' => 'Member'
    ];
    
    return $roles[strtolower($role_level)] ?? 'Unknown Role';
}
?>