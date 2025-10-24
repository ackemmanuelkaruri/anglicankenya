<?php
/**
 * ============================================
 * PASSWORD RESET CONFIRMATION PAGE
 * Handles the actual password reset after user clicks email link
 * WITH FULL SECURITY PROTECTION
 * ============================================
 */
define('DB_INCLUDED', true);

require_once 'db.php';
require_once __DIR__ . '/config.php';
require_once 'includes/init.php';
require_once 'includes/security.php';
require_once 'includes/scope_helpers.php'; // For CSRF protection

start_secure_session();

$error = '';
$success = '';
$token_valid = false;
$user = null;

// ============================================
// RATE LIMITING FOR RESET ATTEMPTS
// ============================================
function check_reset_attempt_limit($ip_address) {
    global $pdo;
    
    try {
        // Check failed attempts in last 15 minutes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM password_reset_confirmations 
            WHERE ip_address = ? 
            AND success = 0
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip_address]);
        $result = $stmt->fetch();
        
        // Allow max 5 failed attempts per 15 minutes
        return ($result['attempt_count'] < 5);
        
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true; // Fail open to not lock out users
    }
}

function log_reset_confirmation_attempt($user_id, $ip_address, $success) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_confirmations 
            (user_id, ip_address, success, attempted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $ip_address, $success ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Failed to log reset confirmation: " . $e->getMessage());
    }
}

// ============================================
// PASSWORD STRENGTH VALIDATION
// ============================================
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

// ============================================
// TOKEN VALIDATION
// ============================================
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (isset($_GET['token'])) {
    // Sanitize token input
    $token = sanitize_input($_GET['token']);
    
    // Validate token format (should be 64 hexadecimal characters)
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $error = "Invalid token format. Please request a new password reset.";
    } else {
        // Check rate limiting
        if (!check_reset_attempt_limit($ip_address)) {
            $error = "Too many failed attempts. Please try again in 15 minutes.";
        } else {
            try {
                // Hash the token to compare with database
                $token_hash = hash('sha256', $token);
                
                // Verify token exists, hasn't expired, and account is active
                $stmt = $pdo->prepare("
                    SELECT 
                        u.id, 
                        u.email, 
                        u.first_name,
                        u.username,
                        u.account_status,
                        u.password_reset_expires
                    FROM users u
                    WHERE u.password_reset_token = ? 
                    AND u.password_reset_expires > NOW()
                    AND u.account_status IN ('active', 'pending')
                    LIMIT 1
                ");
                $stmt->execute([$token_hash]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $token_valid = true;
                    
                    // Store user ID in session for CSRF validation
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_token_hash'] = $token_hash;
                } else {
                    $error = "This password reset link is invalid or has expired. Please request a new one.";
                    
                    // Log failed attempt (if we can't find user, log with null user_id)
                    log_reset_confirmation_attempt(null, $ip_address, false);
                }
                
            } catch (PDOException $e) {
                error_log("Database error in token validation: " . $e->getMessage());
                $error = "An error occurred. Please try again later.";
            }
        }
    }
} else {
    // No token provided - redirect to request page
    header('Location: password_reset.php');
    exit;
}

