<?php
/**
 * Enhanced Database Service Data Clear Handler with Certificate Clearing
 * File: includes/database_clear.php
 * Now handles both service data and certificate clearing
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header early
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    // Include helper files (same as your existing code)
    $helperIncluded = false;
    $dbIncluded = false;
    
    $possibleHelperPaths = [
        dirname(__FILE__) . '/debug_helper.php',
        dirname(__FILE__) . '/../helpers/debug_helper.php',
        dirname(__FILE__) . '/helpers/debug_helper.php',
        dirname(__FILE__) . '/../helpers/debug.php',
        dirname(__FILE__) . '/debug.php'
    ];
    
    foreach ($possibleHelperPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $helperIncluded = true;
            break;
        }
    }
    
    if (!$helperIncluded) {
        if (!function_exists('debugLog')) {
            function debugLog($message) {
                error_log("DEBUG: " . $message);
            }
        }
        if (!function_exists('errorLog')) {
            function errorLog($message, $data = []) {
                error_log("ERROR: " . $message . " - " . json_encode($data));
            }
        }
    }
    
    $possibleDbPaths = [
        dirname(__FILE__) . '/db.php',
        dirname(__FILE__) . '/../db.php',
        dirname(__FILE__) . '/../config/db.php',
        dirname(__FILE__) . '/config/db.php',
        dirname(__FILE__) . '/../database.php'
    ];
    
    foreach ($possibleDbPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $dbIncluded = true;
            break;
        }
    }
    
    if (!$dbIncluded) {
        throw new Exception('Database configuration file not found');
    }
    
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Validate required parameters
    if (!isset($_POST['action'])) {
        throw new Exception('Action not specified');
    }

    $userId = $_SESSION['user_id'];
    $action = $_POST['action'];
    
    // Handle different actions
    switch ($action) {
        case 'clear_service_data':
            handleServiceDataClear($pdo, $userId);
            break;
        case 'clear_certificate':
            handleCertificateClear($pdo, $userId);
            break;
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    debugLog("Database clear error: " . $e->getMessage());
    
    if (function_exists('errorLog')) {
        errorLog("Database clear failed", [
            'user_id' => $_SESSION['user_id'] ?? 'unknown',
            'action' => $_POST['action'] ?? 'unknown',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Handle service data clearing (existing functionality)
 */
