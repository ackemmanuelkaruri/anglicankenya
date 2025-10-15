<?php
session_start();
require_once '../config/database.php';
require_once '../includes/admin_auth.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$requestId = intval($_POST['request_id'] ?? 0);
$response = $_POST['response'] ?? '';
$adminId = $_SESSION['user_id'];

if (!$requestId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

try {
    // Get the activation request
    $requestStmt = $pdo->prepare("
        SELECT * FROM minor_activation_requests WHERE id = ?
    ");
    $requestStmt->execute([$requestId]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Activation request not found');
    }
    
    if ($action === 'approve') {
        // Approve the request
        $updateStmt = $pdo->prepare("
            UPDATE minor_activation_requests 
            SET status = 'approved', admin_response = ?, processed_by_admin_id = ?, processed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$response, $adminId, $requestId]);
        
        // Update minor profile
        $profileStmt = $pdo->prepare("
            UPDATE minor_profiles 
            SET admin_approval_status = 'approved', admin_approval_date = CURRENT_TIMESTAMP, 
                approved_by_admin_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $profileStmt->execute([$adminId, $request['minor_profile_id']]);
        
        // Unlock all tabs for the minor
        $unlockStmt = $pdo->prepare("
            UPDATE minor_tab_permissions 
            SET is_accessible = 1, unlocked_at = CURRENT_TIMESTAMP, unlocked_by_admin_id = ?
            WHERE minor_profile_id = ?
        ");
        $unlockStmt->execute([$adminId, $request['minor_profile_id']]);
        
        // Create user account for the minor (you can implement this)
        // createMinorUserAccount($pdo, $request['minor_profile_id']);
        
        echo json_encode(['success' => true, 'message' => 'Activation request approved successfully']);
        
    } elseif ($action === 'reject') {
        // Reject the request
        $updateStmt = $pdo->prepare("
            UPDATE minor_activation_requests 
            SET status = 'rejected', admin_response = ?, processed_by_admin_id = ?, processed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$response, $adminId, $requestId]);
        
        // Update minor profile
        $profileStmt = $pdo->prepare("
            UPDATE minor_profiles 
            SET admin_approval_status = 'rejected', admin_approval_date = CURRENT_TIMESTAMP, 
                approved_by_admin_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $profileStmt->execute([$adminId, $request['minor_profile_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Activation request rejected successfully']);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>