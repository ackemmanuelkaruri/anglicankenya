<?php
session_start();
require_once 'db.php';
require_once 'includes/security.php';
require_once 'includes/email_helper.php'; // Added PHPMailer helper

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = sanitize_input($_POST['identifier']); // This can be email, phone, or username
    
    // Check if the identifier exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone_number = ? OR username = ?");
    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_reset_token = ?, password_reset_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$token, $expiry, $user['id']]);
        
        // Send reset email using PHPMailer helper
        $sent = sendPasswordResetEmail($user['email'], $user['first_name'], $token);
        
        if ($sent) {
            $success = "A password reset link has been sent to your email address.";
        } else {
            $error = "Failed to send reset email. Please try again.";
        }
    } else {
        // Don't reveal if user exists or not (security best practice)
        $success = "If your account exists, a password reset link has been sent to your email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Church Management System</title>
    <?php include 'includes/styles.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <div class="reset-form border rounded p-4 shadow" style="max-width: 500px; margin: 0 auto;">
            <h2 class="text-center mb-4">Reset Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Return to Login</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Email, Phone, or Username</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="login.php">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/scripts.php'; ?>
</body>
</html>