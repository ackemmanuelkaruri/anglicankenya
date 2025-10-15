<?php
/**
 * Personal Section Update Handler
 * Uses the enhanced DebugLogger system for comprehensive logging
 * FIXED: Updated to work with users table directly instead of personal_info table
 */

function updatePersonalInfo($pdo, $user_id, $postData, $filesData = []) {
    try {
        infoLog("Starting updatePersonalInfo", ['user_id' => $user_id]);
        debugLogArray("POST data received", $postData);
        debugLogArray("FILES data received", $filesData);
        
        // Validate required fields
        $requiredFields = ['username', 'first_name', 'last_name', 'phone_number'];
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (empty(trim($postData[$field] ?? ''))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $message = 'Missing required fields: ' . implode(', ', $missing);
            warningLog("Validation failed", ['missing_fields' => $missing]);
            return ['success' => false, 'message' => $message];
        }
        
        debugLog("Field validation passed");
        
        // Handle passport file upload
        $passportPath = null;
        if (isset($filesData['passport']) && $filesData['passport']['error'] !== UPLOAD_ERR_NO_FILE) {
            infoLog("Processing passport upload");
            debugLogFileUpload('passport', $filesData['passport']);
            
            $passportUpload = handlePassportUpload($filesData['passport'], $user_id);
            if (!$passportUpload['success']) {
                warningLog("Passport upload failed", ['error' => $passportUpload['message']]);
                // Continue without file - don't fail the entire update
            } else {
                $passportPath = $passportUpload['file_path'];
                infoLog("Passport uploaded successfully", ['path' => $passportPath]);
            }
        }
        
        // Verify database connection
        try {
            $pdo->query('SELECT 1');
            debugLog("Database connection verified");
        } catch (Exception $e) {
            errorLog("Database connection test failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Database connection failed. Please try again.'];
        }
        
        // Start transaction
        $pdo->beginTransaction();
        debugLog("Database transaction started");
        
        // Check if user exists (since we're updating users table directly)
        $checkQuery = "SELECT id FROM users WHERE id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        
        if (!$checkStmt) {
            $pdo->rollBack();
            errorLog("Failed to prepare check query", ['pdo_error' => $pdo->errorInfo()]);
            return ['success' => false, 'message' => 'Database error occurred'];
        }
        
        $checkStmt->execute([$user_id]);
        $exists = $checkStmt->fetch();
        
        if (!$exists) {
            $pdo->rollBack();
            warningLog("User not found", ['user_id' => $user_id]);
            return ['success' => false, 'message' => 'User not found'];
        }
        
        infoLog("User exists, proceeding with update", ['user_id' => $user_id]);
        
        // Prepare clean data
        $cleanData = [
            'username' => trim($postData['username']),
            'first_name' => trim($postData['first_name']),
            'last_name' => trim($postData['last_name']),
            'email' => !empty($postData['email']) ? trim($postData['email']) : null,
            'phone_number' => trim($postData['phone_number']),
            'gender' => !empty($postData['gender']) ? trim($postData['gender']) : null,
            'country' => !empty($postData['country']) ? trim($postData['country']) : null,
            'occupation' => !empty($postData['occupation']) ? trim($postData['occupation']) : null,
            'marital_status' => !empty($postData['marital_status']) ? trim($postData['marital_status']) : null,
            'wedding_type' => !empty($postData['wedding_type']) ? trim($postData['wedding_type']) : null,
            'education_level' => !empty($postData['education_level']) ? trim($postData['education_level']) : null
        ];
        
        debugLogArray("Cleaned data for database", $cleanData);
        
        // Update users table with all the personal information
        $result = updateUsersTable($pdo, $user_id, $cleanData, $passportPath);
        
        if (!$result['success']) {
            $pdo->rollBack();
            return $result;
        }
        
        $pdo->commit();
        infoLog("Transaction committed successfully");
        
        return [
            'success' => true, 
            'message' => 'Personal information updated successfully!',
            'data' => [
                'passport_uploaded' => (bool)$passportPath,
                'passport_path' => $passportPath,
                'operation_type' => 'UPDATE'
            ]
        ];
        
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
            debugLog("Transaction rolled back due to exception");
        }
        
        debugLogException($e);
        return [
            'success' => false, 
            'message' => 'Error updating personal information: ' . $e->getMessage()
        ];
    }
}