function handleServiceDataClear($pdo, $userId) {
    if (!isset($_POST['old_service'])) {
        throw new Exception('Old service not specified');
    }

    $oldService = trim($_POST['old_service']);
    
    debugLog("Service data clear request - User: $userId, Old Service: $oldService");
    
    // Validate the old service value
    $validServices = ['english', 'kikuyu', 'teens', 'sunday_school'];
    if (!in_array($oldService, $validServices)) {
        throw new Exception('Invalid service type: ' . $oldService);
    }
    
    // Begin transaction for data integrity
    $pdo->beginTransaction();
    
    try {
        // Clear service-specific data based on old service
        $clearedFields = [];
        
        switch ($oldService) {
            case 'english':
                $clearedFields = clearEnglishServiceData($pdo, $userId);
                break;
            case 'kikuyu':
                $clearedFields = clearKikuyuServiceData($pdo, $userId);
                break;
            case 'teens':
                $clearedFields = clearTeensServiceData($pdo, $userId);
                break;
            case 'sunday_school':
                $clearedFields = clearSundaySchoolServiceData($pdo, $userId);
                break;
        }
        
        // Commit transaction
        $pdo->commit();
        
        debugLog("Service data cleared successfully for user: $userId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Service data cleared successfully',
            'cleared_service' => $oldService,
            'cleared_fields' => $clearedFields
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle certificate clearing (NEW FUNCTIONALITY)
 */
function handleCertificateClear($pdo, $userId) {
    if (!isset($_POST['certificate_type'])) {
        throw new Exception('Certificate type not specified');
    }

    $certificateType = trim($_POST['certificate_type']);
    
    debugLog("Certificate clear request - User: $userId, Type: $certificateType");
    
    // Validate certificate type
    $validTypes = ['baptism', 'confirmation'];
    if (!in_array($certificateType, $validTypes)) {
        throw new Exception('Invalid certificate type: ' . $certificateType);
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        $clearedData = clearCertificateData($pdo, $userId, $certificateType);
        
        // Commit transaction
        $pdo->commit();
        
        debugLog("Certificate cleared successfully - User: $userId, Type: $certificateType");
        
        echo json_encode([
            'success' => true,
            'message' => 'Certificate cleared successfully',
            'certificate_type' => $certificateType,
            'cleared_file' => $clearedData['file_path'] ?? null,
            'cleared_fields' => $clearedData['fields'] ?? []
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Clear certificate data and files
 */
function clearCertificateData($pdo, $userId, $certificateType) {
    $columns = getTableColumns($pdo, 'users');
    $clearedData = ['fields' => [], 'file_path' => null];
    
    // Determine the certificate field name
    $certificateField = $certificateType . '_certificate';
    
    if (!in_array($certificateField, $columns)) {
        debugLog("Certificate field '$certificateField' doesn't exist in database - this is OK");
        return $clearedData;
    }
    
    // Get current certificate file path before clearing
    $stmt = $pdo->prepare("SELECT $certificateField FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $currentFilePath = $currentData[$certificateField] ?? null;
    
    if ($currentFilePath) {
    // Pick correct upload folder based on certificate type
    if ($certificateType === 'baptism') {
        $uploadDir = dirname(__FILE__) . '/../uploads/baptism_certificate/';
    } elseif ($certificateType === 'confirmation') {
        $uploadDir = dirname(__FILE__) . '/../uploads/confirmation_certificates/';
    } else {
        $uploadDir = dirname(__FILE__) . '/../uploads/certificates/'; // fallback
    }

    $fullFilePath = $uploadDir . $currentFilePath;

    if (file_exists($fullFilePath)) {
        if (unlink($fullFilePath)) {
            debugLog("Certificate file deleted: $fullFilePath");
            $clearedData['file_path'] = $currentFilePath;
        } else {
            debugLog("Failed to delete certificate file: $fullFilePath");
        }
    } else {
        debugLog("Certificate file not found for deletion: $fullFilePath");
    }
}

    
    // Clear the database field
    $fieldsToUpdate = ["$certificateField = NULL"];
    $clearedData['fields'][] = $certificateField;
    
    // Add updated_at if it exists
    if (in_array('updated_at', $columns)) {
        $fieldsToUpdate[] = 'updated_at = NOW()';
    }
    
    $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$userId]);
    
    if (!$success) {
        throw new Exception("Failed to clear $certificateType certificate data");
    }
    
    // Log the action
    logCertificateClear($pdo, $userId, $certificateType, $currentFilePath);
    
    return $clearedData;
}

/**
 * Log certificate clearing action
 */
function logCertificateClear($pdo, $userId, $certificateType, $filePath) {
    try {
        $tableExists = false;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'activity_log'");
            $tableExists = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            // Ignore error, table doesn't exist
        }
        
        if ($tableExists) {
            $sql = "INSERT INTO activity_log (user_id, action, details, timestamp) 
                    VALUES (?, ?, ?, NOW())";
            
            $details = json_encode([
                'certificate_type' => $certificateType,
                'cleared_file' => $filePath,
                'reason' => 'status_changed_to_no'
            ]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, 'certificate_cleared', $details]);
        } else {
            debugLog("Certificate cleared - User: $userId, Type: $certificateType, File: $filePath");
        }
    } catch (Exception $e) {
        debugLog("Failed to log certificate clear: " . $e->getMessage());
    }
}

// Include all your existing service clearing functions here...
function clearEnglishServiceData($pdo, $userId) {
    $columns = getTableColumns($pdo, 'users');
    $fieldsToUpdate = [];
    $clearedFields = [];
    
    if (in_array('english_service_team', $columns)) {
        $fieldsToUpdate[] = 'english_service_team = NULL';
        $clearedFields[] = 'english_service_team';
    }
    
    if (empty($fieldsToUpdate)) {
        debugLog("No English service fields found to clear");
        return ['message' => 'No English service columns found'];
    }
    
    if (in_array('updated_at', $columns)) {
        $fieldsToUpdate[] = 'updated_at = NOW()';
    }
    
    $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$userId]);
    
    if (!$success) {
        throw new Exception('Failed to clear English service data');
    }
    
    logServiceDataClear($pdo, $userId, 'english', $clearedFields);
    
    return $clearedFields;
}

function clearKikuyuServiceData($pdo, $userId) {
    $columns = getTableColumns($pdo, 'users');
    $fieldsToUpdate = [];
    $clearedFields = [];
    
    if (in_array('kikuyu_cell_group', $columns)) {
        $fieldsToUpdate[] = 'kikuyu_cell_group = NULL';
        $clearedFields[] = 'kikuyu_cell_group';
    }
    
    if (in_array('family_group', $columns)) {
        $fieldsToUpdate[] = 'family_group = NULL';
        $clearedFields[] = 'family_group';
    }
    
    if (empty($fieldsToUpdate)) {
        debugLog("No Kikuyu service fields found to clear");
        return ['message' => 'No Kikuyu service columns found'];
    }
    
    if (in_array('updated_at', $columns)) {
        $fieldsToUpdate[] = 'updated_at = NOW()';
    }
    
    $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$userId]);
    
    if (!$success) {
        throw new Exception('Failed to clear Kikuyu service data');
    }
    
    logServiceDataClear($pdo, $userId, 'kikuyu', $clearedFields);
    
    return $clearedFields;
}

