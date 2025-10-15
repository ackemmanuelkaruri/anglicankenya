<?php
/**
 * Leadership Details Section Handler
 * Handles updates to user's leadership positions and roles
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
 * Update leadership details for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $id User ID
 * @param array $postData POST data from form
 * @return array Response array with success status and message
 */
function updateLeadershipDetails($pdo, $id, $postData) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete existing leadership records for this user
        $deleteStmt = $pdo->prepare("DELETE FROM user_leadership WHERE user_id = ?");
        $deleteStmt->execute([$id]);
        
        $insertedRecords = 0;
        
        // Insert new leadership records
        if (isset($postData['leadership_type']) && is_array($postData['leadership_type'])) {
            $insertStmt = $pdo->prepare("INSERT INTO user_leadership (user_id, leadership_type, department, ministry, role, other_role, from_date, to_date, is_current) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            for ($i = 0; $i < count($postData['leadership_type']); $i++) {
                $leadershipType = $postData['leadership_type'][$i] ?? null;
                $department = $postData['leadership_department'][$i] ?? null;
                $ministry = $postData['leadership_ministry'][$i] ?? null;
                $role = $postData['leadership_role'][$i] ?? null;
                $otherRole = $postData['other_leadership_role'][$i] ?? null;
                $fromDate = $postData['leadership_from_date'][$i] ?? null;
                $toDate = $postData['leadership_to_date'][$i] ?? null;
                $isCurrent = isset($postData['is_current_leadership']) && in_array($i, $postData['is_current_leadership']) ? 1 : 0;
                
                // Skip empty records
                if (empty($leadershipType) || empty($role)) {
                    continue;
                }
                
                // If current position, set to_date to null
                if ($isCurrent) {
                    $toDate = null;
                }
                
                // Validate leadership data
                $validationErrors = validateSingleLeadershipRecord($leadershipType, $department, $ministry, $role, $fromDate, $toDate, $isCurrent);
                if (!empty($validationErrors)) {
                    throw new Exception("Validation error for record " . ($i + 1) . ": " . implode(', ', $validationErrors));
                }
                
                $success = $insertStmt->execute([$id, $leadershipType, $department, $ministry, $role, $otherRole, $fromDate, $toDate, $isCurrent]);
                if (!$success) {
                    throw new Exception('Failed to insert leadership record');
                }
                
                $insertedRecords++;
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = $insertedRecords > 0 
            ? "Leadership details updated successfully! ($insertedRecords records saved)" 
            : 'All leadership roles cleared successfully!';
        
        return ['success' => true, 'message' => $message];
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Leadership details error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating leadership details: ' . $e->getMessage()];
    }
}

/**
 * Validate a single leadership record
 * 
 * @param string $leadershipType Type of leadership
 * @param string $department Department (if applicable)
 * @param string $ministry Ministry (if applicable)
 * @param string $role Leadership role
 * @param string $fromDate Start date
 * @param string $toDate End date
 * @param bool $isCurrent Whether this is a current position
 * @return array Array of validation errors
 */
function validateSingleLeadershipRecord($leadershipType, $department, $ministry, $role, $fromDate, $toDate, $isCurrent) {
    $errors = [];
    
    // Required fields validation
    if (empty($leadershipType)) {
        $errors[] = "Leadership type is required";
    }
    if (empty($role)) {
        $errors[] = "Leadership role is required";
    }
    
    // Type-specific validation
    if ($leadershipType === 'department' && empty($department)) {
        $errors[] = "Department is required for department leadership";
    }
    if ($leadershipType === 'ministry' && empty($ministry)) {
        $errors[] = "Ministry is required for ministry leadership";
    }
    
    // Date validation
    if (!empty($fromDate) && !empty($toDate) && !$isCurrent) {
        if (strtotime($fromDate) >= strtotime($toDate)) {
            $errors[] = "End date must be after start date";
        }
    }
    
    return $errors;
}

/**
 * Get user's leadership history
 * 
 * @param PDO $pdo Database connection
 * @param int $id User ID
 * @return array Array of leadership records
 */
function getUserLeadershipHistory($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT ul.*, d.name as department_name, m.name as ministry_name 
            FROM user_leadership ul 
            LEFT JOIN departments d ON ul.department = d.id 
            LEFT JOIN ministries m ON ul.ministry = m.id 
            WHERE ul.user_id = ? 
            ORDER BY ul.from_date DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching leadership history: " . $e->getMessage());
        return [];
    }
}

// Handle HTTP requests
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Return user's current leadership information
        $leadershipInfo = getUserLeadershipHistory($pdo, $user_id);
        echo json_encode(['success' => true, 'data' => $leadershipInfo]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update leadership details
        $result = updateLeadershipDetails($pdo, $user_id, $_POST);
        echo json_encode($result);
        
    } else {
        // Unsupported method
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Unexpected error in leadership handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>