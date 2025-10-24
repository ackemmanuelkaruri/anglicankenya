<?php
/**
 * ==================================================
 * MINISTRY SECTION HANDLER (LOGIC) - REVISED FOR SINGLE TABLE
 * ==================================================
 * Updates the single 'ministries' table to handle user assignments.
 */

// NOTE: These lists must match the keys used in ministry_section.php
$VALID_DEPARTMENTS = [
    'MOTHERS_UNION', 'KAMA', 'KAYO', 'CHOIR', 'PRAISE_WORSHIP', 'LADIES_FORUM'
];
$VALID_MINISTRIES = [
    'USHERS', 'GREETERS', 'MEDIA', 'PA_SYSTEM', 'SUNDAY_SCHOOL_TEACHER', 'DORCAS', 'DEVELOPMENT'
];


/**
 * Core function to update a user's ministry and department involvement.
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param array $postData Array of POST data containing 'departments' and 'ministries' arrays.
 * @return array Response array
 */
function updateMinistryDetails($pdo, $user_id, $postData)
{
    global $VALID_DEPARTMENTS, $VALID_MINISTRIES;
    
    $departments = is_array($postData['departments'] ?? null) ? $postData['departments'] : [];
    $ministries = is_array($postData['ministries'] ?? null) ? $postData['ministries'] : [];
    
    // Filter inputs against master list keys
    $validDepartments = array_intersect($departments, $VALID_DEPARTMENTS);
    $validMinistries = array_intersect($ministries, $VALID_MINISTRIES);
    
    error_log("Valid Departments: " . json_encode($validDepartments));
    error_log("Valid Ministries: " . json_encode($validMinistries));
    
    $pdo->beginTransaction();
    try {
        // 1. CLEAR: Delete all existing assignments for the user in the 'ministries' table
        $stmt = $pdo->prepare("DELETE FROM ministries WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // 2. INSERT: Prepare the combined list of new records for batch insert
        $newRecords = [];
        
        // Get user's parish_id (required by table)
        $userStmt = $pdo->prepare("SELECT parish_id, organization_id FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Try to get parish_id, fallback to organization_id, or use 1 as default
        $parish_id = $userData['parish_id'] ?? $userData['organization_id'] ?? 1;
        
        if (!$parish_id) {
            throw new Exception('User has no parish_id or organization_id. Please update your profile with parish information first.');
        }
        
        error_log("Using parish_id: " . $parish_id . " for user_id: " . $user_id);
        
        // Add departments
        foreach ($validDepartments as $dept) {
            $newRecords[] = [
                'user_id' => $user_id,
                'parish_id' => $parish_id,
                'assignment_type' => 'DEPARTMENT', 
                'ministry_department_name' => $dept
            ];
        }
        
        // Add ministries
        foreach ($validMinistries as $min) {
            $newRecords[] = [
                'user_id' => $user_id,
                'parish_id' => $parish_id,
                'assignment_type' => 'MINISTRY', 
                'ministry_department_name' => $min
            ];
        }
        
        error_log("New Records: " . json_encode($newRecords));
        
        if (!empty($newRecords)) {
            $valuePlaceholders = [];
            $valueParams = [];
            
            foreach ($newRecords as $record) {
                // Columns: user_id, parish_id, assignment_type, ministry_department_name
                $valuePlaceholders[] = '(?, ?, ?, ?)'; 
                $valueParams[] = $record['user_id'];
                $valueParams[] = $record['parish_id'];
                $valueParams[] = $record['assignment_type'];
                $valueParams[] = $record['ministry_department_name'];
            }
            
            $sql = "INSERT INTO ministries (user_id, parish_id, assignment_type, ministry_department_name) VALUES " . implode(', ', $valuePlaceholders);
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($valueParams));
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valueParams);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Ministry and department involvement updated successfully.',
            'data' => ['departments' => $validDepartments, 'ministries' => $validMinistries]
        ];

    } catch (Throwable $e) {
        $pdo->rollBack();
        
        // LOG THE ACTUAL ERROR
        error_log("Ministry update failed: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // RETURN THE ACTUAL ERROR MESSAGE FOR DEBUGGING
        return [
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ];
    }
}

/**
 * Function to delete all ministry/department details
 */
function deleteAllMinistryDetails($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM ministries WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return ['success' => true, 'message' => 'Successfully cleared all ministry and department assignments.'];
    } catch (Throwable $e) {
        error_log("Delete ministry failed: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Delete error: ' . $e->getMessage()
        ];
    }
}