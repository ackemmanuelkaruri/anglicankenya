<?php
/**
 * Church Details Section Handler - Unified Certificate Storage + Backward Compatibility
 * All baptism & confirmation certificates now go to uploads/certificates/
 * Old paths still work for reading and cleanup
 */
require_once dirname(__FILE__) . '/../helpers/file_upload_helper.php';
require_once dirname(__FILE__) . '/../helpers/debug_helper.php';

function updateChurchDetails($pdo, $id, $postData, $filesData) {
    $pdo->beginTransaction();
    try {
        debugLog("Starting church details update for user: $id");
        debugLogArray("Raw POST data received", $postData, 'DEBUG');

        $convertedData = convertChurchFormData($postData);
        debugLogArray("Converted POST data", $convertedData, 'DEBUG');

        validateChurchInputs($convertedData);

        $currentUser = getUserChurchInfo($pdo, $id);
        $processedData = processChurchBusinessLogic($pdo, $id, $convertedData, $currentUser);

        $baptismCertificate = null;
        $confirmationCertificate = null;

       // Store baptism certificate in unified certificates folder
        if (!empty($filesData['baptism_certificate']['name'])) {
            // Use FileUploadHandler class directly like personal section
            $baptismUploadResult = FileUploadHandler::handleFileUpload(
                $filesData['baptism_certificate'], 
                'certificate', 
                $id
            );
            
            if ($baptismUploadResult['success'] && isset($baptismUploadResult['relative_path'])) {
                $baptismCertificate = $baptismUploadResult['relative_path'];
                if (!empty($currentUser['baptism_certificate'])) {
                    cleanupOldFile($currentUser['baptism_certificate']);
                }
            } else {
                // Handle upload error - you might want to throw an exception or continue
                debugLog("Baptism certificate upload failed: " . $baptismUploadResult['message']);
            }
        }

        // Store confirmation certificate in unified certificates folder
        if (!empty($filesData['confirmation_certificate']['name'])) {
            // Use FileUploadHandler class directly like personal section
            $confirmationUploadResult = FileUploadHandler::handleFileUpload(
                $filesData['confirmation_certificate'], 
                'certificate', 
                $id
            );
            
            if ($confirmationUploadResult['success'] && isset($confirmationUploadResult['relative_path'])) {
                $confirmationCertificate = $confirmationUploadResult['relative_path'];
                if (!empty($currentUser['confirmation_certificate'])) {
                    cleanupOldFile($currentUser['confirmation_certificate']);
                }
            } else {
                // Handle upload error - you might want to throw an exception or continue
                debugLog("Confirmation certificate upload failed: " . $confirmationUploadResult['message']);
            }
        }

        $sql = "UPDATE users SET
                    service_attending = ?, 
                    english_service_team = ?, 
                    kikuyu_cell_group = ?,
                    family_group = ?, 
                    baptized = ?, 
                    baptism_interest = ?,
                    confirmed = ?, 
                    confirmation_interest = ?,
                    church_membership_no = ?,
                    church_department = ?,
                    ministry_committee = ?,
                    departments = ?,
                    ministries = ?,
                    want_to_be_baptized = ?,
                    want_to_be_confirmed = ?,
                    updated_at = NOW()";

        $params = [
            $processedData['service_attending'] ?? null,
            $processedData['english_service_team'] ?? null,
            $processedData['kikuyu_cell_group'] ?? null,
            $processedData['family_group'] ?? null,
            $processedData['baptized'] ?? null,
            $processedData['baptism_interest'] ?? null,
            $processedData['confirmed'] ?? null,
            $processedData['confirmation_interest'] ?? null,
            $processedData['church_membership_no'] ?? null,
            $processedData['church_department'] ?? null,
            $processedData['ministry_committee'] ?? null,
            $processedData['departments'] ?? null,
            $processedData['ministries'] ?? null,
            $processedData['want_to_be_baptized'] ?? null,
            $processedData['want_to_be_confirmed'] ?? null
        ];

        if ($baptismCertificate !== null) {
            $sql .= ", baptism_certificate = ?";
            $params[] = $baptismCertificate;
        } elseif (array_key_exists('baptism_certificate', $processedData) && $processedData['baptism_certificate'] === null) {
            $sql .= ", baptism_certificate = NULL";
        }

        if ($confirmationCertificate !== null) {
            $sql .= ", confirmation_certificate = ?";
            $params[] = $confirmationCertificate;
        } elseif (array_key_exists('confirmation_certificate', $processedData) && $processedData['confirmation_certificate'] === null) {
            $sql .= ", confirmation_certificate = NULL";
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        debugLog("Final SQL: " . $sql);
        debugLogArray("SQL Parameters", $params, 'DEBUG');

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            debugLogArray("SQL Error Info", $errorInfo, 'ERROR');
            throw new Exception('Database update failed: ' . implode(', ', $errorInfo));
        }

        $rowCount = $stmt->rowCount();
        debugLog("Rows affected: " . $rowCount);
        
        $pdo->commit();
        debugLogDBOperation('UPDATE', $rowCount, 'users (church details)');
        
        return [
            'success' => true, 
            'message' => 'Church details updated successfully!',
            'updated_fields' => array_keys($processedData),
            'rows_affected' => $rowCount
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        debugLog("Church details error: " . $e->getMessage());
        errorLog("Church details update failed", [
            'user_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false, 
            'message' => 'Error updating church details: ' . $e->getMessage()
        ];
    }
}

function convertChurchFormData($postData) {
    $converted = $postData;
    $serviceMapping = [
        'English Service' => 'english',
        'Kikuyu Service' => 'kikuyu', 
        'Teens Service' => 'teens',
        'Sunday School' => 'sunday_school',
        'english' => 'english',
        'kikuyu' => 'kikuyu',
        'teens' => 'teens',
        'sunday_school' => 'sunday_school',
        '' => ''
    ];
    if (isset($postData['service_attending']) && !empty($postData['service_attending'])) {
        $serviceValue = trim($postData['service_attending']);
        if (array_key_exists($serviceValue, $serviceMapping)) {
            $converted['service_attending'] = $serviceMapping[$serviceValue];
        }
    }
    $yesNoFields = ['baptized', 'confirmed', 'want_to_be_baptized', 'want_to_be_confirmed'];
    foreach ($yesNoFields as $field) {
        if (isset($postData[$field])) {
            $converted[$field] = strtolower(trim($postData[$field]));
        }
    }
    $interestFields = ['baptism_interest', 'confirmation_interest'];
    foreach ($interestFields as $field) {
        if (isset($postData[$field])) {
            $value = strtolower(trim($postData[$field]));
            $converted[$field] = $value === 'yes' ? 'interested' : ($value === 'no' ? 'not_interested' : $value);
        }
    }
    if (isset($postData['church_membership_no'])) {
        $converted['church_membership_no'] = trim($postData['church_membership_no']);
    }
    $textFields = [
        'english_service_team', 'kikuyu_cell_group', 'family_group', 
        'church_department', 'ministry_committee', 
        'departments', 'ministries'
    ];
    foreach ($textFields as $field) {
        if (isset($postData[$field])) {
            $converted[$field] = trim($postData[$field]);
        }
    }
    return $converted;
}

function getUserChurchInfo($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT 
                service_attending, english_service_team, kikuyu_cell_group,
                family_group, baptized, baptism_interest, confirmed, 
                confirmation_interest, baptism_certificate, confirmation_certificate,
                church_membership_no, church_department, ministry_committee,
                departments, ministries, want_to_be_baptized, want_to_be_confirmed,
                updated_at
                FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function validateChurchInputs($postData) {
    $validServices = ['english', 'kikuyu', 'teens', 'sunday_school', ''];
    $validYesNo = ['yes', 'no', ''];
    $validInterest = ['interested', 'not_interested', ''];

    if (isset($postData['service_attending']) && !in_array($postData['service_attending'], $validServices)) {
        throw new Exception('Invalid service selection provided.');
    }
    if (isset($postData['baptized']) && !in_array($postData['baptized'], $validYesNo)) {
        throw new Exception('Invalid baptism status provided');
    }
    if (isset($postData['confirmed']) && !in_array($postData['confirmed'], $validYesNo)) {
        throw new Exception('Invalid confirmation status provided');
    }
    if (isset($postData['baptism_interest']) && !in_array($postData['baptism_interest'], $validInterest)) {
        throw new Exception('Invalid baptism interest option provided');
    }
    if (isset($postData['confirmation_interest']) && !in_array($postData['confirmation_interest'], $validInterest)) {
        throw new Exception('Invalid confirmation interest option provided');
    }
}

function processChurchBusinessLogic($pdo, $id, $postData, $currentUser) {
    $stmt = $pdo->prepare("SELECT baptized, confirmed FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentData) {
        throw new Exception('User not found');
    }
    $processedData = $postData;

    if (isset($postData['service_attending']) && !empty($postData['service_attending'])) {
        $currentService = getCurrentUserField($pdo, $id, 'service_attending');
        if ($postData['service_attending'] !== $currentService) {
            $processedData['english_service_team'] = null;
            $processedData['kikuyu_cell_group'] = null;
            $processedData['family_group'] = null;
        }
    }
    if (isset($postData['kikuyu_cell_group']) && !empty($postData['kikuyu_cell_group'])) {
        $currentCellGroup = getCurrentUserField($pdo, $id, 'kikuyu_cell_group');
        if ($postData['kikuyu_cell_group'] !== $currentCellGroup) {
            $processedData['family_group'] = null;
        }
    }
    if (isset($postData['baptized'])) {
        if ($postData['baptized'] === 'yes' && $currentData['baptized'] !== 'yes') {
            $processedData['baptism_interest'] = null;
        }
        if ($postData['baptized'] === 'no') {
            $processedData['baptism_certificate'] = null;
            if (!empty($currentUser['baptism_certificate'])) {
                cleanupOldFile($currentUser['baptism_certificate']);
            }
        }
    }
    if (isset($postData['confirmed'])) {
        if ($postData['confirmed'] === 'yes' && $currentData['confirmed'] !== 'yes') {
            $processedData['confirmation_interest'] = null;
        }
        if ($postData['confirmed'] === 'no') {
            $processedData['confirmation_certificate'] = null;
            if (!empty($currentUser['confirmation_certificate'])) {
                cleanupOldFile($currentUser['confirmation_certificate']);
            }
        }
    }
    return $processedData;
}

function getCurrentUserField($pdo, $id, $field) {
    $stmt = $pdo->prepare("SELECT $field FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result[$field] : null;
}

function cleanupOldFile($filePath) {
    if (empty($filePath)) return true;
    if (strpos($filePath, '/') === false && strpos($filePath, '\\') === false) {
        $filename = $filePath;
        $possiblePaths = [
            __DIR__ . '/../uploads/certificates/' . $filename,
            __DIR__ . '/../uploads/baptism_certificates/' . $filename,
            __DIR__ . '/../uploads/confirmation_certificates/' . $filename
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
                return true;
            }
        }
        return true;
    }
    if (file_exists($filePath)) {
        unlink($filePath);
        return true;
    }
    return true;
}
?>
