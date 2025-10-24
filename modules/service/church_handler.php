<?php
/**
 * ==================================================
 * CHURCH SECTION HANDLER - CORRECTED
 * ==================================================
 * Handles all church-related updates across:
 * - user_details
 * - member_groups
 * - sacrament_records
 * * This file relies on the PDO connection ($pdo) and core
 * logic being loaded by the main router script.
 */

// If $project_root is not defined (it should be in the router), define it here
if (!isset($project_root)) {
    $project_root = dirname(dirname(__DIR__));
}

// --- Specific Dependencies for Church Handler ---
// The main router (personal_update.php) loads init.php and db.php.
// This handler only needs its specific helpers.
// NOTE: These files must exist in your project structure for this script to work.
require_once $project_root . '/helpers/file_upload_helper.php';
require_once $project_root . '/helpers/debug_helper.php';


/**
 * Update Church Details
 */
function updateChurchDetails($pdo, $user_id, $postData, $filesData = [])
{
    $pdo->beginTransaction();
    try {
        debugLog("Starting church details update for user: $user_id");

        $convertedData = convertChurchFormData($postData);
        validateChurchInputs($convertedData);

        // Fetch current data
        $currentUserDetails = getUserDetailsInfo($pdo, $user_id);
        $currentMemberGroup = getMemberGroupInfo($pdo, $user_id);
        $currentSacrament   = getSacramentInfo($pdo, $user_id);

        // Business logic
        $processedData = processChurchBusinessLogic($pdo, $user_id, $convertedData, $currentMemberGroup, $currentSacrament);

        // Handle file uploads
        $baptismCertificate = null;
        $confirmationCertificate = null;

        if (!empty($filesData['baptism_certificate']['name'])) {
            $baptismUpload = FileUploadHandler::handleFileUpload($filesData['baptism_certificate'], 'certificate', $user_id);
            if ($baptismUpload['success'] && isset($baptismUpload['relative_path'])) {
                $baptismCertificate = $baptismUpload['relative_path'];
                cleanupOldFile($currentSacrament['baptism_certificate'] ?? null);
            }
        }

        if (!empty($filesData['confirmation_certificate']['name'])) {
            $confirmUpload = FileUploadHandler::handleFileUpload($filesData['confirmation_certificate'], 'certificate', $user_id);
            if ($confirmUpload['success'] && isset($confirmUpload['relative_path'])) {
                $confirmationCertificate = $confirmUpload['relative_path'];
                cleanupOldFile($currentSacrament['confirmation_certificate'] ?? null);
            }
        }

        // Update tables
        updateUserDetailsTable($pdo, $user_id, $processedData);
        // *** FIX APPLIED HERE ***
        updateMemberGroupsTable($pdo, $user_id, $processedData, $currentMemberGroup); 
        updateSacramentRecordsTable($pdo, $user_id, $processedData, $currentSacrament, $baptismCertificate, $confirmationCertificate);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Church details updated successfully!',
            'updated_fields' => array_keys($processedData)
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        errorLog("Church details update failed", ['error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/* =====================================================
    HELPER FUNCTIONS (ALL IN ONE FILE)
    ===================================================== */

/** USER DETAILS INFO */
function getUserDetailsInfo($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT church_membership_no FROM user_details WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/** MEMBER GROUP INFO */
function getMemberGroupInfo($pdo, $user_id)
{
    // Keeping the original SELECT logic but noting the earlier discussion that 
    // if multiple records exist, this only fetches the first one.
    $stmt = $pdo->prepare("SELECT * FROM member_groups WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/** SACRAMENT INFO */
function getSacramentInfo($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM sacrament_records WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/** UPDATE USER_DETAILS */
function updateUserDetailsTable($pdo, $user_id, $data)
{
    $exists = $pdo->prepare("SELECT detail_id FROM user_details WHERE user_id = ?");
    $exists->execute([$user_id]);
    if ($exists->fetch()) {
        $stmt = $pdo->prepare("UPDATE user_details SET church_membership_no = ?, updated_at=NOW() WHERE user_id = ?");
        $stmt->execute([$data['church_membership_no'] ?? null, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_details (user_id, church_membership_no, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$user_id, $data['church_membership_no'] ?? null]);
    }
}

/** * UPDATE MEMBER GROUPS - FIXED 
 * Ensures all relevant fields are explicitly set to NULL if service is changed.
 */
function updateMemberGroupsTable($pdo, $user_id, $data, $current)
{
    $exists = $current ? true : false;
    
    // Explicitly pull values from the $data array (which was cleaned by Business Logic)
    $service_attending = $data['service_attending'] ?? null;
    $kikuyu_cell_group = $data['kikuyu_cell_group'] ?? null;
    $english_service_team = $data['english_service_team'] ?? null;
    $family_group = $data['family_group'] ?? null;
    
    // NOTE: If church_department and ministry_committee need to be managed by this form, 
    // they MUST be set to null in processChurchBusinessLogic and included here.
    // Since they weren't in the original UPDATE query, we assume they're managed elsewhere or can be ignored.

    if ($exists) {
        $sql = "UPDATE member_groups SET 
            service_attending=?, kikuyu_cell_group=?, english_service_team=?, family_group=?,
            updated_at=NOW()
            WHERE user_id=?";

        $pdo->prepare($sql)->execute([
            $service_attending,
            $kikuyu_cell_group,
            $english_service_team,
            $family_group,
            $user_id
        ]);
    } else {
        $sql = "INSERT INTO member_groups 
            (user_id, service_attending, kikuyu_cell_group, english_service_team, family_group, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        $pdo->prepare($sql)->execute([
            $user_id,
            $service_attending,
            $kikuyu_cell_group,
            $english_service_team,
            $family_group
        ]);
    }
}

/** UPDATE SACRAMENT RECORDS */
function updateSacramentRecordsTable($pdo, $user_id, $data, $current, $baptCert, $confirmCert)
{
    $exists = $current ? true : false;
    if ($exists) {
        $sql = "UPDATE sacrament_records SET 
            baptized=?, baptism_interest=?, confirmed=?, confirmation_interest=?, 
            want_to_be_baptized=?, want_to_be_confirmed=?, updated_at=NOW()";
        $params = [
            $data['baptized'] ?? null,
            $data['baptism_interest'] ?? null,
            $data['confirmed'] ?? null,
            $data['confirmation_interest'] ?? null,
            $data['want_to_be_baptized'] ?? null,
            $data['want_to_be_confirmed'] ?? null
        ];
        if ($baptCert) { $sql .= ", baptism_certificate=?"; $params[] = $baptCert; }
        if ($confirmCert) { $sql .= ", confirmation_certificate=?"; $params[] = $confirmCert; }
        $sql .= " WHERE user_id=?";
        $params[] = $user_id;
        $pdo->prepare($sql)->execute($params);
    } else {
        $sql = "INSERT INTO sacrament_records 
            (user_id, baptized, baptism_interest, confirmed, confirmation_interest, 
             want_to_be_baptized, want_to_be_confirmed, baptism_certificate, confirmation_certificate, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $pdo->prepare($sql)->execute([
            $user_id,
            $data['baptized'] ?? null,
            $data['baptism_interest'] ?? null,
            $data['confirmed'] ?? null,
            $data['confirmation_interest'] ?? null,
            $data['want_to_be_baptized'] ?? null,
            $data['want_to_be_confirmed'] ?? null,
            $baptCert, $confirmCert
        ]);
    }
}

/** FORM DATA CONVERSION */
function convertChurchFormData($postData)
{
    $converted = $postData;
    $map = [
        'English Service' => 'english', 'Kikuyu Service' => 'kikuyu',
        'Teens Service' => 'teens', 'Sunday School' => 'sunday_school',
        'english'=>'english', 'kikuyu'=>'kikuyu', 'teens'=>'teens', 'sunday_school'=>'sunday_school'
    ];
    if (isset($postData['service_attending'])) {
        $key = trim($postData['service_attending']);
        if (isset($map[$key])) $converted['service_attending'] = $map[$key];
    }
    foreach (['baptized','confirmed','want_to_be_baptized','want_to_be_confirmed'] as $f)
        if (isset($postData[$f])) $converted[$f] = strtolower(trim($postData[$f]));
    foreach (['baptism_interest','confirmation_interest'] as $f)
        if (isset($postData[$f])) {
            $v = strtolower(trim($postData[$f]));
            $converted[$f] = $v==='yes'?'interested':($v==='no'?'not_interested':$v);
        }
    return $converted;
}

/** VALIDATION */
function validateChurchInputs($postData)
{
    $services = ['english','kikuyu','teens','sunday_school',''];
    $yesNo = ['yes','no',''];
    $interest = ['interested','not_interested',''];

    if (isset($postData['service_attending']) && !in_array($postData['service_attending'], $services))
        throw new Exception('Invalid service selection.');
    if (isset($postData['baptized']) && !in_array($postData['baptized'], $yesNo))
        throw new Exception('Invalid baptism status.');
    if (isset($postData['confirmed']) && !in_array($postData['confirmed'], $yesNo))
        throw new Exception('Invalid confirmation status.');
}

/** BUSINESS LOGIC */
function processChurchBusinessLogic($pdo, $user_id, $postData, $currentGroups, $currentSacrament)
{
    $data = $postData;
    if (isset($data['service_attending']) && !empty($data['service_attending'])) {
        $current = $currentGroups['service_attending'] ?? null;
        
        // If the service has changed, clear the related groups/teams
        if ($data['service_attending'] !== $current) {
            $data['english_service_team'] = null;
            $data['kikuyu_cell_group'] = null;
            $data['family_group'] = null;
        } else {
            // If service didn't change, ensure fields for the *other* service are explicitly null
            if ($data['service_attending'] === 'english') {
                $data['kikuyu_cell_group'] = null;
                $data['family_group'] = null;
            } elseif ($data['service_attending'] === 'kikuyu') {
                $data['english_service_team'] = null;
            } else { // teens or sunday_school
                $data['english_service_team'] = null;
                $data['kikuyu_cell_group'] = null;
                $data['family_group'] = null;
            }
        }
    }
    
    // Clear certificate links/files if user selects "No"
    if (isset($data['baptized']) && $data['baptized']==='no') {
        cleanupOldFile($currentSacrament['baptism_certificate'] ?? null);
        $data['baptism_certificate'] = null; // Explicitly set the certificate field to null in $data
    }
    if (isset($data['confirmed']) && $data['confirmed']==='no') {
        cleanupOldFile($currentSacrament['confirmation_certificate'] ?? null);
        $data['confirmation_certificate'] = null; // Explicitly set the certificate field to null in $data
    }
    
    return $data;
}

/** FILE CLEANUP */
function cleanupOldFile($file)
{
    // ... (This function remains unchanged as it was correct for file deletion)
    if (empty($file)) return;
    $project_root = dirname(dirname(__DIR__));
    $paths = [
        $project_root."/uploads/certificates/$file",
        $project_root."/uploads/baptism_certificates/$file",
        $project_root."/uploads/confirmation_certificates/$file"
    ];
    foreach ($paths as $p) if (file_exists($p)) unlink($p);
}