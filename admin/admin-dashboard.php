<?php
session_start();
require_once '../db.php'; // Database connection
require_once 'functions.php'; // Functions file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidenav.php'; ?>
            <div class="col-md-10">
                <h1 class="mt-4">Admin Dashboard</h1>
                <img src="images/dashboard_banner.jpg" alt="Admin Dashboard Banner" class="img-fluid">
                
                <!-- Include the comprehensive dashboard content -->
                <?php include 'dashboard_content.php'; ?>
            </div>
        </div>
    </div>
</body>
</html>