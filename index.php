<?php
session_start();

// Check for "Remember Me" cookie and set session if it exists
if (isset($_COOKIE['remember_user']) && !isset($_SESSION['username'])) {
    $_SESSION['username'] = $_COOKIE['remember_user'];
}
?>

<!DOCTYPE html>
<html>
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
            height: 100vh;
            margin: 0;
        }

        /* Overlay for better visibility */
        .overlay {
            background-color: rgba(0, 0, 0, 0.5);
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .text-white {
            color: white;
        }

        .grey-background {
            background-color: rgba(255, 255, 255, 0.6);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s, box-shadow 0.3s;
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
    </style>
</head>

<body>
    <div class="overlay"></div>
    
    <div class="content">
        <?php include 'header.php'; ?>

        <div style="margin-top: 50px;"></div>

        <div class="container">
            <div class="col-md-12">
                <div class="row">
                    <!-- Dashboard Link (Visible only if logged in) -->
                    <?php if (isset($_SESSION['username'])) : ?>
                        <div class="col-md-4 mx-1 grey-background">
                            <a href="dashboard.php">
                                <button class="btn btn-success w-100 my-3">Go to Dashboard</button>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Existing Sections -->
                    <div class="col-md-3 mx-1 grey-background">
                        <img src="img/info.png" class="fixed-image" alt="Contact Us">
                        <h5 class="text-center mt-2 text-white">Contact us</h5>
                        <a href="#">
                            <button class="btn btn-success my-3" style="margin-left:30%;">Contact us!!!</button>
                        </a>
                    </div>
                    <div class="col-md-4 mx-1 grey-background">
                        <img src="img/register.jpg" class="fixed-image" alt="Our Church Clergy">
                        <h5 class="text-center mt-2 text-white">Our Church Clergy</h5>
                        <a href="#">
                            <button class="btn btn-success my-3" style="margin-left:30%;">Join now!!!</button>
                        </a>
                    </div>
                    <div class="col-md-4 mx-1 grey-background">
                        <img src="img/church.jpg" class="fixed-image" alt="Welcome to Our Church">
                        <h5 class="text-center mt-2 text-white">Welcome to Our Church</h5>
                        <a href="login.php">
                            <button class="btn btn-success my-3" style="margin-left:30%;">Login Now!!!</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>
</body>
</html>