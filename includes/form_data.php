<?php
// Form data arrays for dropdowns
$occupations = [
    "ACCOUNTS", "AGRIBUSINESS", "AUDITORS", "BUILDING INDUSTRY", "CASUAL LABOUR",
    "CLERK/RECEPTIONIST", "COUNSELLING", "ENGINEER", "EVENTS MANAGEMENT", "FARMER",
    "FINANCIAL SECTOR", "GENERAL BUSINESS", "GOVT. ADMINISTRATION", "HAWKER/MARKET",
    "ICT", "M&E", "MANAGEMENT", "MEDIA/JOURNALIST", "MEDIC", "MOTIVATIONAL SPEAKER",
    "NUTRITIONIST", "REAL ESTATE", "TEACHING", "TRANSPORT", "VITAL GOVT STAFF"
];
$maritalStatuses = ["SINGLE", "MARRIED", "SINGLE PARENT", "WIDOW", "WIDOWER"];
$weddingTypes = ["CHURCH WEDDING", "TRADITIONAL", "CIVIL"];
$educationLevels = [
    "PRIMARY", "SECONDARY", "TECHNICAL/VOCATI", "UNDERGRADUATE",
    "POSTGRADUATE", "MASTERS", "DOCTORAL"
];
$services = ["SUNDAY SCHOOL", "TEENS SERVICE", "ENGLISH SERVICE", "KIKUYU SERVICE"];
$teams = ["ANTIOCH", "BASHAN", "CAANAN", "SHILLOH"];
$cellGroups = ["GACHORUE", "MOMBASA", "POSTAA", "POSTA B", "KAMBARA", "GITHIRIA"];
$departments = [
    "MOTHERS_UNION" => "MOTHERS' UNION",
    "KAMA" => "KAMA",
    "KAYO" => "KAYO",
    "CHOIR" => "CHOIR",
    "PRAISE_WORSHIP" => "PRAISE & WORSHIP",
    "LADIES_FORUM" => "LADIES FORUM",
    "MENS_FORUM" => "MENS' FORUM"
];
$ministries = [
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
];
$leadershipRoles = [
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
];

// Define clergy roles as a static array instead of querying the database
$clergyRoles = [
    '1' => 'Vicar',
        '2' => 'Curate Vicar',
                '3' => 'Lay Reader',
                '4' => 'Evangelist',
                '5' => 'Church Warden',
                '6' => 'Deacon'

];

// If you want to try to get clergy roles from the database (if the table exists),
// you can use this try-catch block as an alternative:
/*
try {
    $roleStmt = $pdo->prepare("SELECT DISTINCT role FROM clergy_records ORDER BY role");
    $roleStmt->execute();
    $clergyRolesResult = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to key-value format
    $clergyRoles = [];
    foreach ($clergyRolesResult as $row) {
        $key = strtoupper(str_replace(' ', '_', $row['role']));
        $clergyRoles[$key] = $row['role'];
    }
} catch (Exception $e) {
    // Fallback to default roles if query fails (table doesn't exist)
    $clergyRoles = [
        "SENIOR_PASTOR" => "Senior Pastor",
        "ASSOCIATE_PASTOR" => "Associate Pastor",
        "PASTOR" => "Pastor",
        "ELDER" => "Elder",
        "DEACON" => "Deacon",
        "DEACONESS" => "Deaconess",
        "MINISTER" => "Minister",
        "OTHER" => "Other"
    ];
}
*/
?>