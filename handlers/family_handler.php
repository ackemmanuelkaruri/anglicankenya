<?php
/**
 * CORRECTED Family Handler Functions
 * Updated to match your actual HTML form structure
 * Handles: add_existing_user_family and add_minor_family actions
 */

/**
 * Family Section Update Handler - CORRECTED VERSION
 * Processes family-related operations based on action type
 */
function updateFamilyInfo($pdo, $user_id, $postData, $filesData = []) {
    try {
        // Use existing debug helper functions from usection_update.php
        if (function_exists('infoLog')) {
            infoLog("Starting updateFamilyInfo (CORRECTED)", ['user_id' => $user_id]);
            debugLogArray("POST data received", $postData);
            debugLogArray("FILES data received", $filesData);
        }
        
        // Verify database connection
        try {
            $pdo->query('SELECT 1');
            if (function_exists('debugLog')) {
                debugLog("Database connection verified");
            }
        } catch (Exception $e) {
            if (function_exists('errorLog')) {
                errorLog("Database connection test failed", ['error' => $e->getMessage()]);
            }
            return ['success' => false, 'message' => 'Database connection failed. Please try again.'];
        }
        
        // Start transaction
        $pdo->beginTransaction();
        if (function_exists('debugLog')) {
            debugLog("Database transaction started");
        }
        
        // Verify user exists
        $checkQuery = "SELECT id FROM users WHERE id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$user_id]);
        $exists = $checkStmt->fetch();
        
        if (!$exists) {
            $pdo->rollBack();
            if (function_exists('warningLog')) {
                warningLog("User not found", ['user_id' => $user_id]);
            }
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Get the action from POST data
        $action = $postData['action'] ?? '';
        
        if (function_exists('infoLog')) {
            infoLog("Processing family action", ['action' => $action, 'user_id' => $user_id]);
        }
        
        // If no action, this might be a general save (just viewing family section)
        if (empty($action)) {
            $pdo->rollBack();
            return [
                'success' => true, 
                'message' => 'No family changes to save',
                'data' => ['action' => 'view_only']
            ];
        }
        
        // Process based on action - CORRECTED ACTIONS
        switch ($action) {
            case 'add_existing_user_family':
                $result = addExistingUserFamily($pdo, $user_id, $postData);
                break;
            case 'add_minor_family':
                $result = addMinorFamily($pdo, $user_id, $postData);
                break;
            case 'delete_family_member':
                $result = deleteFamilyMember($pdo, $user_id, $postData);
                break;
            default:
                $pdo->rollBack();
                if (function_exists('errorLog')) {
                    errorLog("Invalid family action", ['action' => $action, 'user_id' => $user_id]);
                }
                return ['success' => false, 'message' => 'Invalid action specified: ' . $action];
        }
        
        if (!$result['success']) {
            $pdo->rollBack();
            return $result;
        }
        
        $pdo->commit();
        if (function_exists('infoLog')) {
            infoLog("Family transaction committed successfully", ['action' => $action]);
        }
        
        return [
            'success' => true, 
            'message' => $result['message'] ?? 'Family information updated successfully!',
            'data' => array_merge($result['data'] ?? [], [
                'action' => $action,
                'operation_type' => 'FAMILY_UPDATE',
                'user_id' => $user_id
            ])
        ];
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
            if (function_exists('debugLog')) {
                debugLog("Family transaction rolled back due to exception");
            }
        }
        
        if (function_exists('errorLog')) {
            errorLog("Exception in updateFamilyInfo", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        
        return [
            'success' => false, 
            'message' => 'Error updating family information: ' . $e->getMessage()
        ];
    }
}

/**
 * NEW: Add existing user as family member
 * Matches your HTML form: add_existing_user_family
 */
function addExistingUserFamily($pdo, $user_id, $postData) {
    if (function_exists('infoLog')) {
        infoLog("Adding existing user as family member", ['user_id' => $user_id]);
    }
    
    // Validate required fields from your HTML form
    $related_user_id = trim($postData['related_user_id'] ?? '');
    $relationship = trim($postData['relationship'] ?? '');
    
    if (empty($related_user_id) || !is_numeric($related_user_id)) {
        return ['success' => false, 'message' => 'Please select a valid user to add as family member'];
    }
    
    if (empty($relationship)) {
        return ['success' => false, 'message' => 'Please specify the relationship'];
    }
    
    // Prevent adding self
    if ($related_user_id == $user_id) {
        return ['success' => false, 'message' => 'You cannot add yourself as a family member'];
    }
    
    // Check if the related user exists
    $checkUser = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
    $checkUser->execute([$related_user_id]);
    $relatedUser = $checkUser->fetch();
    
    if (!$relatedUser) {
        return ['success' => false, 'message' => 'Selected user not found'];
    }
    
    // Check if already added as family member
    $checkExisting = $pdo->prepare("SELECT id FROM family_members WHERE user_id = ? AND related_user_id = ?");
    $checkExisting->execute([$user_id, $related_user_id]);
    
    if ($checkExisting->fetch()) {
        return ['success' => false, 'message' => 'This user is already in your family'];
    }
    
    // Insert the family member record - MATCHES YOUR DATABASE STRUCTURE
    $insert = $pdo->prepare("
        INSERT INTO family_members (
            user_id, related_user_id, relationship, is_minor, created_at
        ) VALUES (?, ?, ?, 0, NOW())
    ");
    
    if ($insert->execute([$user_id, $related_user_id, $relationship])) {
        if (function_exists('infoLog')) {
            infoLog("Existing user added as family member successfully", [
                'user_id' => $user_id,
                'related_user_id' => $related_user_id,
                'relationship' => $relationship,
                'family_member_name' => $relatedUser['first_name'] . ' ' . $relatedUser['last_name']
            ]);
        }
        
        return [
            'success' => true, 
            'message' => 'Family member added successfully',
            'data' => [
                'family_member_name' => $relatedUser['first_name'] . ' ' . $relatedUser['last_name'],
                'relationship' => $relationship,
                'related_user_id' => $related_user_id,
                'is_minor' => false
            ]
        ];
    } else {
        if (function_exists('errorLog')) {
            errorLog("Failed to add existing user as family member", [
                'stmt_error' => $insert->errorInfo(),
                'user_id' => $user_id,
                'related_user_id' => $related_user_id
            ]);
        }
        return ['success' => false, 'message' => 'Failed to add family member. Please try again.'];
    }
}

/**
 * NEW: Add minor family member
 * Matches your HTML form: add_minor_family
 */
function addMinorFamily($pdo, $user_id, $postData) {
    if (function_exists('infoLog')) {
        infoLog("Adding minor family member", ['user_id' => $user_id]);
    }
    
    // Validate required fields from your HTML form
    $minor_first_name = trim($postData['minor_first_name'] ?? '');
    $minor_last_name = trim($postData['minor_last_name'] ?? '');
    $relationship = trim($postData['relationship'] ?? '');
    $minor_date_of_birth = trim($postData['minor_date_of_birth'] ?? '');
    
    if (empty($minor_first_name)) {
        return ['success' => false, 'message' => 'First name is required for minor family member'];
    }
    
    if (empty($minor_last_name)) {
        return ['success' => false, 'message' => 'Last name is required for minor family member'];
    }
    
    if (empty($relationship)) {
        return ['success' => false, 'message' => 'Please specify the relationship'];
    }
    
    if (empty($minor_date_of_birth)) {
        return ['success' => false, 'message' => 'Date of birth is required for minor family member'];
    }
    
    // Validate date format and age
    $dob = DateTime::createFromFormat('Y-m-d', $minor_date_of_birth);
    if (!$dob) {
        return ['success' => false, 'message' => 'Invalid date of birth format'];
    }
    
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    
    // Optional fields from your HTML form
    $minor_email = trim($postData['minor_email'] ?? '');
    $minor_phone = trim($postData['minor_phone'] ?? '');
    $minor_gender = trim($postData['minor_gender'] ?? '');
    $minor_notes = trim($postData['minor_notes'] ?? '');
    $can_activate_at_18 = isset($postData['can_activate_at_18']) ? 1 : 0;
    
    // Insert the minor family member - MATCHES YOUR DATABASE STRUCTURE
    $insert = $pdo->prepare("
        INSERT INTO family_members (
            user_id, minor_first_name, minor_last_name, minor_email, minor_phone,
            minor_date_of_birth, minor_gender, relationship, minor_notes, 
            is_minor, can_activate_at_18, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
    ");
    
    $params = [
        $user_id, 
        $minor_first_name, 
        $minor_last_name, 
        !empty($minor_email) ? $minor_email : null,
        !empty($minor_phone) ? $minor_phone : null,
        $minor_date_of_birth,
        !empty($minor_gender) ? $minor_gender : null,
        $relationship,
        !empty($minor_notes) ? $minor_notes : null,
        $can_activate_at_18
    ];
    
    if ($insert->execute($params)) {
        if (function_exists('infoLog')) {
            infoLog("Minor family member added successfully", [
                'user_id' => $user_id,
                'minor_name' => $minor_first_name . ' ' . $minor_last_name,
                'relationship' => $relationship,
                'age' => $age,
                'can_activate_at_18' => $can_activate_at_18
            ]);
        }
        
        return [
            'success' => true, 
            'message' => 'Minor family member added successfully',
            'data' => [
                'family_member_name' => $minor_first_name . ' ' . $minor_last_name,
                'relationship' => $relationship,
                'age' => $age,
                'is_minor' => true,
                'can_activate_at_18' => $can_activate_at_18
            ]
        ];
    } else {
        if (function_exists('errorLog')) {
            errorLog("Failed to add minor family member", [
                'stmt_error' => $insert->errorInfo(),
                'user_id' => $user_id,
                'minor_name' => $minor_first_name . ' ' . $minor_last_name
            ]);
        }
        return ['success' => false, 'message' => 'Failed to add minor family member. Please try again.'];
    }
}

/**
 * Delete family member (existing function, kept as-is since it works)
 */
function deleteFamilyMember($pdo, $user_id, $postData) {
    if (function_exists('infoLog')) {
        infoLog("Deleting family member", ['user_id' => $user_id]);
    }
    
    $member_id = trim($postData['member_id'] ?? '');
    
    if (empty($member_id) || !is_numeric($member_id)) {
        return ['success' => false, 'message' => 'Valid family member ID is required'];
    }
    
    // Get member details before deletion for logging
    $getMember = $pdo->prepare("
        SELECT fm.id, fm.related_user_id, fm.minor_first_name, fm.minor_last_name, fm.is_minor,
               u.first_name, u.last_name 
        FROM family_members fm
        LEFT JOIN users u ON fm.related_user_id = u.id
        WHERE fm.id = ? AND fm.user_id = ?
    ");
    $getMember->execute([$member_id, $user_id]);
    $member = $getMember->fetch();
    
    if (!$member) {
        if (function_exists('warningLog')) {
            warningLog("Family member not found or access denied", [
                'member_id' => $member_id,
                'user_id' => $user_id
            ]);
        }
        return ['success' => false, 'message' => 'Family member not found or access denied'];
    }
    
    // Delete the family member
    $delete = $pdo->prepare("DELETE FROM family_members WHERE id = ? AND user_id = ?");
    
    if ($delete->execute([$member_id, $user_id])) {
        // Determine member name
        if ($member['is_minor']) {
            $memberName = $member['minor_first_name'] . ' ' . $member['minor_last_name'];
        } else {
            $memberName = ($member['first_name'] && $member['last_name']) ? 
                         $member['first_name'] . ' ' . $member['last_name'] : 
                         'User ID: ' . $member['related_user_id'];
        }
        
        if (function_exists('infoLog')) {
            infoLog("Family member removed successfully", [
                'user_id' => $user_id,
                'member_id' => $member_id,
                'member_name' => $memberName,
                'was_minor' => $member['is_minor']
            ]);
        }
        
        return [
            'success' => true, 
            'message' => 'Family member removed successfully',
            'data' => [
                'removed_member' => $memberName,
                'was_minor' => $member['is_minor']
            ]
        ];
    } else {
        if (function_exists('errorLog')) {
            errorLog("Failed to remove family member", [
                'stmt_error' => $delete->errorInfo(),
                'member_id' => $member_id,
                'user_id' => $user_id
            ]);
        }
        return ['success' => false, 'message' => 'Failed to remove family member'];
    }
}
?>