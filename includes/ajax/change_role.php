<?php
/**
 * ============================================
 * AJAX HANDLER - CHANGE USER ROLE
 * Secure role assignment with audit logging
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../rbac.php';
require_once __DIR__ . '/../middleware.php';
require_once __DIR__ . '/../security.php';

start_secure_session();

// Response array
$response = ['success' => false, 'message' => ''];

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Unauthorized. Please login.';
        echo json_encode($response);
        exit;
    }
    
    // Only POST allowed
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method.';
        echo json_encode($response);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON data.';
        echo json_encode($response);
        exit;
    }
    
    $user_id = (int)($input['user_id'] ?? 0);
    $new_role = trim($input['new_role'] ?? '');
    $csrf_token = $input['csrf_token'] ?? '';
    
    // CSRF validation
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $response['message'] = 'Invalid security token.';
        echo json_encode($response);
        exit;
    }
    
    // Validate inputs
    if ($user_id <= 0) {
        $response['message'] = 'Invalid user ID.';
        echo json_encode($response);
        exit;
    }
    
    $valid_roles = ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin', 'member'];
    if (!in_array($new_role, $valid_roles)) {
        $response['message'] = 'Invalid role.';
        echo json_encode($response);
        exit;
    }
    
    // Check permission - must be able to edit this user
    if (!can_edit($_SESSION, 'user', $user_id)) {
        $response['message'] = 'You do not have permission to change this user\'s role.';
        log_activity('UNAUTHORIZED_ROLE_CHANGE_ATTEMPT', null, null, [
            'target_user_id' => $user_id,
            'attempted_role' => $new_role
        ]);
        echo json_encode($response);
        exit;
    }
    
    // Prevent self-role changes
    if ($user_id == $_SESSION['user_id']) {
        $response['message'] = 'You cannot change your own role.';
        echo json_encode($response);
        exit;
    }
    
    // Get current role level for permission check
    $current_user_role = $_SESSION['role_level'] ?? 'member';
    
    // Only super_admin can assign super_admin or national_admin roles
    if (in_array($new_role, ['super_admin', 'national_admin']) && $current_user_role !== 'super_admin') {
        $response['message'] = 'Only Super Admins can assign Super Admin or National Admin roles.';
        echo json_encode($response);
        exit;
    }
    
    // Get target user's current data
    $stmt = $pdo->prepare("
        SELECT role_level, first_name, last_name, email 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit;
    }
    
    $old_role = $target_user['role_level'];
    
    // Check if trying to change another super_admin (only super_admin can do this)
    if ($old_role === 'super_admin' && $current_user_role !== 'super_admin') {
        $response['message'] = 'You cannot change a Super Admin\'s role.';
        echo json_encode($response);
        exit;
    }
    
    // Perform the role change
    $stmt = $pdo->prepare("
        UPDATE users 
        SET role_level = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$new_role, $user_id]);
    
    // Log the activity
    log_activity('ROLE_CHANGED', null, null, [
        'target_user_id' => $user_id,
        'target_user_name' => $target_user['first_name'] . ' ' . $target_user['last_name'],
        'target_user_email' => $target_user['email'],
        'old_role' => $old_role,
        'new_role' => $new_role,
        'changed_by' => $_SESSION['user_id']
    ]);
    
    // Invalidate target user's session to force re-login with new permissions
    // (Optional - depends on your security policy)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET force_logout = 1 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    
    $response['success'] = true;
    $response['message'] = 'Role updated successfully.';
    $response['new_role'] = $new_role;
    $response['old_role'] = $old_role;
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Role change error: " . $e->getMessage());
    $response['message'] = 'Database error occurred. Please try again.';
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Role change error: " . $e->getMessage());
    $response['message'] = 'An error occurred. Please try again.';
    echo json_encode($response);
}