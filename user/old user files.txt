<?php
session_start();
include '../db.php'; // Ensure this file contains the PDO connection

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($login) && !empty($password)) {
        try {
            // Prepare the SQL statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone_number = ?");
            $stmt->execute([$login, $login, $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists and verify password
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'pending') {
                    $error_message = "Your account is pending approval. Please wait for admin verification.";
                } else {
                    // Store the user_id consistently with what the profile page expects
                    session_regenerate_id(true);
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Set a cookie for "Remember Me" functionality
                    if (!empty($_POST['remember_me'])) {
                        setcookie('remember_user', $user['username'], time() + (30 * 24 * 60 * 60), "/");
                    }

                    // Redirect to the user dashboard
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error_message = "Invalid login credentials.";
            }
        } catch (Exception $e) {
            $error_message = "Database connection error. Please try again.";
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Login</title>
    <?php include '../includes/styles.php'; ?>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            background: url('../img/churchinview.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .overlay {
            background-color: rgba(0, 0, 0, 0.5);
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px 0;
            text-align: center;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .login-form {
            text-align: center;
            padding: 40px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.9);
            width: 400px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .login-form h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 28px;
        }
        .btn {
            border-radius: 5px;
            padding: 12px;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #28a745;
            border: none;
        }
        .btn-primary:hover {
            background-color: #218838;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .btn-secondary {
            background-color: #ffc107;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #e0a800;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .form-label {
            font-weight: bold;
        }
        .form-check-label {
            font-weight: normal;
        }
        .alert-danger {
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .login-form {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="overlay"></div>
    
    <div class="content">
        <div class="login-form">
            <h2>Login</h2>
            <?php if (!empty($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="login" class="form-label">Username, Email, or Phone Number</label>
                    <input type="text" class="form-control" name="login" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">Remember Me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="mt-3">
                <a href="../register.php" class="btn btn-secondary w-100">Register</a>
                <span class="text-muted"> (New users)</span>
            </div>

            <div class="mt-3">
                <div class="p-3 bg-primary text-white rounded text-center">
                    <a href="reset_password.php" class="text-white text-decoration-none">Reset Password</a>
                </div>
                <span class="text-muted">(Forgot password/username?)</span>
            </div>
        </div>
    </div>

    <?php include '../includes/scripts.php'; ?>
</body>
</html>