<?php
/**
 * ===================================================
 * LEADERSHIP SECTION HANDLER 
 * ===================================================
 * Core business logic for all leadership role assignments (Delete/Re-Insert).
 * This file is required by leadership_update.php.
 */

if (!isset($project_root)) {
    $project_root = dirname(dirname(__DIR__));
}

/**
 * Core function to update all leadership details for a user.
 */
function updateLeadershipDetails($pdo, $user_id, $postData) 
{
    $pdo->beginTransaction();
    try {
        
        // 1. Delete all existing leadership records for this user (Bulk Save Strategy)
        $deleteStmt = $pdo->prepare("DELETE FROM user_leadership WHERE user_id = ?");
        $deleteStmt->execute([$user_id]);
        
        $insertedRecords = 0;

        // 2. Check and re-insert leadership data
        if (isset($postData['role_id']) && is_array($postData['role_id'])) {
            
            $insertStmt = $pdo->prepare("INSERT INTO user_leadership 
                (user_id, role_id, department_id, ministry_id, role_name, from_date, to_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Assume lookups are available to map standard IDs (1, 2, 3...) to names
            $tempRoleNames = [1 => 'Deacon', 2 => 'Elder', 3 => 'Department Head', 4 => 'Ministry Lead'];

            $roleIds = $postData['role_id'];
            $deptIds = $postData['department_id'];
            $fromDates = $postData['leadership_from_date'];
            $toDates = $postData['leadership_to_date'];
            $isActives = $postData['is_active_leadership'] ?? [];
            $otherRoles = $postData['other_role'] ?? []; // For 'Other' selection (ID 99)

            for ($i = 0; $i < count($roleIds); $i++) {
                $role_id = trim($roleIds[$i]);
                if (empty($role_id)) continue; 
                
                $department_id = trim($deptIds[$i]) ?: null;
                $ministry_id = null; // Assuming ministry_id is not yet in the form, setting to null
                $fromDate = trim($fromDates[$i]);
                $toDate = trim($toDates[$i]) ?: null;
                $isActive = $isActives[$i] ?? 0;
                
                $roleName = $tempRoleNames[$role_id] ?? null;
                if ($role_id === '99') {
                    // Use the text input for custom 'Other' role
                    $roleName = trim($otherRoles[$i] ?? 'Other Role');
                }
                
                // Safety check
                if (empty($roleName)) continue;

                $insertStmt->execute([
                    $user_id, 
                    $role_id, 
                    $department_id, 
                    $ministry_id, 
                    $roleName, 
                    $fromDate, 
                    $toDate, 
                    $isActive
                ]);
                $insertedRecords++;
            }
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Leadership details updated successfully! ' . $insertedRecords . ' assignments saved.',
            'records_updated' => $insertedRecords
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Failed to update leadership details. Error: ' . $e->getMessage()
        ];
    }
}