<?php
session_start();
require_once '../db.php';
require_once '../includes/data_helper.php'; // Include the data helper

// Enable error reporting for detailed debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debugging information
function debugLog($message) {
    error_log($message, 3, '../debug_update.log');
    echo $message . "<br>";
}

// Check if user is logged in
if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
    $id = $_SESSION['id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
} else {
    // Redirect to login if no valid ID found
    header("Location: login.php");
    exit();
}

// Get user data (personal info remains as is)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Get church information with display names
$churchInfo = getUserChurchInfo($pdo, $id);

// Get employment history
$employment_history = getUserEmploymentHistory($pdo, $id);

// Get leadership roles with display names
$leadership_roles = getUserLeadershipRoles($pdo, $id);

// Get clergy information with display names
$clergy_roles = getUserClergyInfo($pdo, $id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['first_name']) ?>'s Profile</title>
    <?php include '../includes/styles.php'; ?>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="profile-photo">
                <?php if (!empty($user['passport'])): ?>
                    <?php
                        $file_extension = pathinfo($user['passport'], PATHINFO_EXTENSION);
                        if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])):
                    ?>
                        <img src="../<?= htmlspecialchars($user['passport']) ?>" alt="Passport Photo">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
                <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($user['member_id'] ?? 'Not assigned') ?></p>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? 'Not specified') ?></p>
                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone_number'] ?? 'Not specified') ?></p>
                <div class="profile-actions">
                    <a href="upload_details.php?id=<?= htmlspecialchars($user['id']) ?>" class="btn">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="two-column">
            <div>
                <!-- Personal Information Section (Horizontal Layout) -->
                <div class="profile-section">
                    <h2><i class="fas fa-user"></i> Personal Information</h2>
                    <div class="horizontal-layout">
                        <div class="info-card">
                            <h4>Username</h4>
                            <p><?= htmlspecialchars($user['username'] ?? 'Not specified') ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Gender</h4>
                            <p><?= isset($user['gender']) ? htmlspecialchars(ucfirst($user['gender'])) : 'Not specified' ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Marital Status</h4>
                            <p><?= htmlspecialchars($user['marital_status'] ?? 'Not specified') ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Occupation</h4>
                            <p><?= htmlspecialchars($user['occupation'] ?? 'Not specified') ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Education Level</h4>
                            <p><?= htmlspecialchars($user['education_level'] ?? 'Not specified') ?></p>
                        </div>
                        <div class="info-card">
                            <h4>Country</h4>
                            <p><?= htmlspecialchars($user['country'] ?? 'Not specified') ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($user['wedding_type'])): ?>
                        <div class="info-card" style="margin-top: 15px;">
                            <h4>Wedding Type</h4>
                            <p><?= htmlspecialchars($user['wedding_type']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Church Information Section (Enhanced) -->
<div class="profile-section">
    <h2><i class="fas fa-church"></i> Church Information</h2>
    <div class="church-info-enhanced">
        <div class="church-info-card">
            <div class="church-icon">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="church-details">
                <h4>Church ID</h4>
                <p><?= htmlspecialchars($churchInfo['member_id'] ?? 'Not assigned') ?></p>
            </div>
        </div>
        
        <div class="church-info-card">
            <div class="church-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="church-details">
                <h4>Service Attending</h4>
                <p><?= htmlspecialchars($churchInfo['display_service_attending']) ?></p>
            </div>
        </div>
        
        <?php if (!empty($churchInfo['english_service_team'])): ?>
            <div class="church-info-card">
                <div class="church-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="church-details">
                    <h4>English Service Team</h4>
                    <p><?= htmlspecialchars($churchInfo['display_english_service_team']) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($churchInfo['kikuyu_cell_group'])): ?>
            <div class="church-info-card">
                <div class="church-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="church-details">
                    <h4>Kikuyu Cell Group</h4>
                    <p><?= htmlspecialchars($churchInfo['display_kikuyu_cell_group']) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($churchInfo['family_group'])): ?>
            <div class="church-info-card">
                <div class="church-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="church-details">
                    <h4>Family Group</h4>
                    <p><?= htmlspecialchars($churchInfo['family_group']) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Spiritual Status Section (Enhanced) -->
