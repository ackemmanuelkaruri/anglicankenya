<?php
/**
 * Personal Section Update Handler - NEW SYSTEM
 * Handles updating both users and user_details tables
 * Compatible with personal-section.js
 */

/**
 * Main function to update personal information
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param array $postData POST data from form
 * @param array $filesData FILES data (optional)
 * @return array Response array with success status and message
 */
function updatePersonalInfo($pdo, $user_id, $postData, $filesData = []) {
    try {
        // Log the start of the operation
        if (function_exists('infoLog')) {
            infoLog("Starting updatePersonalInfo", ['user_id' => $user_id]);
            debugLogArray("POST data received", $postData);
        }
        
        // Validate required fields
        $requiredFields = ['username', 'first_name', 'last_name', 'phone_number', 'gender', 'country', 'occupation', 'marital_status', 'education_level'];
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (empty(trim($postData[$field] ?? ''))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $message = 'Missing required fields: ' . implode(', ', $missing);
            if (function_exists('warningLog')) {
                warningLog("Validation failed", ['missing_fields' => $missing]);
            }
            return ['success' => false, 'message' => $message];
        }
        
        // Handle passport file upload if provided
        $passportPath = null;
        if (isset($filesData['passport']) && $filesData['passport']['error'] !== UPLOAD_ERR_NO_FILE) {
            $passportUpload = handlePassportUpload($filesData['passport'], $user_id);
            if ($passportUpload['success']) {
                $passportPath = $passportUpload['file_path'];
            } else {
                // Log warning but continue - photo is not critical
                if (function_exists('warningLog')) {
                    warningLog("Passport upload failed", ['error' => $passportUpload['message']]);
                }
            }
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Prepare clean data for users table
        $userData = [
            'username' => trim($postData['username']),
            'first_name' => trim($postData['first_name']),
            'last_name' => trim($postData['last_name']),
            'email' => !empty($postData['email']) ? trim($postData['email']) : null,
            'phone_number' => trim($postData['phone_number']),
            'gender' => trim($postData['gender'])
        ];
        
        // Prepare clean data for user_details table
        $detailsData = [
            'occupation' => trim($postData['occupation']),
            'marital_status' => trim($postData['marital_status']),
            'wedding_type' => !empty($postData['wedding_type']) ? trim($postData['wedding_type']) : null,
            'education_level' => trim($postData['education_level']),
            'country' => trim($postData['country']),
            'passport' => $passportPath
        ];
        
        // Update users table
        $userUpdateResult = updateUsersTable($pdo, $user_id, $userData);
        if (!$userUpdateResult['success']) {
            $pdo->rollBack();
            return $userUpdateResult;
        }
        
        // Update or insert user_details table
        $detailsUpdateResult = updateUserDetailsTable($pdo, $user_id, $detailsData);
        if (!$detailsUpdateResult['success']) {
            $pdo->rollBack();
            return $detailsUpdateResult;
        }
        
        // Commit transaction
        $pdo->commit();
        
        if (function_exists('infoLog')) {
            infoLog("Personal information updated successfully", ['user_id' => $user_id]);
        }
        
        return [
            'success' => true,
            'message' => 'Personal information updated successfully!',
            'data' => [
                'passport_uploaded' => (bool)$passportPath,
                'passport_path' => $passportPath
            ]
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        if (function_exists('errorLog')) {
            errorLog("Error in updatePersonalInfo", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        
        return [
            'success' => false,
            'message' => 'Error updating personal information: ' . $e->getMessage()
        ];
    }
}

/**
 * Update the users table with basic user information
 */
function updateUsersTable($pdo, $user_id, $userData) {
    try {
        $sql = "UPDATE users SET 
                username = :username,
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone_number = :phone_number,
                gender = :gender,
                updated_at = NOW()
                WHERE id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':username' => $userData['username'],
            ':first_name' => $userData['first_name'],
            ':last_name' => $userData['last_name'],
            ':email' => $userData['email'],
            ':phone_number' => $userData['phone_number'],
            ':gender' => $userData['gender'],
            ':user_id' => $user_id
        ];
        
        if (!$stmt->execute($params)) {
            return [
                'success' => false,
                'message' => 'Failed to update user information'
            ];
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * Update or insert user_details table
 */
function updateUserDetailsTable($pdo, $user_id, $detailsData) {
    try {
        // Check if record exists
        $checkSql = "SELECT detail_id FROM user_details WHERE user_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$user_id]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // UPDATE existing record
            $sql = "UPDATE user_details SET 
                    occupation = :occupation,
                    marital_status = :marital_status,
                    wedding_type = :wedding_type,
                    education_level = :education_level,
                    country = :country";
            
            // Only update passport if a new one was uploaded
            if ($detailsData['passport'] !== null) {
                $sql .= ", passport = :passport";
            }
            
            $sql .= " WHERE user_id = :user_id";
            
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':occupation' => $detailsData['occupation'],
                ':marital_status' => $detailsData['marital_status'],
                ':wedding_type' => $detailsData['wedding_type'],
                ':education_level' => $detailsData['education_level'],
                ':country' => $detailsData['country'],
                ':user_id' => $user_id
            ];
            
            if ($detailsData['passport'] !== null) {
                $params[':passport'] = $detailsData['passport'];
            }
            
        } else {
            // INSERT new record
            $sql = "INSERT INTO user_details (
                    user_id, occupation, marital_status, wedding_type, 
                    education_level, country, passport, created_at
                ) VALUES (
                    :user_id, :occupation, :marital_status, :wedding_type,
                    :education_level, :country, :passport, NOW()
                )";
            
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':user_id' => $user_id,
                ':occupation' => $detailsData['occupation'],
                ':marital_status' => $detailsData['marital_status'],
                ':wedding_type' => $detailsData['wedding_type'],
                ':education_level' => $detailsData['education_level'],
                ':country' => $detailsData['country'],
                ':passport' => $detailsData['passport']
            ];
        }
        
        if (!$stmt->execute($params)) {
            return [
                'success' => false,
                'message' => 'Failed to update user details'
            ];
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * Handle passport photo upload
 */
function handlePassportUpload($fileData, $user_id) {
    try {
        // Validate upload
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error: ' . $fileData['error']];
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = mime_content_type($fileData['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG and PNG files are allowed.'];
        }
        
        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($fileData['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
        }
        
        // Create upload directory if it doesn't exist
        $uploadBaseDir = __DIR__ . '/../../uploads/passports/';
        if (!is_dir($uploadBaseDir)) {
            if (!mkdir($uploadBaseDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        $filename = 'passport_' . $user_id . '_' . time() . '.' . $extension;
        $uploadPath = $uploadBaseDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file.'];
        }
        
        // Return relative path for database storage
        $relativePath = 'uploads/passports/' . $filename;
        
        return [
            'success' => true,
            'file_path' => $relativePath,
            'message' => 'Passport photo uploaded successfully.'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Upload error: ' . $e->getMessage()
        ];
    }
}
?>