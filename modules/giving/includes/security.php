<?php
/**
 * Security Functions for Giving Module
 */

/**
 * Verify CSRF token
 */
function verify_csrf_token() {
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

/**
 * Validate giving amount
 */
function validate_giving_amount($amount) {
    $amount = (float)$amount;
    
    if ($amount < 10) {
        return false;
    }
    
    if ($amount > 1000000) { // Set a reasonable maximum
        return false;
    }
    
    return true;
}

/**
 * Validate phone number for M-Pesa
 */
function validate_mpesa_phone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Kenyan phone number
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
        // Format: 7XXXXXXXX
        return '254' . $phone;
    } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
        // Format: 2547XXXXXXXX
        return $phone;
    } elseif (strlen($phone) == 10 && substr($phone, 0, 2) == '07') {
        // Format: 07XXXXXXXX
        return '254' . substr($phone, 1);
    } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+254') {
        // Format: +2547XXXXXXXX
        return substr($phone, 1);
    }
    
    return false;
}

/**
 * Log giving activity
 */
function log_giving_activity($action, $details = []) {
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO giving_activity_log 
            (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            json_encode($details),
            $ipAddress,
            $userAgent
        ]);
    } catch (PDOException $e) {
        error_log("Error logging giving activity: " . $e->getMessage());
    }
}

/**
 * Check if user has permission to access giving data
 */
function can_access_giving_data($parishId) {
    $roleLevel = $_SESSION['role_level'] ?? 'member';
    
    // Super admins and national admins can access all data
    if (in_array($roleLevel, ['super_admin', 'national_admin'])) {
        return true;
    }
    
    // Check if user belongs to the parish
    $userParishId = $_SESSION['parish_id'] ?? null;
    
    if ($userParishId && $userParishId == $parishId) {
        return true;
    }
    
    return false;
}

/**
 * Sanitize giving input
 */
function sanitize_giving_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_giving_input', $data);
    }
    
    // Remove any HTML tags and special characters
    $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    
    // Remove any potentially dangerous characters
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
    
    return $data;
}
?>