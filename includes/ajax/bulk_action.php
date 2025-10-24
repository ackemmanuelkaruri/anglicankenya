<?php
/**
 * ============================================
 * AJAX HANDLER - BULK ACTIONS
 * Handles bulk operations on multiple users
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

$response = ['success' => false, 'message' => '', 'affected_count' => 0];

try {
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Unauthorized. Please login.';
        echo json_encode($response);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method.';
        echo json_encode($response);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON data.';
        echo json_encode($response);
        exit;
    }
    
    $action = trim($input['action'] ?? '');
    $user_ids = $input['user_ids'] ?? [];
    $csrf_token = $input['csrf_token'] ?? '';
    
    // CSRF validation
    if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $response['message'] = 'Invalid security token.';
        echo json_encode($response);
        exit;
    }
    
    // Validate inputs
    if (empty($action)) {
        $response['message'] = 'No action specified.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($user_ids) || !is_array($user_ids)) {
        $response['message'] = 'No users selected.';
        echo json_encode($response);
        exit;
    }
    
    $affected_count = 0;
    $errors = [];
    
    // Process each user
    foreach ($user_ids as $user_id) {
        $user_id = (int)$user_id;
        
        // Skip invalid IDs
        if ($user_id <= 0) continue;
        
        // Skip self
        if ($user_id == $_SESSION['user_id']) {
            $errors[] = "Cannot perform action on yourself (ID: $user_id)";
            continue;
        }
        
        // Check permission for this user
        if (!can_edit($_SESSION, 'user', $user_id)) {
            $errors[] = "No permission for user ID: $user_id";
            continue;
        }
        
        // Perform action
        try {
            switch ($action) {
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE users SET account_status = 'active', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $affected_count += $stmt->rowCount();
                    log_activity('BULK_ACTIVATE', null, null, ['user_id' => $user_id]);
                    break;
                    
                case 'suspend':
                    $stmt = $pdo->prepare("UPDATE users SET account_status = 'suspended', force_logout = 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $affected_count += $stmt->rowCount();
                    log_activity('BULK_SUSPEND', null, null, ['user_id' => $user_id]);
                    break;
                    
                case 'delete':
                    // Additional check for delete
                    if (!can_delete($_SESSION, 'user', $user_id)) {
                        $errors[] = "Cannot delete user ID: $user_id";
                        continue 2;
                    }
                    
                    // Soft delete (set status to inactive)
                    $stmt = $pdo->prepare("UPDATE users SET account_status = 'inactive', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $affected_count += $stmt->rowCount();
                    log_activity('BULK_DELETE', null, null, ['user_id' => $user_id]);
                    break;
                    
                case 'export':
                    // For export, just count valid users
                    $affected_count++;
                    break;
                    
                default:
                    $errors[] = "Unknown action: $action";
                    break;
            }
        } catch (PDOException $e) {
            error_log("Bulk action error for user $user_id: " . $e->getMessage());
            $errors[] = "Database error for user ID: $user_id";
        }
    }
    
    if ($affected_count > 0) {
        $response['success'] = true;
        $response['message'] = "Action completed successfully.";
        $response['affected_count'] = $affected_count;
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
    } else {
        $response['message'] = "No users were affected. " . implode('; ', $errors);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Bulk action error: " . $e->getMessage());
    $response['message'] = 'An error occurred. Please try again.';
    echo json_encode($response);
}