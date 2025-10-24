<?php
/**
 * =============================================
 * LEADERSHIP SECTION DEDICATED UPDATE HANDLER
 * =============================================
 * Handles all profile updates specifically for the 'leadership' section via AJAX.
 */

ob_start();

$project_root = dirname(dirname(__DIR__));

require_once $project_root . '/includes/init.php'; 
require_once $project_root . '/db.php';            

require_once __DIR__ . '/leadership_handler.php'; 

start_secure_session();

ob_clean();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User is not logged in.", 401);
    }
    
    $user_id = $_SESSION['user_id'];
    $response = [];

    // Call the dedicated handler function
    $response = updateLeadershipDetails($pdo, $user_id, $_POST);

    if (!is_array($response) || !isset($response['success'])) {
        $response = [
            'success' => false,
            'message' => 'Internal Server Error: Handler returned an invalid response structure.'
        ];
    }
    
    $response['section'] = 'leadership';
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred during the update: ' . $e->getMessage(),
        'section' => 'leadership'
    ]);
}
exit;