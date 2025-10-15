<?php
// Start session to access session variables
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Check if directed here properly with success flag
if (!isset($_SESSION['upload_success'])) {
    // Redirect to dashboard if not coming from successful upload
    header('Location: dashboard.php');
    exit;
}

// Clear the success flag after using it
unset($_SESSION['upload_success']);

// Get user's first name if available
$first_name = $_SESSION['first_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Successful</title>
    <meta http-equiv="refresh" content="5;url=dashboard.php">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .success-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #4CAF50;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
        }
        .success-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        h1 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        .loading-bar {
            height: 6px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .loading-progress {
            height: 100%;
            width: 0%;
            background-color: #4CAF50;
            border-radius: 3px;
            animation: progress 5s linear forwards;
        }
        .redirect-text {
            font-size: 14px;
            color: #777;
        }
        .dashboard-button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        .dashboard-button:hover {
            background-color: #3a9d3e;
        }
        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
        </div>
        <h1>Upload Successful!</h1>
        <p>Thank you <?= htmlspecialchars($first_name) ?>, your personal information has been updated successfully. All your details have been saved to our system.</p>
        <div class="loading-bar">
            <div class="loading-progress"></div>
        </div>
        <div class="redirect-text">Redirecting to dashboard in <span id="countdown">5</span> seconds...</div>
        <a href="dashboard.php" class="dashboard-button">Go to Dashboard Now</a>
    </div>

    <script src="../js/upload-success.js"></script>
</body>
</html>