<?php
/**
 * ============================================
 * GET HIERARCHY OPTIONS (AJAX)
 * Returns hierarchy options based on selected level
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/scope_helpers.php';

start_secure_session();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get level parameter
$level = $_GET['level'] ?? '';

if (empty($level)) {
    echo json_encode(['success' => false, 'message' => 'Level not specified']);
    exit;
}

// Validate level
$valid_levels = ['province', 'diocese', 'archdeaconry', 'deanery', 'parish'];
if (!in_array($level, $valid_levels)) {
    echo json_encode(['success' => false, 'message' => 'Invalid level']);
    exit;
}

try {
    $options = [];
    
    switch ($level) {
        case 'province':
            $stmt = $pdo->query("SELECT province_id as id, province_name as name FROM provinces ORDER BY province_name");
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'diocese':
            $stmt = $pdo->query("SELECT diocese_id as id, diocese_name as name FROM dioceses ORDER BY diocese_name");
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'archdeaconry':
            $stmt = $pdo->query("SELECT archdeaconry_id as id, archdeaconry_name as name FROM archdeaconries ORDER BY archdeaconry_name");
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'deanery':
            $stmt = $pdo->query("SELECT deanery_id as id, deanery_name as name FROM deaneries ORDER BY deanery_name");
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'parish':
            $stmt = $pdo->query("SELECT parish_id as id, parish_name as name FROM parishes ORDER BY parish_name");
            $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
    
} catch (PDOException $e) {
    error_log("Hierarchy options error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>