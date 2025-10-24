<?php
/**
 * ===================================================
 * EMPLOYMENT SECTION HANDLER 
 * ===================================================
 * Core business logic for all employment history updates (Delete/Re-Insert).
 * This file is required by employment_update.php.
 */

if (!isset($project_root)) {
    $project_root = dirname(dirname(__DIR__));
}

// NOTE: Assume your dependency includes (like file_upload_helper, debug_helper) 
// are available via $project_root/helpers/...
// require_once $project_root . '/helpers/debug_helper.php';

/**
 * Core function to update all employment details for a user.
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param array $postData POST data from form
 * @return array Response array with success status and message
 */
function updateEmploymentDetails($pdo, $user_id, $postData) 
{
    $pdo->beginTransaction();
    try {
        
        // 1. Delete all existing employment records for this user (Bulk Save Strategy)
        $deleteStmt = $pdo->prepare("DELETE FROM employment_history WHERE user_id = ?");
        $deleteStmt->execute([$user_id]);
        
        $insertedRecords = 0;

        // 2. Check and re-insert employment data
        if (isset($postData['job_title']) && is_array($postData['job_title'])) {
            
            $insertStmt = $pdo->prepare("INSERT INTO employment_history 
                (user_id, job_title, company, from_date, to_date, is_current) 
                VALUES (?, ?, ?, ?, ?, ?)");
            
            $titles = $postData['job_title'];
            $companies = $postData['company'];
            $fromDates = $postData['employment_from_date'];
            $toDates = $postData['employment_to_date'];
            $isCurrentArray = $postData['is_current_employment'] ?? [];

            for ($i = 0; $i < count($titles); $i++) {
                $jobTitle = trim($titles[$i]);
                if (empty($jobTitle)) continue; 
                
                $company = trim($companies[$i]);
                $fromDate = trim($fromDates[$i]);
                $toDate = trim($toDates[$i]);
                
                $finalToDate = !empty($toDate) ? $toDate : null;
                $isCurrent = $isCurrentArray[$i] ?? 0;
                
                $insertStmt->execute([
                    $user_id, 
                    $jobTitle, 
                    $company, 
                    $fromDate, 
                    $finalToDate, 
                    $isCurrent
                ]);
                $insertedRecords++;
            }
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Employment details updated successfully! ' . $insertedRecords . ' records saved.',
            'records_updated' => $insertedRecords
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // errorLog("Employment update failed: " . $e->getMessage(), $e);
        
        return [
            'success' => false,
            'message' => 'Failed to update employment details. Error: ' . $e->getMessage()
        ];
    }
}