<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Get minor profile ID
$profileId = intval($_GET['id'] ?? 0);
if (!$profileId) {
    header('Location: dashboard.php');
    exit;
}

// Get minor profile with permissions
$profileStmt = $pdo->prepare("
    SELECT mp.*, fm.minor_first_name, fm.minor_last_name, fm.minor_date_of_birth,
           fm.relationship, fm.minor_email, fm.minor_phone,
           FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) as current_age,
           u.first_name as guardian_first_name, u.last_name as guardian_last_name
    FROM minor_profiles mp
    JOIN family_members fm ON mp.family_member_id = fm.id
    JOIN users u ON mp.user_id = u.id
    WHERE mp.id = ? AND mp.user_id = ?
");
$profileStmt->execute([$profileId, $_SESSION['user_id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    header('Location: dashboard.php');
    exit;
}

// Get tab permissions
$permissionsStmt = $pdo->prepare("
    SELECT * FROM minor_tab_permissions 
    WHERE minor_profile_id = ? 
    ORDER BY tab_name
");
$permissionsStmt->execute([$profileId]);
$permissions = $permissionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize permissions by tab name
$tabPermissions = [];
foreach ($permissions as $perm) {
    $tabPermissions[$perm['tab_name']] = $perm;
}

$currentAge = $profile['current_age'];
$canRequestActivation = $currentAge >= 18 && !$profile['is_ready_for_activation'];

// Check if there's a pending activation request
$pendingRequestStmt = $pdo->prepare("
    SELECT * FROM minor_activation_requests 
    WHERE minor_profile_id = ? AND status = 'pending'
");
$pendingRequestStmt->execute([$profileId]);
$pendingRequest = $pendingRequestStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['minor_first_name'] . ' ' . $profile['minor_last_name']) ?> - Profile</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/minor_profile.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="profile-header">
                <div class="profile-info">
                    <h1><?= htmlspecialchars($profile['minor_first_name'] . ' ' . $profile['minor_last_name']) ?></h1>
                    <div class="profile-meta">
                        <span class="age-badge <?= $currentAge >= 18 ? 'adult' : 'minor' ?>">
                            <?= $currentAge ?> years old
                        </span>
                        <span class="relationship-badge">
                            <?= htmlspecialchars($profile['relationship']) ?>
                        </span>
                        <?php if ($currentAge >= 18): ?>
                            <span class="status-badge <?= $profile['is_ready_for_activation'] ? 'ready' : 'pending' ?>">
                                <?= $profile['is_ready_for_activation'] ? 'Ready for Activation' : 'Awaiting Activation' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if ($canRequestActivation && !$pendingRequest): ?>
                        <button type="button" class="btn-request-activation" data-profile-id="<?= $profileId ?>">
                            Request Account Activation
                        </button>
                    <?php elseif ($pendingRequest): ?>
                        <span class="pending-request">
                            Activation request pending admin approval
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Age Restriction Notice -->
            <?php if ($currentAge < 18): ?>
                <div class="age-restriction-notice">
                    <i class="icon-info"></i>
                    <p>Some tabs will become available when <?= htmlspecialchars($profile['minor_first_name']) ?> turns 18. 
                       Currently <?= 18 - $currentAge ?> years until full access.</p>
                </div>
            <?php endif; ?>
            
            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <div class="tab-nav">
                    <?php
                    $tabs = [
                        'personal' => ['title' => 'Personal Info', 'icon' => 'user'],
                        'church' => ['title' => 'Church Details', 'icon' => 'church'],
                        'leadership' => ['title' => 'Leadership', 'icon' => 'star'],
                        'ministry' => ['title' => 'Ministry', 'icon' => 'heart'],
                        'financial' => ['title' => 'Financial', 'icon' => 'dollar'],
                        'advanced_leadership' => ['title' => 'Advanced Leadership', 'icon' => 'crown'],
                        'adult_programs' => ['title' => 'Adult Programs', 'icon' => 'users']
                    ];
                    
                    foreach ($tabs as $tabKey => $tab) {
                        $permission = $tabPermissions[$tabKey] ?? null;
                        $isAccessible = $permission ? $permission['is_accessible'] : false;
                        $ageReq = $permission ? $permission['age_requirement'] : 18;
                        
                        $tabClass = 'tab-button';
                        if (!$isAccessible) {
                            $tabClass .= ' locked';
                        }
                        
                        echo '<button type="button" class="' . $tabClass . '" ';
                        echo 'data-tab="' . $tabKey . '" ';
                        if (!$isAccessible) {
                            echo 'disabled title="Available at age ' . $ageReq . '"';
                        }
                        echo '>';
                        echo '<i class="icon-' . $tab['icon'] . '"></i>';
                        echo '<span>' . $tab['title'] . '</span>';
                        if (!$isAccessible && $ageReq > 0) {
                            echo '<small class="age-req">' . $ageReq . '+</small>';
                        }
                        echo '</button>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Tab Contents -->
            <div class="tab-contents">
                <!-- Personal Tab -->
                <div class="tab-content active" id="personal-tab">
                    <div class="tab-header">
                        <h2>Personal Information</h2>
                        <button type="button" class="btn-edit-tab" data-tab="personal">Edit</button>
                    </div>
                    
                    <form id="personal-form" class="tab-form">
                        <div class="form-section">
                            <h3>Basic Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" value="<?= htmlspecialchars($profile['minor_first_name']) ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" value="<?= htmlspecialchars($profile['minor_last_name']) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($profile['minor_email'] ?? '') ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($profile['minor_phone'] ?? '') ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" rows="3" readonly><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Emergency Contact</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Contact Name</label>
                                    <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($profile['emergency_contact_name'] ?? '') ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Contact Phone</label>
                                    <input type="tel" name="emergency_contact_phone" value="<?= htmlspecialchars($profile['emergency_contact_phone'] ?? '') ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Medical Information</h3>
                            <div class="form-group">
                                <label>Medical Conditions</label>
                                <textarea name="medical_conditions" rows="3" readonly><?= htmlspecialchars($profile['medical_conditions'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Allergies</label>
                                <textarea name="allergies" rows="2" readonly><?= htmlspecialchars($profile['allergies'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions" style="display: none;">
                            <button type="submit" class="btn-save">Save Changes</button>
                            <button type="button" class="btn-cancel">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Church Tab -->
                <div class="tab-content" id="church-tab">
                    <div class="tab-header">
                        <h2>Church Details</h2>
                        <button type="button" class="btn-edit-tab" data-tab="church">Edit</button>
                    </div>
                    
                    <form id="church-form" class="tab-form">
                        <div class="form-section">
                            <h3>Sacraments</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Baptism Date</label>
                                    <input type="date" name="baptism_date" value="<?= $profile['baptism_date'] ?? '' ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Baptism Location</label>
                                    <input type="text" name="baptism_location" value="<?= htmlspecialchars($profile['baptism_location'] ?? '') ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Confirmation Date</label>
                                    <input type="date" name="confirmation_date" value="<?= $profile['confirmation_date'] ?? '' ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Confirmation Location</label>
                                    <input type="text" name="confirmation_location" value="<?= htmlspecialchars($profile['confirmation_location'] ?? '') ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Church Involvement</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Membership Date</label>
                                    <input type="date" name="church_membership_date" value="<?= $profile['church_membership_date'] ?? '' ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Sunday School Class</label>
                                    <input type="text" name="sunday_school_class" value="<?= htmlspecialchars($profile['sunday_school_class'] ?? '') ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Youth Group</label>
                                <input type="text" name="youth_group" value="<?= htmlspecialchars($profile['youth_group'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-actions" style="display: none;">
                            <button type="submit" class="btn-save">Save Changes</button>
                            <button type="button" class="btn-cancel">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Leadership Tab -->
                <div class="tab-content" id="leadership-tab">
                    <div class="tab-header">
                        <h2>Leadership Development</h2>
                        <button type="button" class="btn-edit-tab" data-tab="leadership">Edit</button>
                    </div>
                    
                    <form id="leadership-form" class="tab-form">
                        <div class="form-section">
                            <h3>Interests & Activities</h3>
                            <div class="form-group">
                                <label>Leadership Interests</label>
                                <textarea name="leadership_interests" rows="3" readonly><?= htmlspecialchars($profile['leadership_interests'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Volunteer Activities</label>
                                <textarea name="volunteer_activities" rows="3" readonly><?= htmlspecialchars($profile['volunteer_activities'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Skills & Talents</label>
                                <textarea name="skills_talents" rows="3" readonly><?= htmlspecialchars($profile['skills_talents'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Training & Mentorship</h3>
                            <div class="form-group">
                                <label>Leadership Training Completed</label>
                                <textarea name="leadership_training_completed" rows="3" readonly><?= htmlspecialchars($profile['leadership_training_completed'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Assigned Mentor</label>
                                <input type="text" name="mentor_assigned" value="<?= htmlspecialchars($profile['mentor_assigned'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-actions" style="display: none;">
                            <button type="submit" class="btn-save">Save Changes</button>
                            <button type="button" class="btn-cancel">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Locked Tabs (Ministry, Financial, etc.) -->
                <?php foreach (['ministry', 'financial', 'advanced_leadership', 'adult_programs'] as $lockedTab): ?>
                    <?php if (!($tabPermissions[$lockedTab]['is_accessible'] ?? false)): ?>
                        <div class="tab-content locked-tab" id="<?= $lockedTab ?>-tab">
                            <div class="locked-content">
                                <div class="lock-icon">🔒</div>
                                <h3>Content Locked</h3>
                                <p>This section will be available when <?= htmlspecialchars($profile['minor_first_name']) ?> turns <?= $tabPermissions[$lockedTab]['age_requirement'] ?? 18 ?>.</p>
                                <?php if ($currentAge >= 18): ?>
                                    <p class="admin-note">An administrator needs to approve access to this section.</p>
                                <?php else: ?>
                                    <p class="age-note"><?= (18 - $currentAge) ?> years remaining until eligibility.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <!-- Hidden profile ID for JavaScript -->
    <input type="hidden" id="profile-id" value="<?= $profileId ?>">
    
    <script src="js/minor_profile.js"></script>
</body>
</html>