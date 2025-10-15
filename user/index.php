<?php
session_start();
include '../db.php'; // Database connection
// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: user_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Index</title>
    <?php include '../includes/styles.php'; ?>
</head>
<body>
    <div class="container">
        <h1 class="mt-4">Welcome to Your Dashboard</h1>
        <p>Here you can view your profile, upload documents, and change your password.</p>
        <!-- Additional user functionalities can be added here -->
    </div>
    <?php include '../includes/scripts.php'; ?>
</body>
</html>