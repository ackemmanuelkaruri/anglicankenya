<?php
/**
 * Ministry Details Section Handler
 * Handles updates to user's ministry and department involvement
 */
session_start();
require_once '../db.php';
require_once '../includes/form_data.php';

// Check if user is logged in
$user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

/**
 * Delete all ministry details for a user including orphaned records
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array Response array with success status and message
 */
function deleteAllMinistryDetails($pdo, $user_id) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First, get all ministries and departments associated with this user
        $ministryNames = [];
        $departmentNames = [];
        
        // Get ministry names
        $stmt = $pdo->prepare("
            SELECT DISTINCT ministry_name 
            FROM user_ministry_department 
            WHERE user_id = ? AND ministry_name IS NOT NULL
        ");
        $stmt->execute([$user_id]);
        $ministryNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get department names
        $stmt = $pdo->prepare("
            SELECT DISTINCT department_name 
            FROM user_ministry_department 
            WHERE user_id = ? AND department_name IS NOT NULL
        ");
        $stmt->execute([$user_id]);
        $departmentNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete all user-ministry relationships
        $deleteStmt = $pdo->prepare("DELETE FROM user_ministry_department WHERE user_id = ?");
        $deleteStmt->execute([$user_id]);
        $userDeletedRows = $deleteStmt->rowCount();
        
        // Delete orphaned ministries (those not associated with any user)
        $ministryDeletedRows = 0;
        if (!empty($ministryNames)) {
            $placeholders = implode(',', array_fill(0, count($ministryNames), '?'));
            $stmt = $pdo->prepare("
                DELETE FROM ministries 
                WHERE name IN ($placeholders) 
                AND name NOT IN (
                    SELECT DISTINCT ministry_name 
                    FROM user_ministry_department 
                    WHERE ministry_name IS NOT NULL
                )
            ");
            $stmt->execute($ministryNames);
            $ministryDeletedRows = $stmt->rowCount();
        }
        
        // Delete orphaned departments (those not associated with any user)
        $departmentDeletedRows = 0;
        if (!empty($departmentNames)) {
            $placeholders = implode(',', array_fill(0, count($departmentNames), '?'));
            $stmt = $pdo->prepare("
                DELETE FROM departments 
                WHERE name IN ($placeholders) 
                AND name NOT IN (
                    SELECT DISTINCT department_name 
                    FROM user_ministry_department 
                    WHERE department_name IS NOT NULL
                )
            ");
            $stmt->execute($departmentNames);
            $departmentDeletedRows = $stmt->rowCount();
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = "All ministry assignments deleted successfully! ";
        $message .= "Removed: {$userDeletedRows} user assignments, ";
        $message .= "{$ministryDeletedRows} ministries, ";
        $message .= "{$departmentDeletedRows} departments.";
        
        return [
            'success' => true, 
            'message' => $message
        ];
        
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Delete ministry details error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error deleting ministry details: ' . $e->getMessage()];
    }
}

/**
 * Update ministry details for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param array $postData POST data from form
 * @return array Response array with success status and message
 */
function updateMinistryDetails($pdo, $user_id, $postData) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get current ministries and departments for this user to identify orphans later
        $currentMinistryNames = [];
        $currentDepartmentNames = [];
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT ministry_name 
            FROM user_ministry_department 
            WHERE user_id = ? AND ministry_name IS NOT NULL
        ");
        $stmt->execute([$user_id]);
        $currentMinistryNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT department_name 
            FROM user_ministry_department 
            WHERE user_id = ? AND department_name IS NOT NULL
        ");
        $stmt->execute([$user_id]);
        $currentDepartmentNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete all existing ministry involvements for this user
        $deleteStmt = $pdo->prepare("DELETE FROM user_ministry_department WHERE user_id = ?");
        $deleteStmt->execute([$user_id]);
        
        $insertedCount = 0;
        
        // Process departments
        if (isset($postData['departments']) && is_array($postData['departments'])) {
            foreach ($postData['departments'] as $departmentName) {
                if (!empty(trim($departmentName))) {
                    $departmentName = trim($departmentName);
                    
                    // Check if department exists in departments table
                    $checkStmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
                    $checkStmt->execute([$departmentName]);
                    $deptResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$deptResult) {
                        // Department doesn't exist, create it
                        $insertDeptStmt = $pdo->prepare("INSERT INTO departments (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
                        $insertDeptStmt->execute([$departmentName]);
                    }
                    
                    // Insert user-department relationship with the name
                    $insertRelStmt = $pdo->prepare("
                        INSERT INTO user_ministry_department (user_id, department_name) 
                        VALUES (?, ?)
                    ");
                    $insertRelStmt->execute([$user_id, $departmentName]);
                    $insertedCount++;
                }
            }
        }
        
        // Process ministries
        if (isset($postData['ministries']) && is_array($postData['ministries'])) {
            foreach ($postData['ministries'] as $ministryName) {
                if (!empty(trim($ministryName))) {
                    $ministryName = trim($ministryName);
                    
                    // Check if ministry exists in ministries table
                    $checkStmt = $pdo->prepare("SELECT id FROM ministries WHERE name = ?");
                    $checkStmt->execute([$ministryName]);
                    $minResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$minResult) {
                        // Ministry doesn't exist, create it
                        $insertMinStmt = $pdo->prepare("INSERT INTO ministries (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
                        $insertMinStmt->execute([$ministryName]);
                    }
                    
                    // Insert user-ministry relationship with the name
                    $insertRelStmt = $pdo->prepare("
                        INSERT INTO user_ministry_department (user_id, ministry_name) 
                        VALUES (?, ?)
                    ");
                    $insertRelStmt->execute([$user_id, $ministryName]);
                    $insertedCount++;
                }
            }
        }
        
        // Clean up orphaned ministries (those not associated with any user)
        if (!empty($currentMinistryNames)) {
            $placeholders = implode(',', array_fill(0, count($currentMinistryNames), '?'));
            $stmt = $pdo->prepare("
                DELETE FROM ministries 
                WHERE name IN ($placeholders) 
                AND name NOT IN (
                    SELECT DISTINCT ministry_name 
                    FROM user_ministry_department 
                    WHERE ministry_name IS NOT NULL
                )
            ");
            $stmt->execute($currentMinistryNames);
        }
        
        // Clean up orphaned departments (those not associated with any user)
        if (!empty($currentDepartmentNames)) {
            $placeholders = implode(',', array_fill(0, count($currentDepartmentNames), '?'));
            $stmt = $pdo->prepare("
                DELETE FROM departments 
                WHERE name IN ($placeholders) 
                AND name NOT IN (
                    SELECT DISTINCT department_name 
                    FROM user_ministry_department 
                    WHERE department_name IS NOT NULL
                )
            ");
            $stmt->execute($currentDepartmentNames);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = $insertedCount > 0 
            ? "Ministry details updated successfully! ({$insertedCount} assignments saved)" 
            : 'All ministry assignments cleared successfully!';
        
        return ['success' => true, 'message' => $message];
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Ministry details error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating ministry details: ' . $e->getMessage()];
    }
}

/**
 * Get user's current ministry information
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array User's ministry information
 */
function getUserMinistryInfo($pdo, $user_id) {
    try {
        // Get user's departments from user_ministry_department table
        $stmt = $pdo->prepare("
            SELECT department_name as name 
            FROM user_ministry_department
            WHERE user_id = ? AND department_name IS NOT NULL
            ORDER BY department_name
        ");
        $stmt->execute([$user_id]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user's ministries from user_ministry_department table
        $stmt = $pdo->prepare("
            SELECT ministry_name as name 
            FROM user_ministry_department
            WHERE user_id = ? AND ministry_name IS NOT NULL
            ORDER BY ministry_name
        ");
        $stmt->execute([$user_id]);
        $ministries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'departments' => $departments,
            'ministries' => $ministries
        ];
    } catch (Exception $e) {
        error_log("Error fetching ministry info: " . $e->getMessage());
        return ['departments' => [], 'ministries' => []];
    }
}

// Handle HTTP requests
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Return user's current ministry information
        $ministryInfo = getUserMinistryInfo($pdo, $user_id);
        echo json_encode(['success' => true, 'data' => $ministryInfo]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Debug: Log the POST data
        error_log("POST data: " . print_r($_POST, true));
        
        // Check if this is a delete request
        if (isset($_POST['action']) && $_POST['action'] === 'delete_all') {
            // Debug: Log the delete request
            error_log("Processing delete_all request for user: $user_id");
            
            // Delete all ministry assignments
            $result = deleteAllMinistryDetails($pdo, $user_id);
            echo json_encode($result);
        } else {
            // Update ministry details (normal save)
            $result = updateMinistryDetails($pdo, $user_id, $_POST);
            echo json_encode($result);
        }
        
    } else {
        // Unsupported method
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Unexpected error in ministry handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>