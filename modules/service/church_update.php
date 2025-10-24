<?php
/**
 * =============================================
 * CHURCH SECTION DEDICATED UPDATE HANDLER
 * =============================================
 * Handles all profile updates specifically for the 'church' section via AJAX.
 *
 * NOTE: This file loads the core system and calls the dedicated handler.
 */

// Prevent any output before JSON response
ob_start();

// --- DEPENDENCY INCLUSION ---
// Get the project root directory (This path may need adjustment based on where you place this file)
// Assuming this file is in a subdirectory (e.g., modules/service/) and project_root is 2 levels up.
$project_root = dirname(dirname(__DIR__));

// Include core dependencies
require_once $project_root . '/includes/init.php'; // For start_secure_session, utility functions
require_once $project_root . '/includes/rbac.php'; // For RBAC or permissions logic
require_once $project_root . '/db.php';            // For PDO connection ($pdo)

// Include the dedicated church handler
// Note: __DIR__ assumes church_handler.php is in the same directory as this file
require_once __DIR__ . '/church_handler.php'; 

// Start session securely
start_secure_session();

// Clear any output buffer before sending JSON
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit;
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $section_type = 'church'; // Hardcode the section type for this dedicated router
    $response = [];

    // Call the dedicated handler function
    $response = updateChurchDetails($pdo, $user_id, $_POST, $_FILES);

    // Final check for valid response structure
    if (!is_array($response) || !isset($response['success'])) {
        $response = [
            'success' => false,
            'message' => 'Internal Server Error: Handler returned an invalid response structure.'
        ];
    }
    
    // Add section information to response
    $response['section'] = $section_type;
    
    // Log the final outcome (assuming logging functions are defined in init.php)
    if (function_exists('infoLog')) {
        infoLog("Section update complete", [
            'user_id' => $user_id,
            'section' => $section_type,
            'status' => $response['success'] ? 'SUCCESS' : 'FAILURE'
        ]);
    }
    
    // Send JSON response
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500); // Internal Server Error
    
    // Log the exception
    if (function_exists('errorLog')) {
        errorLog("Church section update exception: " . $e->getMessage(), [
            'user_id' => $user_id ?? 'N/A',
            'section' => 'church',
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => 'A critical error occurred: ' . $e->getMessage(),
        'section' => 'church'
    ]);
}