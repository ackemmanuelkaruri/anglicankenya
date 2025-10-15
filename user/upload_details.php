<?php

// Calculate path depth dynamically
$scriptPath = $_SERVER['SCRIPT_NAME'];
$depth = substr_count(dirname($scriptPath), '/');
$basePath = str_repeat('../', $depth);

// Database connection
include '../db.php'; // Include your database connection file
// Fetch user details (assuming user ID is passed via session or GET)
session_start();
// FIXED: Use 'id' instead of 'user_id'
$id = $_SESSION['id'] ?? $_GET['id'] ?? null;
// ADDED: Fallback to username lookup if id is still not available
if (!$id && isset($_SESSION['username'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $id = $result['id'];
        // Store it in session for future use
        $_SESSION['id'] = $id;
    }
}
if (!$id) {
    die("User ID not provided. Please <a href='login.php'>login</a> again.");
}
// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("User not found. Please <a href='login.php'>login</a> again.");
}
// Include data arrays for dropdowns
include '../includes/form_data.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Your Details</title>
    <?php include '../includes/styles.php'; ?>
</head>
<body>
    <input type="hidden" name="user_id" value="<?php echo $id; ?>">
    
    <?php include '../header.php'; ?>
    
    <div class="container">
        <!-- Added dedicated Dashboard button -->
        <div class="dashboard-nav" style="margin: 15px 0;">
            <a href="dashboard.php" class="btn-dashboard">Back to Dashboard</a>
        </div>
        
        <div class="tabs">
            <button class="tab-button active" data-tab="personal">Personal Info</button>
            <button class="tab-button" data-tab="church">Church Details</button>
            <button class="tab-button" data-tab="employment">Employment</button>
            <button class="tab-button" data-tab="clergy">Clergy/Laity</button>
            <button class="tab-button" data-tab="ministry">Ministries</button>
            <button class="tab-button" data-tab="leadership">Leadership</button>
            <!-- Family Members tab -->
            <button class="tab-button" data-tab="family">Family Members</button>
        </div>
        <h2>Update Your Details</h2>
        <!-- Success and error messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        <!-- Main form -->
        <form id="update-form" action="process_update.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
            <!-- Include form sections -->
            <?php include '../sections/personal_section.php'; ?>
            <?php include '../sections/church_section.php'; ?>
            <?php include '../sections/employment_section.php'; ?>
            <?php include '../sections/clergy_section.php'; ?>
            <?php include '../sections/ministry_section.php'; ?>
            <?php include '../sections/leadership_section.php'; ?>
            <!-- Family Members section -->
            <?php include '../sections/family_sections.php'; ?>
            <div class="form-buttons">
                <button type="submit" class="btn-update">Update Profile</button>
            </div>
        </form>
    </div>
    <?php include '../includes/scripts.php'; ?>
</body>
</html>