/**
 * Update users table with all personal information
 * FIXED: Now updates users table directly with all fields
 */
function updateUsersTable($pdo, $user_id, $cleanData, $passportPath = null) {
    $sql = "UPDATE users SET 
            username = ?, 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone_number = ?, 
            gender = ?, 
            country = ?, 
            occupation = ?, 
            marital_status = ?, 
            wedding_type = ?, 
            education_level = ?";
    
    $params = [
        $cleanData['username'], 
        $cleanData['first_name'], 
        $cleanData['last_name'],
        $cleanData['email'], 
        $cleanData['phone_number'], 
        $cleanData['gender'],
        $cleanData['country'], 
        $cleanData['occupation'], 
        $cleanData['marital_status'],
        $cleanData['wedding_type'], 
        $cleanData['education_level']
    ];
    
    // Add passport field if provided
    if ($passportPath) {
        $sql .= ", passport = ?";
        $params[] = $passportPath;
    }
    
    // Add updated_at if the column exists
    $sql .= ", updated_at = NOW() WHERE id = ?";
    $params[] = $user_id;
    
    debugLogQuery($sql, $params);
    
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        errorLog("Failed to prepare users UPDATE statement", ['pdo_error' => $pdo->errorInfo()]);
        return ['success' => false, 'message' => 'Database error: Failed to prepare user update'];
    }
    
    if (!$stmt->execute($params)) {
        errorLog("Failed to execute users UPDATE statement", ['stmt_error' => $stmt->errorInfo()]);
        return ['success' => false, 'message' => 'Database error: Failed to update user info'];
    }
    
    debugLogDBOperation('UPDATE', $stmt->rowCount(), 'users');
    return ['success' => true];
}

/**
 * Handle passport file upload using the FileUploadHandler
 */
function handlePassportUpload($fileData, $user_id) {
    try {
        infoLog("Starting passport upload using FileUploadHandler", ['user_id' => $user_id]);
        
        // Use the FileUploadHandler class for secure upload
        if (class_exists('FileUploadHandler')) {
            $result = FileUploadHandler::handleFileUpload($fileData, 'passport', $user_id);
            
            if ($result['success'] && isset($result['relative_path'])) {
                return [
                    'success' => true,
                    'file_path' => $result['relative_path'],
                    'message' => $result['message']
                ];
            }
            
            return $result;
        } else {
            // Fallback to basic upload if FileUploadHandler not available
            warningLog("FileUploadHandler class not found, using fallback method");
            return handlePassportUploadFallback($fileData, $user_id);
        }
        
    } catch (Exception $e) {
        debugLogException($e);
        return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
    }
}

/**
 * Fallback passport upload method if FileUploadHandler is not available
 */
function handlePassportUploadFallback($fileData, $user_id) {
    try {
        // Basic validation
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error'];
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png'];
        $detectedType = mime_content_type($fileData['tmp_name']);
        
        if (!in_array($detectedType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG and PNG files are allowed'];
        }
        
        // Validate file size (5MB max)
        if ($fileData['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB'];
        }
        
        // Create upload directory
        $uploadDir = '../uploads/passports/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Cannot create upload directory'];
        }
        
        // Generate filename
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $filename = 'passport_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move file
        if (!move_uploaded_file($fileData['tmp_name'], $filepath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file'];
        }
        
        return [
            'success' => true,
            'file_path' => 'uploads/passports/' . $filename,
            'message' => 'File uploaded successfully'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
    }
}

/**
 * Determine the best upload directory path
 */
function determineUploadDirectory() {
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/passports/',
        dirname(dirname(__FILE__)) . '/uploads/passports/',
        dirname(__FILE__) . '/../uploads/passports/'
    ];
    
    foreach ($possiblePaths as $path) {
        $parentDir = dirname($path);
        if (is_dir($parentDir) && is_writable($parentDir)) {
            debugLog("Selected upload directory", ['path' => $path]);
            return $path;
        }
    }
    
    errorLog("No suitable upload directory found", ['tried_paths' => $possiblePaths]);
    return null;
}
?>