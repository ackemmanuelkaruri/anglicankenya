<?php
/**
 * Bulk Actions Handler
 * Supports: activate, suspend, delete, export
 */

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/activity_logger.php';

start_secure_session();

// Authentication and authorization check
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] === 'member') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate inputs
 $action = isset($_POST['action']) ? trim($_POST['action']) : '';
 $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];

if (empty($action) || empty($userIds) || !is_array($userIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Sanitize user IDs
 $userIds = array_map('intval', $userIds);
 $userIds = array_filter($userIds, function($id) { return $id > 0; });

if (empty($userIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No valid user IDs provided']);
    exit;
}

// Current user info
 $currentUserId = (int)$_SESSION['user_id'];
 $currentUserRole = $_SESSION['role_level'];
 $currentUserName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Initialize response
 $response = [
    'success' => false,
    'message' => '',
    'processed' => 0,
    'failed' => 0,
    'details' => []
];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get users info for logging and validation
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $usersQuery = "SELECT id, first_name, last_name, email, account_status, role_level FROM users WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($usersQuery);
    $stmt->execute($userIds);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) !== count($userIds)) {
        throw new Exception("Some users not found in database");
    }
    
    // Process each user
    foreach ($users as $user) {
        $userId = (int)$user['id'];
        $userName = $user['first_name'] . ' ' . $user['last_name'];
        
        // Skip if trying to modify self
        if ($userId === $currentUserId) {
            $response['failed']++;
            $response['details'][] = "Cannot perform action on yourself: $userName";
            continue;
        }
        
        // Check if current user has permission to modify this user
        if (!can_manage_user($currentUserRole, $currentUserId, $user['role_level'], $userId)) {
            $response['failed']++;
            $response['details'][] = "No permission to modify user: $userName";
            continue;
        }
        
        switch ($action) {
            case 'activate':
                // Update user status
                $updateStmt = $pdo->prepare("UPDATE users SET account_status = 'active' WHERE id = ?");
                $updateResult = $updateStmt->execute([$userId]);
                
                if ($updateResult) {
                    // Log activity
                    log_activity(
                        $currentUserId,
                        'user_status_change',
                        'users',
                        $userId,
                        ['account_status' => $user['account_status']],
                        ['account_status' => 'active'],
                        "Activated user account: $userName"
                    );
                    
                    $response['processed']++;
                    $response['details'][] = "Activated user: $userName";
                } else {
                    $response['failed']++;
                    $response['details'][] = "Failed to activate user: $userName";
                }
                break;
                
            case 'suspend':
                // Update user status
                $updateStmt = $pdo->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ?");
                $updateResult = $updateStmt->execute([$userId]);
                
                if ($updateResult) {
                    // Log activity
                    log_activity(
                        $currentUserId,
                        'user_status_change',
                        'users',
                        $userId,
                        ['account_status' => $user['account_status']],
                        ['account_status' => 'suspended'],
                        "Suspended user account: $userName"
                    );
                    
                    $response['processed']++;
                    $response['details'][] = "Suspended user: $userName";
                } else {
                    $response['failed']++;
                    $response['details'][] = "Failed to suspend user: $userName";
                }
                break;
                
            case 'delete':
                // Soft delete by marking as inactive
                $updateStmt = $pdo->prepare("UPDATE users SET account_status = 'inactive', deleted_at = NOW() WHERE id = ?");
                $updateResult = $updateStmt->execute([$userId]);
                
                if ($updateResult) {
                    // Log activity
                    log_activity(
                        $currentUserId,
                        'user_delete',
                        'users',
                        $userId,
                        ['account_status' => $user['account_status']],
                        ['account_status' => 'inactive', 'deleted_at' => date('Y-m-d H:i:s')],
                        "Deleted user account: $userName"
                    );
                    
                    $response['processed']++;
                    $response['details'][] = "Deleted user: $userName";
                } else {
                    $response['failed']++;
                    $response['details'][] = "Failed to delete user: $userName";
                }
                break;
                
            default:
                $response['failed']++;
                $response['details'][] = "Unknown action for user: $userName";
                break;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Set overall success message
    if ($response['processed'] > 0) {
        $response['success'] = true;
        $response['message'] = "Successfully processed {$response['processed']} users.";
        
        if ($response['failed'] > 0) {
            $response['message'] .= " Failed to process {$response['failed']} users.";
        }
    } else {
        $response['message'] = "No users were processed.";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log error
    error_log("Bulk action error: " . $e->getMessage());
    
    $response['success'] = false;
    $response['message'] = "An error occurred: " . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;