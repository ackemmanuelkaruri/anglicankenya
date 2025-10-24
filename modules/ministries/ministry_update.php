<?php
/**
 * =============================================
 * MINISTRY SECTION DEDICATED UPDATE HANDLER (ROUTER)
 * =============================================
 * Handles all profile updates specifically for the 'ministry' section via AJAX.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Prevent any output before JSON response
ob_start();

// --- DEPENDENCY INCLUSION ---
$project_root = dirname(dirname(__DIR__));

// UNCOMMENT THESE - You need the database connection!
require_once $project_root . '/includes/init.php'; 
require_once $project_root . '/db.php'; // For PDO connection ($pdo)

// Include the dedicated ministry handler
require_once $project_root . '/modules/ministries/ministry_handler.php'; 

// Check if a session exists (if not, start one)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$section_type = 'ministry';

// Clear any output buffer and set JSON header
ob_clean();
header('Content-Type: application/json');

try {
    if (!$user_id) {
        throw new Exception('User not logged in. Session expired.');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    
    // Get JSON input from request body (since JS sends JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Check for delete action
    if (isset($data['action']) && $data['action'] === 'delete_all') {
        $response = deleteAllMinistryDetails($pdo, $user_id);
    } else {
        // Normal update/save action
        $response = updateMinistryDetails($pdo, $user_id, $data);
    }
    
    if (!is_array($response) || !isset($response['success'])) {
        $response = ['success' => false, 'message' => 'Internal Server Error: Invalid response structure.'];
    }
    
    $response['section'] = $section_type;
    
    echo json_encode($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Request Failed: ' . $e->getMessage(),
        'section' => $section_type,
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

ob_end_flush();