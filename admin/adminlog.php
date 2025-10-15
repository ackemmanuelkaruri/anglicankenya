<?php
session_start();
include '../db.php'; // Ensure this file contains the PDO connection
$error_message = "";

// Redirect if already logged in
if (isset($_SESSION['username']) && is_admin($_SESSION['username'], $pdo)) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($login) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone_number = ?");
        $stmt->execute([$login, $login, $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if (is_admin($user['username'], $pdo)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if (!empty($_POST['remember_me'])) {
                    setcookie('remember_user', $user['username'], time() + (30 * 24 * 60 * 60), "/");
                }
                
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Access denied. Admin credentials required.";
            }
        } else {
            $error_message = "Invalid login credentials.";
        }
    } else {
        $error_message = "Please enter both login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body, html { height: 100%; margin: 0; padding: 0; }
        body {
            background: url('../img/churchoutview.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .login-form {
            text-align: center;
            padding: 40px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.9);
            width: 400px;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .alert-danger { margin-bottom: 20px; }
        .btn { border-radius: 5px; padding: 12px; font-size: 18px; transition: all 0.3s ease; }
        .btn-primary { background-color: #007bff; border: none; }
        .btn-primary:hover { background-color: #0056b3; transform: scale(1.05); }
        .btn-secondary { background-color: #6c757d; border: none; }
        .btn-secondary:hover { background-color: #5a6268; transform: scale(1.05); }
        @media (max-width: 576px) { .login-form { width: 90%; } }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="content">
        <div class="login-form">
            <h2>Admin Login</h2>
            <?php if (!empty($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username, Email, or Phone</label>
                    <input type="text" class="form-control" name="login" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
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
                <span class="text-muted">(New users)</span>
            </div>
            <div class="mt-3">
                <div class="p-3 bg-primary text-white rounded text-center">
                    <a href="reset_password.php" class="text-white text-decoration-none">Reset Password</a>
                </div>
                <span class="text-muted">(Forgot password/username?)</span>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>