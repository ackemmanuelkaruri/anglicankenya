<?php
/**
 * ===================================================
 * CLERGY SECTION HANDLER 
 * ===================================================
 * Core business logic for Clergy/Laity roles (CRUD functions).
 * This file is required by clergy_update.php.
 */

if (!isset($project_root)) {
    $project_root = dirname(dirname(__DIR__));
}

$clergyRoleNames = [
    '1' => 'Vicar', '2' => 'Curate Vicar', '3' => 'Lay Reader',
    '4' => 'Evangelist', '5' => 'Church Warden', '6' => 'Deacon'
];

/**
 * Gets all existing clergy roles for a user.
 */
function getClergyRoles($pdo, $user_id) {
    global $clergyRoleNames;
    try {
        $stmt = $pdo->prepare("SELECT * FROM clergy_roles WHERE user_id = ? ORDER BY serving_from_date DESC");
        $stmt->execute([$user_id]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($roles as &$role) {
            $role['display_role_name'] = $clergyRoleNames[$role['role_id']] ?? $role['role_name'] ?? 'N/A';
        }
        return $roles;
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Saves or updates a single clergy role.
 */
function updateClergyRole($pdo, $user_id, $postData) {
    global $clergyRoleNames;
    
    $clergy_id = $postData['clergy_id'] ?? null;
    $role_id = $postData['role_id'] ?? null;
    $role_name = trim($postData['role_name'] ?? null);
    $from_date = $postData['serving_from_date'] ?? null;
    $to_date = !empty($postData['serving_to_date']) ? $postData['serving_to_date'] : null;
    $is_current = isset($postData['is_current']) ? 1 : 0;
    
    // Set the role_name if a standard role_id is used
    if (isset($role_id) && $role_id !== '99' && isset($clergyRoleNames[$role_id])) {
        $role_name = $clergyRoleNames[$role_id];
    } elseif ($role_id === '99' && empty($role_name)) {
        return ['success' => false, 'message' => 'Please specify the "Other" role name.'];
    } elseif (empty($role_id)) {
        return ['success' => false, 'message' => 'Role Type is required.'];
    }
    
    $pdo->beginTransaction();
    try {
        if ($clergy_id) {
            // Update existing role
            $stmt = $pdo->prepare("UPDATE clergy_roles 
                SET role_id = ?, role_name = ?, serving_from_date = ?, serving_to_date = ?, is_current = ?
                WHERE id = ? AND user_id = ?");
            $stmt->execute([$role_id, $role_name, $from_date, $to_date, $is_current, $clergy_id, $user_id]);
            $action = 'Update';
        } else {
            // Insert new role
            $stmt = $pdo->prepare("INSERT INTO clergy_roles 
                (user_id, role_id, role_name, serving_from_date, serving_to_date, is_current) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $role_id, $role_name, $from_date, $to_date, $is_current]);
            $clergy_id = $pdo->lastInsertId();
            $action = 'Insert';
        }
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => "Clergy role $action successful.",
            'id' => $clergy_id,
            'roles' => getClergyRoles($pdo, $user_id) // Return updated list for client refresh
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Error saving clergy role: ' . $e->getMessage()];
    }
}

/**
 * Deletes a single clergy role.
 */
function deleteClergyRole($pdo, $user_id, $clergy_id) {
    if (empty($clergy_id)) {
        return ['success' => false, 'message' => 'Clergy ID is missing for deletion.'];
    }
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM clergy_roles WHERE id = ? AND user_id = ?");
        $stmt->execute([$clergy_id, $user_id]);
        $deleted = $stmt->rowCount();
        
        if ($deleted === 0) {
            throw new Exception("Role not found or user unauthorized to delete.", 403);
        }
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Clergy role deleted successfully.',
            'id' => $clergy_id,
            'roles' => getClergyRoles($pdo, $user_id) // Return updated list
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Error deleting clergy role: ' . $e->getMessage()];
    }
}