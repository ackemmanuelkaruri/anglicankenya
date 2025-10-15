<?php
/**
 * Employment Details Section Handler
 * Compatible with existing usection_update.php structure
 */

// Using existing debug helper with DebugLogger class - no additional includes needed

/**
 * Update employment details for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $id User ID
 * @param array $postData POST data from form
 * @param array $filesData FILES data (not used for employment but keeping signature consistent)
 * @return array Response array with success status and message
 */
function updateEmploymentDetails($pdo, $id, $postData, $filesData = []) {
    try {
        debugLog("=== EMPLOYMENT UPDATE STARTED ===");
        debugLog("Starting employment details update for user: $id");
        debugLogArray("Employment POST data", $postData, 'DEBUG');

        // First, delete existing employment records for this user
        $deleteStmt = $pdo->prepare("DELETE FROM employment_history WHERE user_id = ?");
        $deleteResult = $deleteStmt->execute([$id]);
        $deletedRows = $deleteStmt->rowCount();
        debugLogDBOperation('DELETE', $deletedRows, 'employment_history', 'INFO');

        $insertedRecords = 0;

        // Check if we have employment data to process
        if (isset($postData['job_title']) && is_array($postData['job_title'])) {
            debugLog("Processing array format employment data");
            
            $insertStmt = $pdo->prepare("INSERT INTO employment_history (user_id, job_title, company, from_date, to_date, is_current) VALUES (?, ?, ?, ?, ?, ?)");

            for ($i = 0; $i < count($postData['job_title']); $i++) {
                $jobTitle = trim($postData['job_title'][$i] ?? '');
                $company = trim($postData['company'][$i] ?? '');
                $fromDate = $postData['employment_from_date'][$i] ?? null;
                $toDate = $postData['employment_to_date'][$i] ?? null;
                
                // Handle checkbox array - check if this index is in the is_current_employment array
                $isCurrent = 0;
                if (isset($postData['is_current_employment']) && is_array($postData['is_current_employment'])) {
                    $isCurrent = in_array($i, $postData['is_current_employment']) ? 1 : 0;
                }

                debugLog("Processing employment record $i:");
                debugLog("  Job Title: '$jobTitle'");
                debugLog("  Company: '$company'");
                debugLog("  From Date: '$fromDate'");
                debugLog("  To Date: '$toDate'");
                debugLog("  Is Current: $isCurrent");

                // Skip completely empty records
                if (empty($jobTitle) && empty($company) && empty($fromDate)) {
                    debugLog("Skipping completely empty employment record $i");
                    continue;
                }

                // Validate required fields for non-empty records
                if (empty($jobTitle)) {
                    throw new Exception("Job title is required for employment record " . ($i + 1));
                }
                
                if (empty($company)) {
                    throw new Exception("Company is required for employment record " . ($i + 1));
                }

                // Handle date formatting and validation
                $fromDate = !empty($fromDate) ? $fromDate : null;
                $toDate = !empty($toDate) ? $toDate : null;

                // If current job, set to_date to null
                if ($isCurrent) {
                    $toDate = null;
                    debugLog("Setting to_date to null for current employment");
                }

                // Validate date logic
                if ($fromDate && $toDate && !$isCurrent) {
                    if (strtotime($fromDate) >= strtotime($toDate)) {
                        throw new Exception("End date must be after start date for employment record " . ($i + 1));
                    }
                }

                // Execute the insert
                debugLogQuery("INSERT INTO employment_history", [$id, $jobTitle, $company, $fromDate, $toDate, $isCurrent], 'DEBUG');
                $success = $insertStmt->execute([$id, $jobTitle, $company, $fromDate, $toDate, $isCurrent]);
                
                if (!$success) {
                    $errorInfo = $insertStmt->errorInfo();
                    debugLog("Insert error: " . print_r($errorInfo, true));
                    throw new Exception('Failed to insert employment record ' . ($i + 1) . ': ' . $errorInfo[2]);
                }

                $insertedRecords++;
                debugLog("Successfully inserted employment record $i: '$jobTitle' at '$company'");
            }
        } else if (isset($postData['job_title']) && !is_array($postData['job_title'])) {
            // Handle single employment record (not array format)
            debugLog("Processing single employment record format");
            
            $jobTitle = trim($postData['job_title'] ?? '');
            $company = trim($postData['company'] ?? '');
            $fromDate = $postData['employment_from_date'] ?? null;
            $toDate = $postData['employment_to_date'] ?? null;
            $isCurrent = isset($postData['is_current_employment']) ? 1 : 0;
            
            if (!empty($jobTitle) && !empty($company)) {
                $insertStmt = $pdo->prepare("INSERT INTO employment_history (user_id, job_title, company, from_date, to_date, is_current) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($isCurrent) {
                    $toDate = null;
                }
                
                $success = $insertStmt->execute([$id, $jobTitle, $company, $fromDate, $toDate, $isCurrent]);
                
                if ($success) {
                    $insertedRecords++;
                    debugLog("Successfully inserted single employment record: '$jobTitle' at '$company'");
                } else {
                    $errorInfo = $insertStmt->errorInfo();
                    throw new Exception('Failed to insert employment record: ' . $errorInfo[2]);
                }
            } else {
                debugLog("Single employment record is empty - skipping");
            }
        } else {
            debugLog("No employment data found to process");
        }

        debugLogDBOperation('INSERT', $insertedRecords, 'employment_history', 'INFO');
        debugLog("Employment details updated successfully for user: $id");
        debugLog("Total records inserted: $insertedRecords");
        debugLog("=== EMPLOYMENT UPDATE COMPLETED ===");
        
        $message = $insertedRecords > 0 
            ? "Employment details updated successfully! ($insertedRecords employment record(s) saved)" 
            : "Employment section updated successfully! (No employment records to save)";
            
        return [
            'success' => true, 
            'message' => $message,
            'records_inserted' => $insertedRecords
        ];

    } catch (Exception $e) {
        debugLog("Employment details error: " . $e->getMessage());
        debugLog("=== EMPLOYMENT UPDATE FAILED ===");
        return [
            'success' => false, 
            'message' => 'Error updating employment details: ' . $e->getMessage(),
            'records_inserted' => 0
        ];
    }
}