<div class="profile-section">
    <h2><i class="fas fa-pray"></i> Spiritual Status</h2>
    <div class="spiritual-status-enhanced">
        <div class="spiritual-status-card <?= (isset($user['baptized']) && $user['baptized'] == 'yes') ? 'status-yes' : 'status-no' ?>">
            <div class="status-icon">
                <i class="fas fa-tint"></i>
            </div>
            <div class="status-content">
                <h4>Baptized</h4>
                <p><?= (isset($user['baptized']) && $user['baptized'] == 'yes') ? 'Yes' : 'No' ?></p>
            </div>
            <div class="status-indicator">
                <i class="fas <?= (isset($user['baptized']) && $user['baptized'] == 'yes') ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            </div>
        </div>
        
        <div class="spiritual-status-card <?= (isset($user['confirmed']) && $user['confirmed'] == 'yes') ? 'status-yes' : 'status-no' ?>">
            <div class="status-icon">
                <i class="fas fa-cross"></i>
            </div>
            <div class="status-content">
                <h4>Confirmed</h4>
                <p><?= (isset($user['confirmed']) && $user['confirmed'] == 'yes') ? 'Yes' : 'No' ?></p>
            </div>
            <div class="status-indicator">
                <i class="fas <?= (isset($user['confirmed']) && $user['confirmed'] == 'yes') ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            </div>
        </div>
        
        <?php if (isset($user['baptized']) && $user['baptized'] == 'no'): ?>
            <div class="spiritual-status-card <?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'yes') ? 'status-yes' : 'status-no' ?>">
                <div class="status-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="status-content">
                    <h4>Baptism Interest</h4>
                    <p><?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'yes') ? 'Yes' : 'No' ?></p>
                </div>
                <div class="status-indicator">
                    <i class="fas <?= (isset($user['baptism_interest']) && $user['baptism_interest'] == 'yes') ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($user['confirmed']) && $user['confirmed'] == 'no'): ?>
            <div class="spiritual-status-card <?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'yes') ? 'status-yes' : 'status-no' ?>">
                <div class="status-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="status-content">
                    <h4>Confirmation Interest</h4>
                    <p><?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'yes') ? 'Yes' : 'No' ?></p>
                </div>
                <div class="status-indicator">
                    <i class="fas <?= (isset($user['confirmation_interest']) && $user['confirmation_interest'] == 'yes') ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Certificates Section (Enhanced) -->
