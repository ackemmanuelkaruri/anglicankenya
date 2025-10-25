<?php
session_start();

// Check for "Remember Me" cookie and set session if it exists
if (isset($_COOKIE['remember_user']) && !isset($_SESSION['username'])) {
    $_SESSION['username'] = $_COOKIE['remember_user'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ACK EMMANUEL KARURI Home page</title>
    <?php include 'includes/styles.php'; ?>
    
    <style>
        /* Background image for the body */
        body {
            background-image: url('img/anglicankenya.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Overlay for better visibility */
        .overlay {
            background-color: rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .text-white {
            color: white !important;
        }

        .grey-background {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }

        .grey-background:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
        }

        .fixed-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .card-title {
            color: #333 !important;
            font-weight: bold;
        }

        .btn-center {
            display: block;
            margin: 15px auto 0;
            min-width: 150px;
        }
    </style>
</head>

<body>
    <div class="overlay"></div>
    
    <div class="content">
        <?php include 'header.php'; ?>

        <div class="container mt-5 pt-4">
            <div class="row justify-content-center">
                <!-- Dashboard Link (Visible only if logged in) -->
                <?php if (isset($_SESSION['username'])) : ?>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="grey-background text-center">
                            <h5 class="card-title mb-3">Dashboard</h5>
                            <a href="dashboard.php" class="btn btn-primary btn-center">Go to Dashboard</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contact Us Section -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="grey-background">
                        <img src="img/info.png" class="fixed-image" alt="Contact Us">
                        <h5 class="text-center mt-3 card-title">Contact Us</h5>
                        <a href="#" class="btn btn-success btn-center">Contact Us</a>
                    </div>
                </div>

                <!-- Our Church Clergy Section -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="grey-background">
                        <img src="img/register.jpg" class="fixed-image" alt="Our Church Clergy">
                        <h5 class="text-center mt-3 card-title">Our Church Clergy</h5>
                        <a href="#" class="btn btn-success btn-center">Join Now</a>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="grey-background">
                        <img src="img/church.jpg" class="fixed-image" alt="Welcome to Our Church">
                        <h5 class="text-center mt-3 card-title">Welcome to Our Church</h5>
                        <a href="login.php" class="btn btn-success btn-center">Login Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
</body>
</html>
