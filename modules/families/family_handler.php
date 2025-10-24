<?php
/**
 * ===================================================
 * FAMILY SECTION HANDLER 
 * ===================================================
 * Core business logic for all family and dependent updates (CRUD).
 * This file is required by family_update.php.
 */

if (!isset($project_root)) {
    $project_root = dirname(dirname(__DIR__));
}

/**
 * Request a family link between two existing users.
 */
function requestFamilyLink($pdo, $user1_id, $target_username, $relationship_type) 
{
    $pdo->beginTransaction();
    try {
        // 1. Find target user by username or email
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$target_username, $target_username]);
        $user2 = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user2) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        $user2_id = $user2['id'];

        if ($user1_id == $user2_id) {
            return ['success' => false, 'message' => 'Cannot request link to yourself.'];
        }

        // 2. Determine the reciprocal relationship type (simplified mapping for this example)
        $reciprocalType = ($relationship_type === 'Spouse') ? 'Spouse' : 'Sibling/Relative'; 
        
        // 3. Insert PENDING request
        $stmt = $pdo->prepare("INSERT INTO user_relationships 
            (user1_id, user2_id, relationship_type1, relationship_type2, status) 
            VALUES (?, ?, ?, ?, 'PENDING')
            ON DUPLICATE KEY UPDATE relationship_type1 = VALUES(relationship_type1), status = 'PENDING'");
        
        $stmt->execute([$user1_id, $user2_id, $relationship_type, $reciprocalType]);
        
        $pdo->commit();

        return [
            'success' => true,
            'message' => "Link request sent successfully to " . htmlspecialchars($user2['first_name']) . ". They must approve it."
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => 'Error requesting link: ' . $e->getMessage()];
    }
}

/**
 * Add a minor dependent to the user's account.
 */
function addDependent($pdo, $parent_user_id, $postData) 
{
    $pdo->beginTransaction();
    try {
        // Simple input validation check for minor age (assuming logic elsewhere, but ensure DOB is past)
        if (empty($postData['date_of_birth'])) {
             return ['success' => false, 'message' => 'Date of Birth is required for a dependent.'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO dependents 
            (first_name, last_name, date_of_birth, gender, school_name, parent_user_id, relationship_to_parent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $postData['first_name'],
            $postData['last_name'],
            $postData['date_of_birth'],
            $postData['gender'],
            $postData['school_name'] ?? null,
            $parent_user_id,
            $postData['relationship_to_parent']
        ]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Dependent added successfully.'
        ];
        
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => 'Error adding dependent: ' . $e->getMessage()];
    }
}

/**
 * Delete a user relationship (removes link from both sides, if approved).
 */
function deleteUserRelationship($pdo, $user_id, $relationship_id) 
{
    $pdo->beginTransaction();
    try {
        // Delete request where user_id is user1 or user2 (ensures user can delete their own link/request)
        $stmt = $pdo->prepare("DELETE FROM user_relationships 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
        $stmt->execute([$relationship_id, $user_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Relationship not found or user unauthorized.", 403);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Family link removed successfully.'];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => 'Error removing link: ' . $e->getMessage()];
    }
}

/**
 * Delete a dependent.
 */
function deleteDependent($pdo, $parent_user_id, $dependent_id) 
{
    try {
        // Must ensure the logged-in user is the parent of the dependent
        $stmt = $pdo->prepare("DELETE FROM dependents WHERE dependent_id = ? AND parent_user_id = ?");
        $stmt->execute([$dependent_id, $parent_user_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Dependent not found or user not authorized to delete.", 403);
        }
        
        return ['success' => true, 'message' => 'Dependent removed successfully.'];

    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Error deleting dependent: ' . $e->getMessage()];
    }
}