/**
 * Validate employment data
 * 
 * @param array $postData POST data to validate
 * @return array Array of validation errors (empty if valid)
 */
function validateEmploymentData($postData) {
    $errors = [];

    debugLog("=== EMPLOYMENT VALIDATION STARTED ===");
    debugLogArray("Data to validate", $postData, 'DEBUG');

    if (isset($postData['job_title']) && is_array($postData['job_title'])) {
        for ($i = 0; $i < count($postData['job_title']); $i++) {
            $jobTitle = trim($postData['job_title'][$i] ?? '');
            $company = trim($postData['company'][$i] ?? '');
            $fromDate = $postData['employment_from_date'][$i] ?? '';
            $toDate = $postData['employment_to_date'][$i] ?? '';
            
            // Check if this index is marked as current
            $isCurrent = false;
            if (isset($postData['is_current_employment']) && is_array($postData['is_current_employment'])) {
                $isCurrent = in_array($i, $postData['is_current_employment']);
            }

            // Skip validation for completely empty records
            if (empty($jobTitle) && empty($company) && empty($fromDate)) {
                debugLog("Skipping validation for completely empty record $i");
                continue;
            }

            // Validate required fields for non-empty records
            if (empty($jobTitle)) {
                $errors[] = "Job title is required for employment record " . ($i + 1);
            }
            if (empty($company)) {
                $errors[] = "Company is required for employment record " . ($i + 1);
            }

            // Validate dates
            if (!empty($fromDate) && !empty($toDate) && !$isCurrent) {
                if (strtotime($fromDate) >= strtotime($toDate)) {
                    $errors[] = "End date must be after start date for employment record " . ($i + 1);
                }
            }
        }
    } else if (isset($postData['job_title']) && !is_array($postData['job_title'])) {
        // Handle single record validation
        $jobTitle = trim($postData['job_title'] ?? '');
        $company = trim($postData['company'] ?? '');
        $fromDate = $postData['employment_from_date'] ?? '';
        $toDate = $postData['employment_to_date'] ?? '';
        $isCurrent = isset($postData['is_current_employment']);

        if (!empty($jobTitle) || !empty($company) || !empty($fromDate)) {
            if (empty($jobTitle)) {
                $errors[] = "Job title is required";
            }
            if (empty($company)) {
                $errors[] = "Company is required";
            }

            if (!empty($fromDate) && !empty($toDate) && !$isCurrent) {
                if (strtotime($fromDate) >= strtotime($toDate)) {
                    $errors[] = "End date must be after start date";
                }
            }
        }
    }

    debugLogArray("Validation errors found", $errors, 'INFO');
    debugLog("=== EMPLOYMENT VALIDATION COMPLETED ===");
    return $errors;
}

/**
 * Get user's employment history
 * 
 * @param PDO $pdo Database connection
 * @param int $id User ID
 * @return array Array of employment records
 */
function getUserEmploymentHistory($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM employment_history WHERE user_id = ? ORDER BY is_current DESC, from_date DESC");
        $stmt->execute([$id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        debugLog("Found " . count($results) . " employment records for user $id");
        return $results;
    } catch (Exception $e) {
        debugLog("Error fetching employment history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get current employment for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $id User ID
 * @return array|null Current employment record or null if none found
 */
function getCurrentEmployment($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM employment_history WHERE user_id = ? AND is_current = 1 LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        debugLog($result ? "Found current employment for user $id" : "No current employment found for user $id");
        return $result ?: null;
    } catch (Exception $e) {
        debugLog("Error fetching current employment: " . $e->getMessage());
        return null;
    }
}
?>