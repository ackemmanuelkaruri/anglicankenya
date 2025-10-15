<?php
/**
 * Clergy Section Update Handler
 * Uses the enhanced DebugLogger system for comprehensive logging
 * Similar structure to personal_section.php
 */
session_start();
require_once '../db.php';
// FIXED: Check both session variables to match your login system
$user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
/**
 * Get existing clergy roles for a user
 */
function getClergyRoles($pdo, $user_id) {
    try {
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'clergy_roles'");
        if ($tableCheck->rowCount() === 0) {
            return [];
        }
        
        // Role name mapping
        $roleNames = [
            '1' => 'Vicar',
            '2' => 'Curate Vicar',
            '3' => 'Lay Reader',
            '4' => 'Evangelist',
            '5' => 'Church Warden',
            '6' => 'Deacon'
        ];
        
        $stmt = $pdo->prepare("
            SELECT cr.*, r.role_name as db_role_name
            FROM clergy_roles cr 
            LEFT JOIN roles r ON cr.role_id = r.id 
            WHERE cr.user_id = ?
            ORDER BY cr.serving_from_date DESC
        ");
        $stmt->execute([$user_id]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normalize the data structure
        foreach ($roles as &$role) {
            // Use role name from database if available, otherwise use mapping
            if (!empty($role['db_role_name'])) {
                $role['display_role_name'] = $role['db_role_name'];
            } else {
                $role['display_role_name'] = $roleNames[$role['role_id']] ?? 'Unknown Role';
            }
            
            // Handle is_current
            if (isset($role['is_current'])) {
                $role['is_current'] = $role['is_current'] ? 1 : 0;
            } else {
                // Determine from to date
                $role['is_current'] = empty($role['to_date']) ? 1 : 0;
            }
        }
        
        return $roles;
        
    } catch (Exception $e) {
        error_log("Failed to load clergy roles: " . $e->getMessage());
        return [];
    }
}
/**
 * Update or insert clergy role
 */
function updateClergyRole($pdo, $user_id, $postData) {
    try {
        // Validate required fields
        $required = ['role_id', 'serving_from_date'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty(trim($postData[$field] ?? ''))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $message = 'Missing required fields: ' . implode(', ', $missing);
            return ['success' => false, 'message' => $message];
        }
        
        // Prepare clean data
        $roleId = intval($postData['role_id']);
        $fromDate = trim($postData['serving_from_date']);
        $toDate = !empty($postData['serving_to_date']) ? trim($postData['serving_to_date']) : null;
        $isCurrent = !empty($postData['is_current']) ? 1 : 0;
        
        // If marked as current, clear the end date
        if ($isCurrent) {
            $toDate = null;
        }
        
        // Get role name from role ID
        $roleNames = [
            '1' => 'Vicar',
            '2' => 'Curate Vicar',
            '3' => 'Lay Reader',
            '4' => 'Evangelist',
            '5' => 'Church Warden',
            '6' => 'Deacon'
        ];
        $roleName = $roleNames[$roleId] ?? 'Unknown Role';
        
        // Create table if it doesn't exist with simplified structure
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS clergy_roles (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    role_id INT NOT NULL,
                    role_name VARCHAR(100),
                    serving_from_date DATE NOT NULL,
                    to_date DATE NULL,
                    is_current BOOLEAN DEFAULT FALSE,
                    INDEX idx_user_id (user_id),
                    CONSTRAINT fk_clergy_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        } catch (Exception $e) {
            error_log("Failed to create clergy_roles table: " . $e->getMessage());
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Check if updating existing record
            if (!empty($postData['clergy_id'])) {
                // Update existing record
                $clergyId = intval($postData['clergy_id']);
                
                $sql = "UPDATE clergy_roles 
                        SET role_id = ?, role_name = ?, serving_from_date = ?, to_date = ?, is_current = ? 
                        WHERE id = ? AND user_id = ?";
                
                $params = [$roleId, $roleName, $fromDate, $toDate, $isCurrent, $clergyId, $user_id];
                
                $stmt = $pdo->prepare($sql);
                if (!$stmt->execute($params)) {
                    throw new Exception('Failed to update clergy role: ' . implode(', ', $stmt->errorInfo()));
                }
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('No rows updated - clergy role may not exist or belong to user');
                }
                
                $message = 'Clergy role updated successfully';
                
                // Return updated role data
                $roleData = [
                    'id' => $clergyId,
                    'role_id' => $roleId,
                    'role_name' => $roleName,
                    'display_role_name' => $roleName,
                    'serving_from_date' => $fromDate,
                    'to_date' => $toDate,
                    'is_current' => $isCurrent
                ];
                
            } else {
                // Insert new record
                $sql = "INSERT INTO clergy_roles (user_id, role_id, role_name, serving_from_date, to_date, is_current) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$user_id, $roleId, $roleName, $fromDate, $toDate, $isCurrent];
                
                $stmt = $pdo->prepare($sql);
                if (!$stmt->execute($params)) {
                    throw new Exception('Failed to insert clergy role: ' . implode(', ', $stmt->errorInfo()));
                }
                
                $newId = $pdo->lastInsertId();
                $message = 'Clergy role saved successfully';
                
                // Return new role data
                $roleData = [
                    'id' => $newId,
                    'role_id' => $roleId,
                    'role_name' => $roleName,
                    'display_role_name' => $roleName,
                    'serving_from_date' => $fromDate,
                    'to_date' => $toDate,
                    'is_current' => $isCurrent
                ];
            }
            
            $pdo->commit();
            
            return [
                'success' => true, 
                'message' => $message,
                'role' => $roleData,
                'data' => $roleData
            ];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Clergy role transaction failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Error in updateClergyRole: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error processing clergy role: ' . $e->getMessage()
        ];
    }
}
/**
 * Delete clergy role
 */
function deleteClergyRole($pdo, $user_id, $clergy_id) {
    try {
        if (empty($clergy_id)) {
            return ['success' => false, 'message' => 'Clergy ID is required'];
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $sql = "DELETE FROM clergy_roles WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            
            if (!$stmt->execute([$clergy_id, $user_id])) {
                throw new Exception('Failed to delete clergy role: ' . implode(', ', $stmt->errorInfo()));
            }
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('No rows deleted - clergy role may not exist or belong to user');
            }
            
            $pdo->commit();
            
            return [
                'success' => true, 
                'message' => 'Clergy role deleted successfully'
            ];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Delete clergy role failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Error in deleteClergyRole: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error deleting clergy role: ' . $e->getMessage()
        ];
    }
}
// Handle different HTTP methods and actions
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Return existing clergy roles
        $roles = getClergyRoles($pdo, $user_id);
        echo json_encode(['success' => true, 'data' => $roles]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            // Delete clergy role
            $result = deleteClergyRole($pdo, $user_id, $_POST['clergy_id'] ?? '');
            echo json_encode($result);
        } else {
            // Save/update clergy role (handles both 'save' and 'update' actions)
            $result = updateClergyRole($pdo, $user_id, $_POST);
            echo json_encode($result);
        }
        
    } else {
        // Unsupported method
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Unexpected error in clergy handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>