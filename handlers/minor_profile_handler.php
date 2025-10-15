<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'create_minor_profile':
            createMinorProfile($pdo, $user_id);
            break;
            
        case 'update_minor_profile':
            updateMinorProfile($pdo, $user_id);
            break;
            
        case 'request_activation':
            requestActivation($pdo, $user_id);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log("Minor profile handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createMinorProfile($pdo, $user_id) {
    $familyMemberId = intval($_POST['family_member_id'] ?? 0);
    
    if (!$familyMemberId) {
        throw new Exception('Invalid family member');
    }
    
    // Verify the family member belongs to the user and is a minor
    $memberStmt = $pdo->prepare("
        SELECT * FROM family_members 
        WHERE id = ? AND user_id = ? AND is_minor = 1
    ");
    $memberStmt->execute([$familyMemberId, $user_id]);
    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        throw new Exception('Family member not found or not a minor');
    }
    
    // Check if profile already exists
    $existingStmt = $pdo->prepare("
        SELECT id FROM minor_profiles WHERE family_member_id = ?
    ");
    $existingStmt->execute([$familyMemberId]);
    
    if ($existingStmt->fetch()) {
        throw new Exception('Profile already exists for this minor');
    }
    
    // Create minor profile
    $insertStmt = $pdo->prepare("
        INSERT INTO minor_profiles (family_member_id, user_id) 
        VALUES (?, ?)
    ");
    $insertStmt->execute([$familyMemberId, $user_id]);
    
    $profileId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Minor profile created successfully',
        'profile_id' => $profileId
    ]);
}

function updateMinorProfile($pdo, $user_id) {
    $profileId = intval($_POST['profile_id'] ?? 0);
    $tabSection = $_POST['tab_section'] ?? '';
    
    if (!$profileId) {
        throw new Exception('Invalid profile ID');
    }
    
    // Verify profile belongs to user
    $profileStmt = $pdo->prepare("
        SELECT * FROM minor_profiles WHERE id = ? AND user_id = ?
    ");
    $profileStmt->execute([$profileId, $user_id]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        throw new Exception('Profile not found');
    }
    
    // Update based on tab section
    switch ($tabSection) {
        case 'personal':
            updatePersonalInfo($pdo, $profileId);
            break;
        case 'church':
            updateChurchInfo($pdo, $profileId);
            break;
        case 'leadership':
            updateLeadershipInfo($pdo, $profileId);
            break;
        default:
            throw new Exception('Invalid tab section');
    }
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
}

function updatePersonalInfo($pdo, $profileId) {
    $stmt = $pdo->prepare("
        UPDATE minor_profiles SET
            address = ?,
            emergency_contact_name = ?,
            emergency_contact_phone = ?,
            medical_conditions = ?,
            allergies = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['address'] ?? null,
        $_POST['emergency_contact_name'] ?? null,
        $_POST['emergency_contact_phone'] ?? null,
        $_POST['medical_conditions'] ?? null,
        $_POST['allergies'] ?? null,
        $profileId
    ]);
}

function updateChurchInfo($pdo, $profileId) {
    $stmt = $pdo->prepare("
        UPDATE minor_profiles SET
            baptism_date = ?,
            baptism_location = ?,
            confirmation_date = ?,
            confirmation_location = ?,
            church_membership_date = ?,
            sunday_school_class = ?,
            youth_group = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['baptism_date'] ?: null,
        $_POST['baptism_location'] ?? null,
        $_POST['confirmation_date'] ?: null,
        $_POST['confirmation_location'] ?? null,
        $_POST['church_membership_date'] ?: null,
        $_POST['sunday_school_class'] ?? null,
        $_POST['youth_group'] ?? null,
        $profileId
    ]);
}

function updateLeadershipInfo($pdo, $profileId) {
    $stmt = $pdo->prepare("
        UPDATE minor_profiles SET
            leadership_interests = ?,
            volunteer_activities = ?,
            skills_talents = ?,
            leadership_training_completed = ?,
            mentor_assigned = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['leadership_interests'] ?? null,
        $_POST['volunteer_activities'] ?? null,
        $_POST['skills_talents'] ?? null,
        $_POST['leadership_training_completed'] ?? null,
        $_POST['mentor_assigned'] ?? null,
        $profileId
    ]);
}

function requestActivation($pdo, $user_id) {
    $profileId = intval($_POST['profile_id'] ?? 0);
    
    if (!$profileId) {
        throw new Exception('Invalid profile ID');
    }
    
    // Get profile with age calculation
    $profileStmt = $pdo->prepare("
        SELECT mp.*, fm.minor_date_of_birth,
               FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) as current_age,
               fm.minor_first_name, fm.minor_last_name
        FROM minor_profiles mp
        JOIN family_members fm ON mp.family_member_id = fm.id
        WHERE mp.id = ? AND mp.user_id = ?
    ");
    $profileStmt->execute([$profileId, $user_id]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        throw new Exception('Profile not found');
    }
    
    if ($profile['current_age'] < 18) {
        throw new Exception('Cannot request activation: Minor is still under 18 years old');
    }
    
    // Check for existing pending request
    $existingStmt = $pdo->prepare("
        SELECT id FROM minor_activation_requests 
        WHERE minor_profile_id = ? AND status = 'pending'
    ");
    $existingStmt->execute([$profileId]);
    
    if ($existingStmt->fetch()) {
        throw new Exception('Activation request already pending');
    }
    
    // Create activation request
    $requestStmt = $pdo->prepare("
        INSERT INTO minor_activation_requests 
        (minor_profile_id, requested_by_user_id, minor_age_at_request, request_reason) 
        VALUES (?, ?, ?, ?)
    ");
    
    $reason = "Minor has reached 18 years of age and is ready for account activation";
    $requestStmt->execute([$profileId, $user_id, $profile['current_age'], $reason]);
    
    // Update profile status
    $updateStmt = $pdo->prepare("
        UPDATE minor_profiles SET 
            activation_request_date = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $updateStmt->execute([$profileId]);
    
    // Notify admins (you can implement email notification here)
    notifyAdminsOfActivationRequest($pdo, $profile);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Activation request submitted successfully. An administrator will review it shortly.'
    ]);
}

function notifyAdminsOfActivationRequest($pdo, $profile) {
    // Get all admin users
    $adminStmt = $pdo->prepare("
        SELECT email, first_name FROM users WHERE role = 'admin' AND is_active = 1
    ");
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $minorName = $profile['minor_first_name'] . ' ' . $profile['minor_last_name'];
    
    foreach ($admins as $admin) {
        // Send email notification (implement your email service here)
        $subject = "Minor Account Activation Request";
        $message = "
            Dear {$admin['first_name']},
            
            A new account activation request has been submitted for:
            Minor: {$minorName}
            Age: {$profile['current_age']} years
            
            Please review this request in the admin dashboard.
            
            Best regards,
            Church Management System
        ";
        
        // Use your email service here
        // sendEmail($admin['email'], $subject, $message);
    }
}
?>