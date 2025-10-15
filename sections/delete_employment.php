<?php
/**
 * Employment Deletion Handler
 * Handles deletion of employment records from database
 */

require_once '../db.php';
require_once '../helpers/debug_helper.php';

session_start();

// CRITICAL: Ensure no output before this point
ob_start(); // Start output buffering to catch any unexpected output

// Set content type to JSON
header('Content-Type: application/json');

// Ensure no HTML errors are displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);  // NEVER display errors in production
ini_set('log_errors', 1);
ini_set('html_errors', 0);     // Disable HTML formatting for errors

try {
    // Clear any previous output that might interfere
    if (ob_get_length()) {
        ob_clean();
    }
    
    debugLog("=== EMPLOYMENT DELETE REQUEST STARTED ===");
    debugLogRequest('INFO');
    debugLogArray("POST Data", $_POST, 'DEBUG');
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorLog("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        throw new Exception('Invalid request method. Only POST is allowed.');
    }
    
    // Check if user ID is provided
    $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;
    if (!$userId || !is_numeric($userId)) {
        errorLog("Invalid or missing user ID", ['provided_user_id' => $userId]);
        throw new Exception('Valid User ID is required.');
    }
    
    // Check if employment ID is provided
    $employmentId = isset($_POST['employment_id']) ? trim($_POST['employment_id']) : null;
    if (!$employmentId || !is_numeric($employmentId)) {
        errorLog("Invalid or missing employment ID", ['provided_employment_id' => $employmentId]);
        throw new Exception('Valid Employment ID is required.');
    }
    
    infoLog("Processing delete request for employment ID: $employmentId by user ID: $userId");
    
    // Validate PDO connection
    if (!isset($pdo) || !$pdo) {
        errorLog("Database connection failed - PDO not available");
        throw new Exception('Database connection failed. Please try again later.');
    }
    
    // Test database connection
    try {
        $pdo->query('SELECT 1');
        debugLog("Database connection test successful");
    } catch (Exception $e) {
        errorLog("Database connection test failed", ['error' => $e->getMessage()]);
        throw new Exception('Database connection test failed. Please try again later.');
    }
    
    // Verify that the employment record exists and belongs to this user
    $checkStmt = $pdo->prepare("SELECT id, job_title, company, user_id FROM employment_history WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$employmentId, $userId]);
    $employmentRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employmentRecord) {
        errorLog("Employment record not found or doesn't belong to user", [
            'employment_id' => $employmentId, 
            'user_id' => $userId
        ]);
        throw new Exception('Employment record not found or you do not have permission to delete it.');
    }
    
    debugLog("Found employment record to delete:", [
        'id' => $employmentRecord['id'],
        'job_title' => $employmentRecord['job_title'],
        'company' => $employmentRecord['company']
    ]);
    
    // Perform the deletion
    $deleteStmt = $pdo->prepare("DELETE FROM employment_history WHERE id = ? AND user_id = ?");
    $deleteResult = $deleteStmt->execute([$employmentId, $userId]);
    
    if (!$deleteResult) {
        $errorInfo = $deleteStmt->errorInfo();
        errorLog("Delete operation failed", ['error' => $errorInfo]);
        throw new Exception('Failed to delete employment record: ' . $errorInfo[2]);
    }
    
    $deletedRows = $deleteStmt->rowCount();
    
    if ($deletedRows === 0) {
        warningLog("No rows were deleted", ['employment_id' => $employmentId, 'user_id' => $userId]);
        throw new Exception('No employment record was deleted. It may have already been removed.');
    }
    
    debugLogDBOperation('DELETE', $deletedRows, 'employment_history', 'INFO');
    infoLog("Successfully deleted employment record", [
        'employment_id' => $employmentId,
        'user_id' => $userId,
        'job_title' => $employmentRecord['job_title'],
        'company' => $employmentRecord['company']
    ]);
    
    debugLog("=== EMPLOYMENT DELETE REQUEST COMPLETED ===");
    
    // Success response
    $response = [
        'success' => true,
        'message' => "Employment record '{$employmentRecord['job_title']}' at '{$employmentRecord['company']}' has been successfully deleted.",
        'deleted_employment_id' => $employmentId,
        'remaining_count' => getRemainingEmploymentCount($pdo, $userId)
    ];
    
    // Add debug information if in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug'] = [
            'employment_id' => $employmentId,
            'user_id' => $userId,
            'deleted_record' => $employmentRecord,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Clean any buffered output before sending JSON
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clean any buffered output before sending error JSON
    if (ob_get_length()) {
        ob_clean();
    }
    
    $errorMessage = $e->getMessage();
    errorLog("Exception in employment delete handler", [
        'message' => $errorMessage,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage,
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Error $e) {
    // Clean any buffered output before sending error JSON
    if (ob_get_length()) {
        ob_clean();
    }
    
    errorLog("Fatal error in employment delete handler", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A fatal error occurred while deleting the employment record. Please check the server logs.',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}

/**
 * Get the remaining count of employment records for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return int Number of remaining employment records
 */
function getRemainingEmploymentCount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employment_history WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        debugLog("Error getting remaining employment count: " . $e->getMessage());
        return 0;
    }
}

// End output buffering and flush
ob_end_flush();
?>