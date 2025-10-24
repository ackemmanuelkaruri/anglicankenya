<?php
/**
 * ============================================
 * AJAX ROLE ASSIGNMENT ENDPOINT (SECURED)
 * Handles role updates via AJAX requests
 * ============================================
 */

// Prevent any output before JSON response
ob_start();

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper headers for JSON response
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Load dependencies
require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';

// Check if we're in development mode
$is_dev = (defined('ENVIRONMENT') && ENVIRONMENT === 'development') || 
          ($_SERVER['SERVER_NAME'] === 'localhost') ||
          ($_SERVER['HTTP_HOST'] === 'localhost');

// Debug logging (only in development)
if ($is_dev) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Log but don't display
    @file_put_contents(__DIR__ . '/debug_role.log', date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND);
    @file_put_contents(__DIR__ . '/debug_role.log', date('Y-m-d H:i:s') . " - POST data: " . json_encode($_POST) . "\n", FILE_APPEND);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Send JSON response and exit
 */
function json_response($status, $message, $data = []) {
    // Clear any output that might have been generated
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

// ==========================================
// 1. REQUEST METHOD VALIDATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response('error', 'Invalid request method');
}

// ==========================================
// 2. AUTHENTICATION CHECK
// ==========================================
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    json_response('error', 'Not authenticated. Please log in.');
}

// ==========================================
// 3. CSRF PROTECTION
// ==========================================
$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!$csrf_token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    json_response('error', 'Invalid security token. Please refresh the page.');
}

// ==========================================
// 4. RATE LIMITING (10 requests per minute)
// ==========================================
$rate_limit_key = 'role_change_' . $_SESSION['user_id'];

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'time' => time()];
}

// Reset counter after 1 minute
if (time() - $_SESSION[$rate_limit_key]['time'] > 60) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'time' => time()];
}

// Check limit
if ($_SESSION[$rate_limit_key]['count'] >= 10) {
    json_response('error', 'Too many requests. Please wait a moment before trying again.');
}

$_SESSION[$rate_limit_key]['count']++;

// ==========================================
// 5. AUTHORIZATION CHECK
// ==========================================
$current_role = $_SESSION['role_level'] ?? 'member';
$allowed_roles = ['super_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin'];

if (!in_array($current_role, $allowed_roles, true)) {
    json_response('error', 'Insufficient permissions to assign roles');
}

// ==========================================
// 6. INPUT VALIDATION & SANITIZATION
// ==========================================
// Validate user_id as positive integer
$user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($user_id === false || $user_id <= 0) {
    json_response('error', 'Invalid user ID');
}

// Sanitize and validate role
$new_role = trim($_POST['role_level'] ?? '');
if (empty($new_role)) {
    json_response('error', 'Role level is required');
}

// Whitelist of valid roles
$valid_roles = [
    'super_admin', 
    'national_admin', 
    'diocese_admin', 
    'archdeaconry_admin', 
    'deanery_admin', 
    'parish_admin', 
    'member'
];

if (!in_array($new_role, $valid_roles, true)) {
    json_response('error', 'Invalid role specified');
}

// ==========================================
// 7. ROLE HIERARCHY VALIDATION
// ==========================================
$role_hierarchy = [
    'member' => 1,
    'parish_admin' => 2,
    'deanery_admin' => 3,
    'archdeaconry_admin' => 4,
    'diocese_admin' => 5,
    'national_admin' => 6,
    'super_admin' => 7
];

$current_user_level = $role_hierarchy[$current_role] ?? 0;
$new_role_level = $role_hierarchy[$new_role] ?? 0;

// Only super_admin can assign super_admin or national_admin roles
if (($new_role === 'super_admin' || $new_role === 'national_admin') && $current_role !== 'super_admin') {
    json_response('error', 'Only Super Admins can assign Super Admin or National Admin roles');
}

// Users can only assign roles below their level (except super_admin can assign any)
if ($current_role !== 'super_admin' && $new_role_level >= $current_user_level) {
    json_response('error', 'You cannot assign roles at or above your privilege level');
}

// ==========================================
// 8. DATABASE OPERATIONS
// ==========================================
try {
    // Begin transaction for data consistency
    $pdo->beginTransaction();

    // Fetch the target user
    $stmt = $pdo->prepare("SELECT id, username, role_level FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $pdo->rollBack();
        json_response('error', 'User not found');
    }
    
    // Prevent users from modifying their own role (except super_admin can)
    if ($user_id == $_SESSION['user_id'] && $current_role !== 'super_admin') {
        $pdo->rollBack();
        json_response('error', 'You cannot change your own role');
    }

    // Check if role is actually changing
    if ($user['role_level'] === $new_role) {
        $pdo->rollBack();
        json_response('info', 'User already has this role', ['new_role' => $new_role]);
    }
    
    // Store old role for logging
    $old_role = $user['role_level'];
    
    // Update the role
    $update_stmt = $pdo->prepare("UPDATE users SET role_level = ?, updated_at = NOW() WHERE id = ?");
    $update_success = $update_stmt->execute([$new_role, $user_id]);
    
    if (!$update_success) {
        $pdo->rollBack();
        json_response('error', 'Failed to update role in database');
    }
    
    // ==========================================
    // 9. ACTIVITY LOGGING
    // ==========================================
    try {
        $stmt_log = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
            VALUES (?, 'role_change', ?, ?, NOW())
        ");
        
        $log_details = json_encode([
            'target_user_id' => $user_id,
            'target_username' => $user['username'],
            'old_role' => $old_role,
            'new_role' => $new_role,
            'changed_by_id' => $_SESSION['user_id'],
            'changed_by_username' => $_SESSION['username'] ?? 'unknown'
        ]);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt_log->execute([$_SESSION['user_id'], $log_details, $ip_address]);
    } catch (PDOException $log_error) {
        // Don't fail the transaction if logging fails, but log the error
        error_log("Activity logging failed: " . $log_error->getMessage());
    }
    
    // Commit transaction
    $pdo->commit();
    
    // ==========================================
    // 10. SUCCESS RESPONSE
    // ==========================================
    $role_names = [
        'super_admin' => 'Super Admin',
        'national_admin' => 'National Admin',
        'diocese_admin' => 'Diocese Admin',
        'archdeaconry_admin' => 'Archdeaconry Admin',
        'deanery_admin' => 'Deanery Admin',
        'parish_admin' => 'Parish Admin',
        'member' => 'Member'
    ];
    
    json_response('success', 'Role updated successfully', [
        'new_role' => $new_role,
        'new_role_display' => $role_names[$new_role] ?? 'Member',
        'old_role' => $old_role,
        'user_id' => $user_id,
        'username' => $user['username']
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the actual error for debugging
    error_log("Role assignment error: " . $e->getMessage());
    
    // Return generic error to user
    json_response('error', 'A database error occurred. Please try again.');
} catch (Exception $e) {
    // Rollback on any error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Unexpected error in role assignment: " . $e->getMessage());
    json_response('error', 'An unexpected error occurred. Please contact support.');
}