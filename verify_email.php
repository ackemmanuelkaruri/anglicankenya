<?php
/**
 * ============================================
 * EMAIL VERIFICATION PAGE
 * Verifies user email after registration
 * ============================================
 */
define('DB_INCLUDED', true);

require_once 'db.php';
require_once 'includes/security.php';

start_secure_session();

$message = '';
$status = '';

// Get IP address for logging
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
    
    // Validate token format (64 hex characters)
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $message = "Invalid verification token format.";
        $status = 'error';
    } else {
        try {
            // Check if token exists and hasn't expired
            $stmt = $pdo->prepare("
                SELECT 
                    id, 
                    email, 
                    first_name, 
                    email_verified, 
                    email_token_expires_at,
                    account_status
                FROM users 
                WHERE email_verification_token = ? 
                LIMIT 1
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $message = "Invalid verification token. The link may have already been used.";
                $status = 'error';
                error_log("Email verification failed: Token not found. IP: {$ip_address}");
                
            } elseif ($user['email_verified'] == 1) {
                // Already verified
                $message = "This email address has already been verified. You can log in to your account.";
                $status = 'info';
                error_log("Email verification attempted for already verified user: {$user['email']}");
                
            } elseif ($user['email_token_expires_at'] && strtotime($user['email_token_expires_at']) < time()) {
                // Token expired
                $message = "This verification link has expired. Please register again or request a new verification email.";
                $status = 'error';
                error_log("Email verification failed: Token expired for user {$user['email']}");
                
            } else {
                // Valid token - verify the user
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET 
                        email_verified = 1, 
                        email_verification_token = NULL,
                        email_token_expires_at = NULL,
                        account_status = 'active',
                        updated_at = NOW()
                    WHERE id = ? 
                    AND email_verification_token = ?
                ");
                
                $result = $stmt->execute([$user['id'], $token]);
                
                if ($stmt->rowCount() > 0) {
                    $pdo->commit();
                    
                    $message = "Your email has been verified successfully! You can now log in to your account.";
                    $status = 'success';
                    
                    // Log successful verification
                    error_log(sprintf(
                        "Email verified successfully for user ID %d (%s) from IP: %s",
                        $user['id'],
                        $user['email'],
                        $ip_address
                    ));
                    
                } else {
                    $pdo->rollBack();
                    $message = "Verification failed. Please try again or contact support.";
                    $status = 'error';
                    error_log("Email verification failed: Update returned 0 rows for user {$user['email']}");
                }
            }
            
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Email verification database error: " . $e->getMessage());
            $message = "An error occurred during verification. Please try again later or contact support.";
            $status = 'error';
        }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .icon-large {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .icon-success { color: #28a745; }
        .icon-error { color: #dc3545; }
        .icon-info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-form p-5">
            <div class="text-center mb-4">
                <?php if ($status === 'success'): ?>
                    <i class="fas fa-check-circle icon-large icon-success"></i>
                <?php elseif ($status === 'info'): ?>
                    <i class="fas fa-info-circle icon-large icon-info"></i>
                <?php else: ?>
                    <i class="fas fa-times-circle icon-large icon-error"></i>
                <?php endif; ?>
                
                <h2 class="mt-3">Email Verification</h2>
            </div>
            
            <div class="alert alert-<?php 
                echo $status === 'success' ? 'success' : ($status === 'info' ? 'info' : 'danger'); 
            ?>" role="alert">
                <strong>
                    <?php if ($status === 'success'): ?>
                        <i class="fas fa-check-circle"></i> Success!
                    <?php elseif ($status === 'info'): ?>
                        <i class="fas fa-info-circle"></i> Already Verified
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i> Verification Failed
                    <?php endif; ?>
                </strong>
                <hr>
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            
            <?php if ($status === 'success' || $status === 'info'): ?>
                <div class="alert alert-info">
                    <strong>Next Steps:</strong>
                    <ul class="mb-0 mt-2">
                        <li>You will be redirected to the login page in 5 seconds</li>
                        <li>Use your email and password to log in</li>
                        <li>Complete your profile after logging in</li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Log In Now
                    </a>
                </div>
                
                <script>
                    // Auto-redirect after 5 seconds
                    let countdown = 5;
                    const countdownElement = document.createElement('p');
                    countdownElement.className = 'text-center text-muted mt-3';
                    countdownElement.innerHTML = `Redirecting in <strong>${countdown}</strong> seconds...`;
                    document.querySelector('.verification-form').appendChild(countdownElement);
                    
                    const interval = setInterval(() => {
                        countdown--;
                        countdownElement.innerHTML = `Redirecting in <strong>${countdown}</strong> seconds...`;
                        if (countdown <= 0) {
                            clearInterval(interval);
                            window.location.href = 'login.php';
                        }
                    }, 1000);
                </script>
                
            <?php else: ?>
                <div class="text-center mt-4">
                    <a href="register.php" class="btn btn-primary me-2">
                        <i class="fas fa-user-plus"></i> Register Again
                    </a>
                    <a href="login.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sign-in-alt"></i> Back to Login
                    </a>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <strong><i class="fas fa-lightbulb"></i> Need Help?</strong><br>
                    If you're having trouble verifying your email:
                    <ul class="mb-0 mt-2">
                        <li>Check if the link has expired (links are valid for 24 hours)</li>
                        <li>Make sure you clicked the complete link from your email</li>
                        <li>Try registering again with the same email</li>
                        <li>Contact support if the problem persists</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>