function clearTeensServiceData($pdo, $userId) {
    $columns = getTableColumns($pdo, 'users');
    $fieldsToUpdate = [];
    $clearedFields = [];
    
    $possibleFields = ['church_department', 'ministry_committee', 'departments', 'ministries'];
    
    foreach ($possibleFields as $field) {
        if (in_array($field, $columns)) {
            $fieldsToUpdate[] = "$field = NULL";
            $clearedFields[] = $field;
        }
    }
    
    if (empty($fieldsToUpdate)) {
        debugLog("No Teens service fields to clear");
        return [];
    }
    
    $fieldsToUpdate[] = 'updated_at = NOW()';
    
    $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$userId]);
    
    if (!$success) {
        throw new Exception('Failed to clear Teens service data');
    }
    
    logServiceDataClear($pdo, $userId, 'teens', $clearedFields);
    
    return $clearedFields;
}

function clearSundaySchoolServiceData($pdo, $userId) {
    $columns = getTableColumns($pdo, 'users');
    $fieldsToUpdate = [];
    $clearedFields = [];
    
    $possibleFields = ['church_department', 'ministry_committee', 'departments', 'ministries'];
    
    foreach ($possibleFields as $field) {
        if (in_array($field, $columns)) {
            $fieldsToUpdate[] = "$field = NULL";
            $clearedFields[] = $field;
        }
    }
    
    if (empty($fieldsToUpdate)) {
        debugLog("No Sunday School service fields to clear");
        return [];
    }
    
    $fieldsToUpdate[] = 'updated_at = NOW()';
    
    $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$userId]);
    
    if (!$success) {
        throw new Exception('Failed to clear Sunday School service data');
    }
    
    logServiceDataClear($pdo, $userId, 'sunday_school', $clearedFields);
    
    return $clearedFields;
}

function getTableColumns($pdo, $tableName) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $columns;
    } catch (Exception $e) {
        debugLog("Error getting table columns: " . $e->getMessage());
        return [];
    }
}

function logServiceDataClear($pdo, $userId, $service, $fields) {
    try {
        $tableExists = false;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'activity_log'");
            $tableExists = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            // Ignore error, table doesn't exist
        }
        
        if ($tableExists) {
            $sql = "INSERT INTO activity_log (user_id, action, details, timestamp) 
                    VALUES (?, ?, ?, NOW())";
            
            $details = json_encode([
                'cleared_service' => $service,
                'cleared_fields' => $fields,
                'reason' => 'service_change'
            ]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, 'service_data_cleared', $details]);
        } else {
            debugLog("Service data cleared - User: $userId, Service: $service, Fields: " . implode(', ', $fields));
        }
    } catch (Exception $e) {
        debugLog("Failed to log service data clear: " . $e->getMessage());
    }
}
?>