// ============================================
// HANDLE PASSWORD RESET FORM SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid && $user) {
    
    // CSRF Protection
    verify_csrf_token();
    
    // Verify session matches the user being reset
    if (!isset($_SESSION['reset_user_id']) || $_SESSION['reset_user_id'] != $user['id']) {
        $error = "Session mismatch. Please start the reset process again.";
        $token_valid = false;
    } else {
        
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($password)) {
            $error = "Password is required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match. Please try again.";
        } else {
            // Validate password strength
            $password_errors = validate_password_strength($password);
            
            if (!empty($password_errors)) {
                $error = "Password does not meet requirements:<br>• " . implode("<br>• ", $password_errors);
            } else {
                try {
                    // Check if user hasn't changed password in the last 5 minutes
                    // (Prevents race conditions with multiple reset attempts)
                    $stmt = $pdo->prepare("
                        SELECT updated_at 
                        FROM users 
                        WHERE id = ? 
                        AND password_reset_token = ?
                        AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    ");
                    $stmt->execute([$user['id'], $_SESSION['reset_token_hash']]);
                    $can_update = $stmt->fetch();
                    
                    if (!$can_update && $stmt->rowCount() == 0) {
                        // Check if token is still valid
                        $stmt = $pdo->prepare("
                            SELECT id FROM users 
                            WHERE id = ? AND password_reset_token = ?
                        ");
                        $stmt->execute([$user['id'], $_SESSION['reset_token_hash']]);
                        
                        if ($stmt->rowCount() == 0) {
                            $error = "This reset link has already been used. Please request a new one if needed.";
                            $token_valid = false;
                        }
                    }
                    
                    if ($token_valid) {
                        // Hash new password with strong algorithm
                        if (defined('PASSWORD_ARGON2ID')) {
                            $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                                'memory_cost' => 65536,
                                'time_cost' => 4,
                                'threads' => 3
                            ]);
                        } else {
                            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        }
                        
                        // Update password and clear reset token
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET password = ?,
                                password_reset_token = NULL,
                                password_reset_expires = NULL,
                                updated_at = NOW()
                            WHERE id = ? 
                            AND password_reset_token = ?
                        ");
                        
                        $result = $stmt->execute([
                            $hashed_password,
                            $user['id'],
                            $_SESSION['reset_token_hash']
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            $success = "Your password has been reset successfully! You can now log in with your new password.";
                            $token_valid = false; // Prevent form from showing again
                            
                            // Log successful reset
                            log_reset_confirmation_attempt($user['id'], $ip_address, true);
                            
                            // Clear session variables
                            unset($_SESSION['reset_user_id']);
                            unset($_SESSION['reset_token_hash']);
                            
                            // Log security event
                            error_log(sprintf(
                                "Password successfully reset for user ID %d (%s) from IP: %s",
                                $user['id'],
                                $user['email'],
                                $ip_address
                            ));
                            
                            // Optional: Send email notification about password change
                            // sendPasswordChangedNotification($user['email'], $user['first_name']);
                            
                        } else {
                            $error = "Failed to reset password. The link may have already been used.";
                            log_reset_confirmation_attempt($user['id'], $ip_address, false);
                        }
                    }
                    
                } catch (PDOException $e) {
                    error_log("Database error in password reset: " . $e->getMessage());
                    $error = "An error occurred while resetting your password. Please try again.";
                    log_reset_confirmation_attempt($user['id'] ?? null, $ip_address, false);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Church Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reset-form {
            max-width: 500px;
            margin: 50px auto;
            background: white;
        }
        .password-requirements {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .password-requirements ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }
        .password-strength-meter {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
            width: 0%;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="reset-form border rounded p-4 shadow">
            <h2 class="text-center mb-4">
                <i class="fas fa-key"></i> Set New Password
            </h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="password_reset.php" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Log In Now
                    </a>
                </div>
                
            <?php elseif ($token_valid && $user && empty($error)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i>
                    Resetting password for: <strong><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                
                <form method="POST" action="" id="resetPasswordForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required 
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <div class="password-strength-meter">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-requirements text-muted">
                            <strong>Password must contain:</strong>
                            <ul>
                                <li>At least 8 characters</li>
                                <li>One uppercase letter (A-Z)</li>
                                <li>One lowercase letter (a-z)</li>
                                <li>One number (0-9)</li>
                                <li>One special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            autocomplete="new-password"
                        >
                        <small class="text-muted">Re-enter your password to confirm</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-lock"></i> Reset Password
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i>
                        This link expires in 
                        <?php 
                        $expires = new DateTime($user['password_reset_expires']);
                        $now = new DateTime();
                        $diff = $now->diff($expires);
                        echo $diff->format('%i minutes');
                        ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Password strength checker
    document.getElementById('password')?.addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthBar = document.getElementById('strengthBar');
        
        let strength = 0;
        
        // Check length
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Check for character types
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Update strength bar
        strengthBar.className = 'password-strength-bar';
        if (strength <= 2) {
            strengthBar.classList.add('strength-weak');
        } else if (strength <= 4) {
            strengthBar.classList.add('strength-medium');
        } else {
            strengthBar.classList.add('strength-strong');
        }
    });
    
    // Form validation
    document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        // Check password requirements
        const requirements = [
            { regex: /.{8,}/, message: 'at least 8 characters' },
            { regex: /[A-Z]/, message: 'an uppercase letter' },
            { regex: /[a-z]/, message: 'a lowercase letter' },
            { regex: /[0-9]/, message: 'a number' },
            { regex: /[^A-Za-z0-9]/, message: 'a special character' }
        ];
        
        for (let req of requirements) {
            if (!req.regex.test(password)) {
                e.preventDefault();
                alert('Password must contain ' + req.message);
                return false;
            }
        }
    });
    </script>
</body>
</html>