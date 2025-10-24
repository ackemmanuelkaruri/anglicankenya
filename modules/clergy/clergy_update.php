<?php
/**
 * =============================================
 * CLERGY SECTION DEDICATED UPDATE/API HANDLER
 * =============================================
 * Handles all profile updates specifically for the 'clergy' section via AJAX.
 */

ob_start();

// --- DEPENDENCY INCLUSION ---
$project_root = dirname(dirname(__DIR__));

require_once $project_root . '/includes/init.php'; 
require_once $project_root . '/db.php';            

// Include the dedicated clergy handler
require_once __DIR__ . '/clergy_handler.php'; 

start_secure_session();

ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User is not logged in.", 401);
    }
    
    $user_id = $_SESSION['user_id'];
    $response = [];
    $section_type = 'clergy';

    // --- Action Routing for CRUD operations ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            // DELETE: Deletes a single role
            $response = deleteClergyRole($pdo, $user_id, $_POST['clergy_id'] ?? '');
        } else {
            // SAVE/UPDATE: Saves a single role
            $response = updateClergyRole($pdo, $user_id, $_POST);
        }
        
    } else {
        // GET (for initial role list or future edit data retrieval)
        $roles = getClergyRoles($pdo, $user_id);
        $response = ['success' => true, 'data' => $roles];
    }
    
    $response['section'] = $section_type;
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred during the request: ' . $e->getMessage(),
        'section' => 'clergy'
    ]);
}
exit;