<div class="profile-section">
    <h2><i class="fas fa-file-certificate"></i> Certificates</h2>
    <div class="certificates-enhanced">
        <!-- Baptism Certificate -->
        <?php if (isset($user['baptized']) && $user['baptized'] == 'yes'): ?>
            <div class="certificate-container">
                <div class="certificate-header">
                    <div class="certificate-title">
                        <i class="fas fa-tint"></i>
                        <h3>Baptism Certificate</h3>
                    </div>
                    <div class="certificate-status">
                        <?php if (!empty($user['baptism_certificate'])): ?>
                            <span class="status-badge status-available">Available</span>
                        <?php else: ?>
                            <span class="status-badge status-missing">Not Uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($user['baptism_certificate'])): ?>
                    <div class="certificate-preview">
                        <div class="certificate-item">
                            <a href="../<?= htmlspecialchars($user['baptism_certificate']) ?>" target="_blank">
                                <?php
                                    $file_extension = pathinfo($user['baptism_certificate'], PATHINFO_EXTENSION);
                                    if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])):
                                ?>
                                    <img src="../<?= htmlspecialchars($user['baptism_certificate']) ?>" alt="Baptism Certificate">
                                <?php else: ?>
                                    <div class="file-placeholder">
                                        <i class="fas fa-file-pdf fa-3x"></i>
                                        <p>PDF Document</p>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="certificate-actions">
                            <a href="../<?= htmlspecialchars($user['baptism_certificate']) ?>" target="_blank" class="btn-certificate">
                                <i class="fas fa-eye"></i> View Certificate
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-certificate">
                        <i class="fas fa-file-certificate fa-3x"></i>
                        <p>No baptism certificate uploaded</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Confirmation Certificate -->
        <?php if (isset($user['confirmed']) && $user['confirmed'] == 'yes'): ?>
            <div class="certificate-container">
                <div class="certificate-header">
                    <div class="certificate-title">
                        <i class="fas fa-cross"></i>
                        <h3>Confirmation Certificate</h3>
                    </div>
                    <div class="certificate-status">
                        <?php if (!empty($user['confirmation_certificate'])): ?>
                            <span class="status-badge status-available">Available</span>
                        <?php else: ?>
                            <span class="status-badge status-missing">Not Uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($user['confirmation_certificate'])): ?>
                    <div class="certificate-preview">
                        <div class="certificate-item">
                            <a href="../<?= htmlspecialchars($user['confirmation_certificate']) ?>" target="_blank">
                                <?php
                                    $file_extension = pathinfo($user['confirmation_certificate'], PATHINFO_EXTENSION);
                                    if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])):
                                ?>
                                    <img src="../<?= htmlspecialchars($user['confirmation_certificate']) ?>" alt="Confirmation Certificate">
                                <?php else: ?>
                                    <div class="file-placeholder">
                                        <i class="fas fa-file-pdf fa-3x"></i>
                                        <p>PDF Document</p>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="certificate-actions">
                            <a href="../<?= htmlspecialchars($user['confirmation_certificate']) ?>" target="_blank" class="btn-certificate">
                                <i class="fas fa-eye"></i> View Certificate
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-certificate">
                        <i class="fas fa-file-certificate fa-3x"></i>
                        <p>No confirmation certificate uploaded</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
            
            <div>
                <!-- Church Involvement Section (Horizontal Layout) -->
                <?php if (!empty($churchInfo['display_departments']) || !empty($churchInfo['display_ministries'])): ?>
                    <div class="profile-section">
                        <h2><i class="fas fa-hands-helping"></i> Church Involvement</h2>
                        <div class="horizontal-layout">
                            <?php if (!empty($churchInfo['display_departments'])): ?>
                                <div class="involvement-card">
                                    <h4>Church Departments</h4>
                                    <div class="tag-container">
                                        <?php foreach ($churchInfo['display_departments'] as $department): ?>
                                            <?php if (!empty($department)): ?>
                                                <div class="tag"><?= htmlspecialchars($department) ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($churchInfo['display_ministries'])): ?>
                                <div class="involvement-card">
                                    <h4>Ministries/Committees</h4>
                                    <div class="tag-container">
                                        <?php foreach ($churchInfo['display_ministries'] as $ministry): ?>
                                            <?php if (!empty($ministry)): ?>
                                                <div class="tag"><?= htmlspecialchars($ministry) ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Employment History Section (Horizontal Layout) -->
                <?php if (!empty($employment_history)): ?>
                    <div class="profile-section">
                        <h2><i class="fas fa-briefcase"></i> Employment History</h2>
                        <div class="scrollable-horizontal">
                            <?php foreach ($employment_history as $employment): ?>
                                <div class="employment-card <?= (isset($employment['is_current']) && $employment['is_current'] == '1') ? 'current' : '' ?>">
                                    <h4><?= htmlspecialchars($employment['job_title']) ?></h4>
                                    <div class="company"><?= htmlspecialchars($employment['company']) ?></div>
                                    <div class="dates">
                                        <?php if (!empty($employment['employment_from_date'])): ?>
                                            From: <?= htmlspecialchars(date('F j, Y', strtotime($employment['employment_from_date']))) ?>
                                        <?php endif; ?>
                                        <?php if (isset($employment['is_current']) && $employment['is_current'] == '1'): ?>
                                            to Present
                                        <?php elseif (!empty($employment['employment_to_date'])): ?>
                                            to <?= htmlspecialchars(date('F j, Y', strtotime($employment['employment_to_date']))) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Leadership Roles Section (Horizontal Layout) -->
                <?php if (!empty($leadership_roles)): ?>
                    <div class="profile-section">
                        <h2><i class="fas fa-users-cog"></i> Leadership Roles</h2>
                        <div class="scrollable-horizontal">
                            <?php foreach ($leadership_roles as $role): ?>
                                <div class="leadership-card <?= (isset($role['is_current']) && $role['is_current'] == '1') ? 'current' : '' ?>">
                                    <h4><?= htmlspecialchars($role['display_role']) ?></h4>
                                    <div class="entity"><?= htmlspecialchars($role['display_entity']) ?></div>
                                    <div class="dates">
                                        <?php if (!empty($role['from_date'])): ?>
                                            From: <?= htmlspecialchars(date('F j, Y', strtotime($role['from_date']))) ?>
                                        <?php endif; ?>
                                        <?php if (isset($role['is_current']) && $role['is_current'] == '1'): ?>
                                            to Present
                                        <?php elseif (!empty($role['to_date'])): ?>
                                            to <?= htmlspecialchars(date('F j, Y', strtotime($role['to_date']))) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Clergy Roles Section (Horizontal Layout) -->
                <?php if (!empty($clergy_roles)): ?>
                    <div class="profile-section">
                        <h2><i class="fas fa-cross"></i> Clergy/Laity Details</h2>
                        <div class="scrollable-horizontal">
                            <?php foreach ($clergy_roles as $role): ?>
                                <div class="leadership-card <?= (isset($role['is_current']) && $role['is_current'] == '1') ? 'current' : '' ?>">
                                    <h4><?= htmlspecialchars($role['display_role']) ?></h4>
                                    <div class="dates">
                                        <?php if (!empty($role['serving_from_date'])): ?>
                                            From: <?= htmlspecialchars(date('F j, Y', strtotime($role['serving_from_date']))) ?>
                                        <?php endif; ?>
                                        <?php if (isset($role['is_current']) && $role['is_current'] == '1'): ?>
                                            to Present
                                        <?php elseif (!empty($role['to_date'])): ?>
                                            to <?= htmlspecialchars(date('F j, Y', strtotime($role['to_date']))) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/scripts.php'; ?>
</body>
</html>