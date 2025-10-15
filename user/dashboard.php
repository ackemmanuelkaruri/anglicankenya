<?php
session_start();
// REMOVE THESE DEBUG LINES TEMPORARILY TO SEE WHAT'S WRONG
// echo "Session ID: " . session_id() . "<br>";
// echo "Session data: ";
// var_dump($_SESSION);
// echo "<br>";
require_once '../db.php'; // Ensure database connection is included
// Get current page filename for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
// Set breadcrumb
$breadcrumb = [
    ['name' => 'Home', 'url' => 'dashboard.php'],
    ['name' => 'Dashboard', 'url' => null] // Current page
];
// Check if database connection works
if(isset($pdo)) {
    // echo "Database connection: SUCCESS<br>";
} else {
    echo "Database connection: FAILED<br>";
}
// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    echo "Username not found in session<br>";
    echo "Available session keys: " . implode(', ', array_keys($_SESSION)) . "<br>";
    die("Access Denied. Please log in.");
}
// echo "Username from session: " . $_SESSION['username'] . "<br><br>";
// Fetch user details from the database
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE username = :username");
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute(); // Execute the query
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// Ensure user data exists before accessing it
if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <?php include '../includes/styles.php'; ?>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="container-fluid">
        <!-- Include breadcrumb navigation -->
        <?php include '../breadcrumb.php'; ?>
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-2 bg-info" style="min-height: 100vh; margin-left: -30px;">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'dashboard.php') ? 'sidebar-active' : ''; ?>">Dashboard</a>
                    <a href="profile.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'profile.php') ? 'sidebar-active' : ''; ?>">Profile</a>
                    <a href="upload_details.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'upload_details.php') ? 'sidebar-active' : ''; ?>">Upload Details</a>
                    <a href="responsibilities.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'responsibilities.php') ? 'sidebar-active' : ''; ?>">Active Responsibilities</a>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-10">
               <h3 class="text-center mt-4">
                   Welcome, <?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?>!
               </h3>
                
                <div class="row text-center mt-4">
                    <!-- Profile -->
                    <div class="col-md-3">
                        <a href="profile.php" class="dashboard-box bg-primary text-white">
                            <i class="fas fa-user icon"></i>
                            <p>Profile</p>
                        </a>
                    </div>
                    <!-- Upload Details -->
                    <div class="col-md-3">
                        <a href="upload_details.php" class="dashboard-box bg-success text-white">
                            <i class="fas fa-upload icon"></i>
                            <p>Upload Details</p>
                        </a>
                    </div>
                    <!-- Active Responsibilities -->
                    <div class="col-md-3">
                        <a href="responsibilities.php" class="dashboard-box bg-warning text-white">
                            <i class="fas fa-tasks icon"></i>
                            <p>Active Responsibilities</p> <!-- Fixed text -->
                        </a>
                    </div>
                </div>
                
                <!-- Additional Dashboard Content -->
                <div class="row mt-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4>Quick Actions</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="upload_details.php" class="btn btn-outline-primary btn-block mb-3">Update Your Details</a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="change_password.php" class="btn btn-outline-warning btn-block mb-3">Change Password</a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="view_profile.php" class="btn btn-outline-info btn-block mb-3">View Profile</a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="help.php" class="btn btn-outline-secondary btn-block mb-3">Help & Support</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/scripts.php'; ?>
</body>
</html>