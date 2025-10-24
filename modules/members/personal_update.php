<?php
/**
 * =============================================
 * UNIFIED SECTION UPDATE HANDLER
 * =============================================
 * Handles all profile section updates via AJAX, located at:
 * /anglicankenya/modules/members/personal_update.php
 *
 * NOTE: The paths for 'require_once' assume this file is in 'modules/members/'
 * and its dependencies (init, rbac, db) are in 'includes/' and the handler
 * is in 'handlers/' - both relative to the project root.
 */

// Prevent any output before JSON response
ob_start();

// --- DEPENDENCY INCLUSION ---
// Get the project root directory (2 levels up from modules/members/)
$project_root = dirname(dirname(__DIR__));

// Include core dependencies
require_once $project_root . '/includes/init.php'; // For start_secure_session, utility functions
require_once $project_root . '/includes/rbac.php'; // For RBAC or permissions logic
require_once $project_root . '/db.php';            // For PDO connection ($pdo)

// Include the individual section handlers
require_once __DIR__ . '/personal_handler.php';
// REMOVED: require_once dirname(__DIR__) . '/service/church_handler.php';

// Add other section handlers here as you create them

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
    $section_type = $_POST['section_type'] ?? null;
    $response = [];

    if (empty($section_type)) {
        throw new Exception('Missing section_type parameter.');
    }

    // Determine which handler function to call based on the section
    switch ($section_type) {
        case 'personal':
            // personal_handler.php contains the updatePersonalInfo function
            $response = updatePersonalInfo($pdo, $user_id, $_POST, $_FILES);
            break;
            
        // REMOVED: case 'church': and its handler call
            
        case 'service':
            // service_handler.php contains the updateServiceDetails function
            $response = updateServiceDetails($pdo, $user_id, $_POST, $_FILES);
            break;

        case 'contact':
            // contact_handler.php contains the updateContactDetails function
            // $response = updateContactDetails($pdo, $user_id, $_POST, $_FILES);
            // break;

        case 'employment':
            // employment_handler.php contains the updateEmploymentDetails function
            // $response = updateEmploymentDetails($pdo, $user_id, $_POST, $_FILES);
            // break;

        case 'family':
            // family_handler.php contains the updateFamilyDetails function
            // $response = updateFamilyDetails($pdo, $user_id, $_POST, $_FILES);
            // break;

        case 'ministry':
            // ministry_handler.php contains the updateMinistryDetails function
            // $response = updateMinistryDetails($pdo, $user_id, $_POST, $_FILES);
            // break;

        default:
            $response = [
                'success' => false,
                'message' => 'Unknown section type: ' . htmlspecialchars($section_type)
            ];
            break;
    }
    
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
        errorLog("Section update exception: " . $e->getMessage(), [
            'user_id' => $user_id ?? 'N/A',
            'section' => $section_type ?? 'N/A',
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => 'A critical error occurred: ' . $e->getMessage(),
        'section' => $section_type ?? 'N/A'
    ]);
}