<?php
// Include the form_data.php to get the arrays
require_once 'form_data.php';

// Create display name mappings from form_data arrays
$display_mappings = [
    'services' => [
        "SUNDAY SCHOOL" => "Sunday School",
        "TEENS SERVICE" => "Teens Service",
        "ENGLISH SERVICE" => "English Service",
        "KIKUYU SERVICE" => "Kikuyu Service"
    ],
    'teams' => [
        "ANTIOCH" => "Antioch",
        "BASHAN" => "Bashan",
        "CAANAN" => "Caanan",
        "SHILLOH" => "Shilloh"
    ],
    'cellGroups' => [
        "GACHORUE" => "Gachorue",
        "MOMBASA" => "Mombasa",
        "POSTAA" => "Posta A",
        "POSTA B" => "Posta B",
        "KAMBARA" => "Kambara",
        "GITHIRIA" => "Githiria"
    ],
    'departments' => [
        "MOTHERS_UNION" => "MOTHERS' UNION",
        "KAMA" => "KAMA",
        "KAYO" => "KAYO",
        "CHOIR" => "CHOIR",
        "PRAISE_WORSHIP" => "PRAISE & WORSHIP",
        "LADIES_FORUM" => "LADIES FORUM",
        "MENS_FORUM" => "MENS' FORUM"
    ],
    'ministries' => [
        "PCC" => "PCC",
        "USHERING" => "USHERING",
        "DORCAS" => "DORCAS",
        "CELL_GROUP_COMM" => "CELL GROUP COMMITTEE",
        "DEVELOPMENT" => "DEVELOPMENT",
        "STRATEGIC_PLAN" => "STRATEGIC PLAN",
        "SUNDAY_SCHOOL" => "SUNDAY SCHOOL",
        "TE" => "TE",
        "MISSIONS" => "MISSIONS",
        "ABLED_DIFFERENTLY" => "ABLED-DIFFERENTLY",
        "SPORTS_FITNESS" => "SPORTS & FITNESS",
        "INTERCESSORY" => "INTERCESSORY",
        "ICT" => "ICT",
        "SOUND_INSTRUMENT" => "SOUND & INSTRUMENT"
    ],
    'leadershipRoles' => [
        "CHAIRPERSON" => "Chairperson",
        "VICE_CHAIRPERSON" => "Vice Chairperson",
        "SECRETARY" => "Secretary",
        "ASSISTANT_SECRETARY" => "Assistant Secretary",
        "TREASURER" => "Treasurer",
        "ASSISTANT_TREASURER" => "Assistant Treasurer",
        "ORGANIZING_SECRETARY" => "Organizing Secretary",
        "PRAYER_COORDINATOR" => "Prayer Coordinator",
        "TRAINING_COORDINATOR" => "Training/Discipleship Coordinator",
        "OUTREACH_COORDINATOR" => "Outreach/Evangelism Coordinator",
        "WELFARE_OFFICER" => "Welfare Officer",
        "COMMUNICATIONS_OFFICER" => "Communications Officer",
        "OTHER" => "Other"
    ],
    'clergyRoles' => [
        '1' => 'Vicar',
        '2' => 'Curate Vicar',
        '3' => 'Lay Reader',
        '4' => 'Evangelist',
        '5' => 'Church Warden',
        '6' => 'Deacon'
    ]
];

// Function to get display name from value
function getDisplayName($value, $type) {
    global $display_mappings;
    
    if (empty($value)) {
        return 'Not specified';
    }
    
    if (isset($display_mappings[$type][$value])) {
        return $display_mappings[$type][$value];
    }
    
    return $value; // Return original if not found
}

// Function to get user's church information with display names
function getUserChurchInfo($pdo, $userId) {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return null;
    }
    
    // Add display names for church-related fields
    $user['display_service_attending'] = getDisplayName($user['service_attending'], 'services');
    $user['display_english_service_team'] = getDisplayName($user['english_service_team'], 'teams');
    $user['display_kikuyu_cell_group'] = getDisplayName($user['kikuyu_cell_group'], 'cellGroups');
    
    // Get departments and ministries as arrays
    $user['departments'] = isset($user['departments']) ? explode(',', $user['departments']) : [];
    $user['ministries'] = isset($user['ministries']) ? explode(',', $user['ministries']) : [];
    
    // Convert departments and ministries to display names
    $user['display_departments'] = [];
    foreach ($user['departments'] as $dept) {
        $user['display_departments'][] = getDisplayName($dept, 'departments');
    }
    
    $user['display_ministries'] = [];
    foreach ($user['ministries'] as $ministry) {
        $user['display_ministries'][] = getDisplayName($ministry, 'ministries');
    }
    
    return $user;
}

// Function to get user's employment history
function getUserEmploymentHistory($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM employment_history 
        WHERE user_id = ? 
        ORDER BY is_current DESC, to_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get user's leadership roles with display names
function getUserLeadershipRoles($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM user_leadership 
        WHERE user_id = ? 
        ORDER BY is_current DESC, to_date DESC
    ");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert leadership roles to display names
    foreach ($roles as &$role) {
        $role['display_role'] = getDisplayName($role['role'], 'leadershipRoles');
        
        if ($role['leadership_type'] == 'department' && !empty($role['department'])) {
            $role['display_entity'] = getDisplayName($role['department'], 'departments');
        } elseif ($role['leadership_type'] == 'ministry' && !empty($role['ministry'])) {
            $role['display_entity'] = getDisplayName($role['ministry'], 'ministries');
        }
    }
    
    return $roles;
}

// Function to get user's clergy information with display names
function getUserClergyInfo($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM clergy_roles 
        WHERE user_id = ? 
        ORDER BY serving_from_date DESC
    ");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert clergy roles to display names
    foreach ($roles as &$role) {
        $role['display_role'] = getDisplayName($role['role_id'], 'clergyRoles');
    }
    
    return $roles;
}
?>