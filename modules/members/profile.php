<?php
/**
 * ============================================
 * MEMBER PROFILE UPDATE PAGE (LINKED SECTIONS)
 * ============================================
 */

require_once '../../includes/init.php';
require_once '../../includes/rbac.php';
require_once '../../includes/scope_helpers.php';
require_once '../../db.php';

start_secure_session();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$id = $_SESSION['user_id'];
$role_level = $_SESSION['role_level'] ?? 'member';

// Fetch user basic info with hierarchical information (same as dashboard)
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        o.org_name,
        o.org_code,
        p.parish_name,
        d.deanery_name,
        a.archdeaconry_name,
        dio.diocese_name,
        prov.province_name
    FROM users u
    LEFT JOIN organizations o ON u.organization_id = o.id
    LEFT JOIN parishes p ON u.parish_id = p.parish_id
    LEFT JOIN deaneries d ON u.deanery_id = d.deanery_id
    LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
    LEFT JOIN dioceses dio ON u.diocese_id = dio.diocese_id
    LEFT JOIN provinces prov ON u.province_id = prov.province_id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found. Please <a href='../../login.php'>login</a> again.");
}

// Fetch user details from user_details table
$detailsStmt = $pdo->prepare("SELECT * FROM user_details WHERE user_id = ?");
$detailsStmt->execute([$id]);
$userDetails = $detailsStmt->fetch(PDO::FETCH_ASSOC);

// If no details record exists, create an empty array with default values
if (!$userDetails) {
    $userDetails = [
        'user_id' => $id,
        'occupation' => '',
        'marital_status' => '',
        'wedding_type' => '',
        'education_level' => '',
        'country' => 'Kenya',
        'passport' => '',
        'church_membership_no' => ''
    ];
}

// Set role_class for styling
$role_class = "role-{$role_level}";

// Include form data arrays for dropdowns
include '../../includes/form_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Profile - Church Management System</title>

<!-- Global CSS -->
<?php include '../../includes/styles.php'; ?>
<link href="../../css/dashboard.css" rel="stylesheet">
<link href="../../css/themes.css" rel="stylesheet">
<link href="../../css/Profile.css" rel="stylesheet">
<link href="../../css/role-colors.css" rel="stylesheet">
<link href="../../css/impersonation-banner.css" rel="stylesheet">
<link rel="stylesheet" href="../service/church_section.css">
<link rel="stylesheet" href="../ministries/ministry_section.css">
<link rel="stylesheet" href="../clergy/clergy_section.css">
<link rel="stylesheet" href="../employment/employment_section.css">
<link rel="stylesheet" href="../families/family_section.css">
<link rel="stylesheet" href="../leadership/leadership_section.css">


</head>

<body data-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<?php include '../../header.php'; ?>

<!-- Include Sidebar (same as dashboard) -->
<?php include '../../includes/dashboard_sidebar.php'; ?>

<div class="main-content">
    <div class="page-header role-<?php echo $_SESSION['role_level']; ?>">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Update Your Profile</h2>
                <p class="text-muted mb-0">Edit your personal, church, and family information.</p>
            </div>
            <a href="../../dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container py-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                    <i class="fas fa-user"></i> Personal Info
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="church-tab" data-bs-toggle="tab" data-bs-target="#church" type="button" role="tab" aria-controls="church" aria-selected="false">
                    <i class="fas fa-church"></i> Church Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment" type="button" role="tab" aria-controls="employment" aria-selected="false">
                    <i class="fas fa-briefcase"></i> Employment
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clergy-tab" data-bs-toggle="tab" data-bs-target="#clergy" type="button" role="tab" aria-controls="clergy" aria-selected="false">
                    <i class="fas fa-cross"></i> Clergy/Laity
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ministry-tab" data-bs-toggle="tab" data-bs-target="#ministry" type="button" role="tab" aria-controls="ministry" aria-selected="false">
                    <i class="fas fa-hands-praying"></i> Ministries
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="leadership-tab" data-bs-toggle="tab" data-bs-target="#leadership" type="button" role="tab" aria-controls="leadership" aria-selected="false">
                    <i class="fas fa-users-cog"></i> Leadership
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="family-tab" data-bs-toggle="tab" data-bs-target="#family" type="button" role="tab" aria-controls="family" aria-selected="false">
                    <i class="fas fa-users"></i> Family Members
                </button>
            </li>
        </ul>

        <!-- Main Form -->
        <form id="update-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

            <div class="tab-content" id="profileTabContent">
                <!-- Personal Info Tab -->
                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                    <?php include 'personal_section.php'; ?>
                </div>
                
                <!-- Church Details Tab -->
                <div class="tab-pane fade" id="church" role="tabpanel">
                    <?php include '../service/church_section.php'; ?>
                </div>
                
             <!-- Employment Tab -->
                <div class="tab-pane fade" id="employment" role="tabpanel">
                <?php include '../employment/employment_section.php'; ?>
                </div>
                
                <!-- Clergy Tab -->
                <div class="tab-pane fade" id="clergy" role="tabpanel">
                <?php include '../clergy/clergy_section.php'; ?>
                </div>
                
                <!-- Ministry Tab -->
                <div class="tab-pane fade" id="ministry" role="tabpanel">
                    <?php include '../ministries/ministry_section.php'; ?>
                </div>
                
                <!-- Leadership Tab -->
                <div class="tab-pane fade" id="leadership" role="tabpanel">
                    <?php include '../leadership/leadership_section.php'; ?>
                </div>
                
                <!-- Family Tab -->
                <div class="tab-pane fade" id="family" role="tabpanel">
                    <?php include '../families/family_section.php'; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/scripts.php'; ?>
<script src="./js/personal-section.js"></script>
<script src="../../js/dashboard.js"></script>

<script>
// Initialize form validation and user ID for JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Make user ID available globally for section-save.js
    window.userId = <?= json_encode($user['id']) ?>;
    
    // Add data attribute to body for easy access
    document.body.setAttribute('data-user-id', <?= json_encode($user['id']) ?>);
    
    console.log('Profile page initialized with user ID:', window.userId);
});
</script>
</body>
</html>