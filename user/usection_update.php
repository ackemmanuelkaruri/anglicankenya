<?php
require_once '../db.php';
require_once '../helpers/file_upload_helper.php';
require_once '../helpers/debug_helper.php';
require_once '../sections/upersonal_section.php';
require_once '../sections/uchurch_section.php';
require_once '../sections/uemployment_section.php';
require_once '../sections/uclergy_section.php';
require_once '../sections/uministry_section.php';
require_once '../sections/uleadership_section.php';
// ADD THIS LINE FOR FAMILY SECTION
require_once '../handlers/family_handler.php';

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
    
    // Log the incoming request with your debug helper
    debugLog("=== NEW SECTION UPDATE REQUEST ===");
    debugLogRequest('INFO');
    debugLogArray("POST Data", $_POST, 'DEBUG');
    debugLogArray("FILES Data", $_FILES, 'DEBUG');
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorLog("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        throw new Exception('Invalid request method. Only POST is allowed.');
    }
    
    // Check if user ID is provided
    $id = isset($_POST['id']) ? trim($_POST['id']) : null;
    if (!$id || !is_numeric($id)) {
        errorLog("Invalid or missing user ID", ['provided_id' => $id]);
        throw new Exception('Valid User ID is required.');
    }
    
    infoLog("Processing request for user ID: $id");
    
    // Check for section_type or section parameter
    $sectionType = $_POST['section_type'] ?? $_POST['section'] ?? null;
    if (!$sectionType) {
        errorLog("Missing section type parameter");
        throw new Exception('Section type is required.');
    }
    
    infoLog("Processing section: $sectionType for user ID: $id");
    
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
    
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        errorLog("User not found in database", ['user_id' => $id]);
        throw new Exception('User not found in database.');
    }
    
    debugLog("User exists in database - proceeding with update");
    
    $response = ['success' => false, 'message' => 'Unknown error occurred.'];
    
    // Process based on section type
    switch (strtolower($sectionType)) {
        case 'personal':
            infoLog("Starting personal section update");
            
            // Check if function exists
            if (!function_exists('updatePersonalInfo')) {
                errorLog("updatePersonalInfo function not found");
                throw new Exception('Personal section handler not available.');
            }
            
            debugLog("Calling updatePersonalInfo function");
            $response = updatePersonalInfo($pdo, $id, $_POST, $_FILES);
            debugLogArray("updatePersonalInfo response", $response, 'INFO');
            break;
            
        case 'church':
            infoLog("Starting church section update");
            if (!function_exists('updateChurchDetails')) {
                errorLog("updateChurchDetails function not found");
                throw new Exception('Church section handler not available.');
            }
            $response = updateChurchDetails($pdo, $id, $_POST, $_FILES);
            break;
            
        case 'employment':
            infoLog("Starting employment section update");
            
            // Check if function exists
            if (!function_exists('updateEmploymentDetails')) {
                errorLog("updateEmploymentDetails function not found");
                throw new Exception('Employment section handler not available.');
            }
            
            debugLog("Calling updateEmploymentDetails function");
            
            // Call the employment handler directly - let it handle its own validation
            debugLog("Proceeding with employment update - handler will validate");
            $response = updateEmploymentDetails($pdo, $id, $_POST);
            debugLogArray("updateEmploymentDetails response", $response, 'INFO');
            break;
            
        case 'clergy':
            infoLog("Starting clergy section update");
            if (!function_exists('updateClergyDetails')) {
                errorLog("updateClergyDetails function not found");
                throw new Exception('Clergy section handler not available.');
            }
            $response = updateClergyDetails($pdo, $id, $_POST);
            break;
            
        case 'ministry':
            infoLog("Starting ministry section update");
            if (!function_exists('updateMinistryDetails')) {
                errorLog("updateMinistryDetails function not found");
                throw new Exception('Ministry section handler not available.');
            }
            $response = updateMinistryDetails($pdo, $id, $_POST);
            break;
            
        case 'leadership':
            infoLog("Starting leadership section update");
            if (!function_exists('updateLeadershipDetails')) {
                errorLog("updateLeadershipDetails function not found");
                throw new Exception('Leadership section handler not available.');
            }
            $response = updateLeadershipDetails($pdo, $id, $_POST);
            break;
            
        // ADD THIS CASE FOR FAMILY SECTION
        case 'family':
            infoLog("Starting family section update");
            if (!function_exists('updateFamilyInfo')) {
                errorLog("updateFamilyInfo function not found");
                throw new Exception('Family section handler not available.');
            }
            $response = updateFamilyInfo($pdo, $id, $_POST, $_FILES);
            break;
            
        default:
            // UPDATE THIS ERROR MESSAGE TO INCLUDE FAMILY
            errorLog("Invalid section type provided", ['section_type' => $sectionType]);
            throw new Exception("Invalid section type: '$sectionType'. Allowed types: personal, church, employment, clergy, ministry, leadership, family");
    }
    
    debugLogArray("Final response before output", $response, 'INFO');
    
    // Validate response format
    if (!is_array($response) || !isset($response['success'])) {
        errorLog("Invalid response format from section handler", ['response' => $response]);
        throw new Exception('Invalid response format from section handler.');
    }
    
    if ($response['success']) {
        infoLog("Update completed successfully", ['section' => $sectionType, 'user_id' => $id]);
    } else {
        warningLog("Update failed", ['section' => $sectionType, 'user_id' => $id, 'message' => $response['message']]);
    }
    
    // Add debug information if in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug'] = [
            'section' => $sectionType,
            'user_id' => $id,
            'post_keys' => array_keys($_POST),
            'timestamp' => date('Y-m-d H:i:s'),
            'handler_called' => true
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
    errorLog("Exception in main update handler", [
        'message' => $errorMessage,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
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
    
    errorLog("Fatal error in main update handler", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'A fatal error occurred. Please check the server logs.',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
// End output buffering and flush
ob_end_flush();
?>