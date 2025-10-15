<?php
require_once 'db.php';
require_once 'includes/security.php';

$message = '';
$status = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE email_verification_token = ? AND email_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update user as verified
        $stmt = $pdo->prepare("
            UPDATE users 
            SET email_verified = 1, email_verification_token = NULL, account_status = 'active' 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        $message = "Your email has been verified successfully! You can now log in to your account.";
        $status = 'success';
    } else {
        $message = "Invalid or expired verification token. Please register again or contact support.";
        $status = 'error';
    }
} else {
    $message = "No verification token provided.";
    $status = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Church Management System</title>
    <?php include 'includes/styles.php'; ?>
</head>
<body>
    <div class="container mt-5">
        <div class="verification-form border rounded p-4 shadow" style="max-width: 600px; margin: 0 auto;">
            <h2 class="text-center mb-4">Email Verification</h2>
            
            <div class="alert alert-<?php echo $status === 'success' ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <?php if ($status === 'success'): ?>
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-primary">Log In to Your Account</a>
                </div>
                <script>
                    // Auto-redirect after 5 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 5000);
                </script>
            <?php else: ?>
                <div class="text-center mt-4">
                    <a href="register.php" class="btn btn-primary">Register Again</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
