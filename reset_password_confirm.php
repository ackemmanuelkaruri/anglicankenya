<?php
session_start();
require_once 'db.php';
require_once 'includes/security.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE password_reset_token = ? AND password_reset_expires > ?
    ");
    $stmt->execute([$token, date('Y-m-d H:i:s')]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Invalid or expired reset token. Please request a new reset link.";
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Hash new password
            if (defined('PASSWORD_ARGON2ID')) {
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }
            
            // Update password and clear token
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, password_reset_token = NULL, password_reset_expires = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$hashed_password, $user['id']]);
            
            $success = "Your password has been reset successfully. You can now log in with your new password.";
        }
    }
} else {
    header('Location: reset_password.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Church Management System</title>
    <?php include 'includes/styles.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <div class="reset-form border rounded p-4 shadow" style="max-width: 500px; margin: 0 auto;">
            <h2 class="text-center mb-4">Reset Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <div class="text-center mt-3">
                    <a href="reset_password.php" class="btn btn-primary">Request New Link</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Log In</a>
                </div>
            <?php elseif (empty($error)): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        <small class="text-muted">At least 8 characters with mixed case, numbers, and symbols</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/scripts.php'; ?>
</body>
</html>