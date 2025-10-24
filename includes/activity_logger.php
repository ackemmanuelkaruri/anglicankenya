<?php
/**
 * Quick Status Update Handler
 * For AJAX requests from the user list
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
 $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
 $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($userId <= 0 || empty($newStatus)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Validate status
 $valid_statuses = ['active', 'pending', 'suspended', 'inactive'];
if (!in_array($newStatus, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Current user info
 $currentUserId = (int)$_SESSION['user_id'];
 $currentUserRole = $_SESSION['role_level'];

// Check if trying to modify self
if ($userId === $currentUserId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cannot change your own status']);
    exit;
}

try {
    // Get user info
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, account_status, role_level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if current user has permission to modify this user
    if (!can_manage_user($currentUserRole, $currentUserId, $user['role_level'], $userId)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No permission to modify this user']);
        exit;
    }
    
    // Get user's full name
    $userName = $user['first_name'] . ' ' . $user['last_name'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update user status
    $updateStmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $updateResult = $updateStmt->execute([$newStatus, $userId]);
    
    if (!$updateResult) {
        throw new Exception("Failed to update user status");
    }
    
    // Log activity
    log_activity(
        $currentUserId,
        'user_status_change',
        'users',
        $userId,
        ['account_status' => $user['account_status']],
        ['account_status' => $newStatus],
        "Changed user status from {$user['account_status']} to $newStatus for: $userName"
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Send email notification if enabled
    if (in_array($newStatus, ['active', 'suspended']) && !empty($user['email'])) {
        send_status_change_email($user['email'], $userName, $newStatus);
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "User status updated successfully",
        'new_status' => $newStatus,
        'user_name' => $userName
    ]);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log error
    error_log("Status update error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}

/**
 * Helper function to send status change email notification
 */
function send_status_change_email($email, $name, $newStatus) {
    // This is a placeholder function. In a real implementation, you would use
    // PHP's mail() function or a library like PHPMailer to send emails.
    
    $subject = "Your Account Status Has Been Updated";
    
    $message = "Dear $name,\n\n";
    $message .= "Your account status has been updated to: " . ucfirst($newStatus) . ".\n\n";
    
    if ($newStatus === 'active') {
        $message .= "You can now log in to your account.\n\n";
    } elseif ($newStatus === 'suspended') {
        $message .= "Your account has been suspended and you cannot log in at this time.\n\n";
    }
    
    $message .= "If you believe this is an error, please contact the system administrator.\n\n";
    $message .= "Thank you,\n";
    $message .= "Church Management System";
    
    // In a real implementation, uncomment the following lines:
    // $headers = "From: no-reply@yourdomain.com\r\n";
    // $headers .= "Reply-To: admin@yourdomain.com\r\n";
    // $headers .= "X-Mailer: PHP/" . phpversion();
    // 
    // mail($email, $subject, $message, $headers);
    
    // For now, we'll just log that we would have sent an email
    error_log("Would send email to $email: Status changed to $newStatus");
}