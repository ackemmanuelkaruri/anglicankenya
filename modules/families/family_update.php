<?php
/**
 * =============================================
 * FAMILY SECTION DEDICATED UPDATE HANDLER
 * =============================================
 * Handles all CRUD operations for 'family' and 'dependents' via AJAX.
 */

ob_start();

$project_root = dirname(dirname(__DIR__));

require_once $project_root . '/includes/init.php'; 
require_once $project_root . '/db.php';            

require_once __DIR__ . '/family_handler.php'; 

start_secure_session();

ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User is not logged in.", 401);
    }
    
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? null;
    $response = [];

    switch ($action) {
        case 'request_link':
            $response = requestFamilyLink($pdo, $user_id, $_POST['target_username'] ?? '', $_POST['relationship_type'] ?? '');
            break;
            
        case 'add_dependent':
            $response = addDependent($pdo, $user_id, $_POST);
            break;
            
        case 'delete_relationship':
            $response = deleteUserRelationship($pdo, $user_id, $_POST['id'] ?? '');
            break;
            
        case 'delete_dependent':
            $response = deleteDependent($pdo, $user_id, $_POST['id'] ?? '');
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid or missing family action.'];
            break;
    }

    if (!is_array($response) || !isset($response['success'])) {
        $response = [
            'success' => false,
            'message' => 'Internal Server Error: Handler returned an invalid response structure.'
        ];
    }
    
    $response['section'] = 'family';
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred during the request: ' . $e->getMessage(),
        'section' => 'family'
    ]);
}
exit;