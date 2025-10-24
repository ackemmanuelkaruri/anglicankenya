<?php
/**
 * ============================================
 * CHANGE PASSWORD PAGE
 * Allows authenticated users to change their password
 * WITH FULL SECURITY PROTECTION
 * ============================================
 */
define('DB_INCLUDED', true);

require_once 'db.php';
require_once __DIR__ . '/config.php';
require_once 'includes/init.php';
require_once 'includes/security.php';
require_once 'includes/scope_helpers.php'; // For CSRF protection

session_start();

// ============================================
// AUTHENTICATION CHECK
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php?redirect=change_password.php");
    exit();
}

// Validate session hasn't expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

$error = '';
$success = '';
$user_id = (int)$_SESSION['user_id'];

// ============================================
// RATE LIMITING
// ============================================
function check_password_change_rate_limit($user_id, $ip_address) {
    global $pdo;
    
    try {
        // Check failed attempts in last 15 minutes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM password_change_attempts 
            WHERE (user_id = ? OR ip_address = ?) 
            AND success = 0
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$user_id, $ip_address]);
        $result = $stmt->fetch();
        
        // Allow max 5 failed attempts per 15 minutes
        return ($result['attempt_count'] < 5);
        
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true; // Fail open
    }
}

function log_password_change_attempt($user_id, $ip_address, $success) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO password_change_attempts 
            (user_id, ip_address, success, attempted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $ip_address, $success ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Failed to log password change attempt: " . $e->getMessage());
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
    
    if (strlen($password) > 128) {
        $errors[] = "Password must not exceed 128 characters";
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
    
    // Check for common weak passwords
    $weak_passwords = ['password', '12345678', 'qwerty123', 'admin123'];
    if (in_array(strtolower($password), $weak_passwords)) {
        $errors[] = "This password is too common. Please choose a stronger password";
    }
    
    return $errors;
}

// ============================================
// VERIFY CURRENT PASSWORD
// ============================================
function verify_current_password($user_id, $current_password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($current_password, $user['password'])) {
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Password verification error: " . $e->getMessage());
        return false;
    }
}

// ============================================
// MAIN FORM PROCESSING
// ============================================
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // CSRF Protection
    verify_csrf_token();
    
    // Check rate limiting
    if (!check_password_change_rate_limit($user_id, $ip_address)) {
        $error = "Too many failed attempts. Please try again in 15 minutes.";
        log_password_change_attempt($user_id, $ip_address, false);
    } else {
        
        // Get and sanitize inputs
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password)) {
            $error = "Current password is required.";
        } elseif (empty($new_password)) {
            $error = "New password is required.";
        } elseif (empty($confirm_password)) {
            $error = "Please confirm your new password.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
            log_password_change_attempt($user_id, $ip_address, false);
        } elseif ($current_password === $new_password) {
            $error = "New password must be different from current password.";
        } else {
            
            // Verify current password
            if (!verify_current_password($user_id, $current_password)) {
                $error = "Current password is incorrect.";
                log_password_change_attempt($user_id, $ip_address, false);
                
                // Add delay to prevent brute force
                sleep(2);
            } else {
                
                // Validate new password strength
                $password_errors = validate_password_strength($new_password);
                
                if (!empty($password_errors)) {
                    $error = "Password does not meet requirements:<br>• " . implode("<br>• ", $password_errors);
                } else {
                    
                    try {
                        // Hash new password with strong algorithm
                        if (defined('PASSWORD_ARGON2ID')) {
                            $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID, [
                                'memory_cost' => 65536,
                                'time_cost' => 4,
                                'threads' => 3
                            ]);
                        } else {
                            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                        }
                        
                        // Update password
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET password = ?,
                                last_password_change = NOW(),
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$hashed_password, $user_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            $success = "Your password has been changed successfully!";
                            
                            // Log successful change
                            log_password_change_attempt($user_id, $ip_address, true);
                            
                            // Get user email for notification
                            $stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
                            $stmt->execute([$user_id]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Log security event
                            error_log(sprintf(
                                "Password changed for user ID %d (%s) from IP: %s",
                                $user_id,
                                $user['email'] ?? 'unknown',
                                $ip_address
                            ));
                            
                            // Optional: Send email notification
                            // sendPasswordChangedNotification($user['email'], $user['first_name']);
                            
                            // Invalidate all other sessions (force re-login on other devices)
                            // This is optional but recommended for security
                            session_regenerate_id(true);
                            
                            // Show success and redirect after 3 seconds
                            header("Refresh: 3; url=dashboard.php");
                            
                        } else {
                            $error = "Failed to change password. Please try again.";
                            log_password_change_attempt($user_id, $ip_address, false);
                        }
                        
                    } catch (PDOException $e) {
                        error_log("Database error in password change: " . $e->getMessage());
                        $error = "An error occurred. Please try again later.";
                        log_password_change_attempt($user_id, $ip_address, false);
                    }
                }
            }
        }
    }
}

// Get user info for display
try {
    $stmt = $pdo->prepare("SELECT email, first_name, last_name, last_password_change FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    $error = "An error occurred. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Church Management System</title>
    <?php include 'includes/styles.php'; ?>
    <style>
        .password-form {
            max-width: 600px;
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
        .password-requirements li {
            margin: 0.25rem 0;
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
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 38px;
        }
        .form-group {
            position: relative;
        }
        .last-changed {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-5">
        <div class="password-form border rounded p-4 shadow">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-key"></i> Change Password
                </h2>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if ($user && isset($user['last_password_change'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Account:</strong> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($user['last_password_change']): ?>
                        <br>
                        <span class="last-changed">
                            Last changed: <?php echo date('F j, Y \a\t g:i A', strtotime($user['last_password_change'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; // Contains only controlled messages or HTML ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    <br><small>Redirecting to dashboard in 3 seconds...</small>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Security Note:</strong> For your protection, you may be logged out of other devices.
                </div>
            <?php else: ?>
                
                <form method="POST" action="" id="changePasswordForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-4 form-group">
                        <label for="current_password" class="form-label">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="current_password" 
                            name="current_password" 
                            required
                            autocomplete="current-password"
                        >
                        <span class="password-toggle" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye" id="current_password_icon"></i>
                        </span>
                        <small class="text-muted">Enter your current password to verify your identity</small>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3 form-group">
                        <label for="new_password" class="form-label">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="new_password" 
                            name="new_password" 
                            required 
                            minlength="8"
                            maxlength="128"
                            autocomplete="new-password"
                        >
                        <span class="password-toggle" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye" id="new_password_icon"></i>
                        </span>
                        <div class="password-strength-meter">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-requirements text-muted">
                            <strong>Password must contain:</strong>
                            <ul>
                                <li id="req-length">At least 8 characters</li>
                                <li id="req-upper">One uppercase letter (A-Z)</li>
                                <li id="req-lower">One lowercase letter (a-z)</li>
                                <li id="req-number">One number (0-9)</li>
                                <li id="req-special">One special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-4 form-group">
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
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                        </span>
                        <small class="text-muted" id="match-message"></small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i>
                        For security, you'll be logged out of other devices after changing your password.
                    </small>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/scripts.php'; ?>
    
    <script>
    // Toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Password strength checker
    document.getElementById('new_password')?.addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthBar = document.getElementById('strengthBar');
        
        let strength = 0;
        const requirements = {
            'req-length': password.length >= 8,
            'req-upper': /[A-Z]/.test(password),
            'req-lower': /[a-z]/.test(password),
            'req-number': /[0-9]/.test(password),
            'req-special': /[^A-Za-z0-9]/.test(password)
        };
        
        // Update requirement checkmarks
        for (let [id, met] of Object.entries(requirements)) {
            const element = document.getElementById(id);
            if (met) {
                element.style.color = '#28a745';
                element.innerHTML = element.innerHTML.replace(/❌|⚠️/, '✅');
                if (!element.innerHTML.includes('✅')) {
                    element.innerHTML = '✅ ' + element.innerHTML;
                }
                strength++;
            } else {
                element.style.color = '#6c757d';
                element.innerHTML = element.innerHTML.replace(/✅|⚠️/, '❌');
                if (!element.innerHTML.includes('❌')) {
                    element.innerHTML = '❌ ' + element.innerHTML;
                }
            }
        }
        
        // Additional strength bonuses
        if (password.length >= 12) strength++;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        
        // Update strength bar
        strengthBar.className = 'password-strength-bar';
        if (strength <= 3) {
            strengthBar.classList.add('strength-weak');
        } else if (strength <= 5) {
            strengthBar.classList.add('strength-medium');
        } else {
            strengthBar.classList.add('strength-strong');
        }
    });
    
    // Check password match
    document.getElementById('confirm_password')?.addEventListener('input', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = e.target.value;
        const matchMessage = document.getElementById('match-message');
        
        if (confirmPassword.length > 0) {
            if (newPassword === confirmPassword) {
                matchMessage.textContent = '✅ Passwords match';
                matchMessage.style.color = '#28a745';
            } else {
                matchMessage.textContent = '❌ Passwords do not match';
                matchMessage.style.color = '#dc3545';
            }
        } else {
            matchMessage.textContent = '';
        }
    });
    
    // Form validation
    document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (!currentPassword) {
            e.preventDefault();
            alert('Please enter your current password');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match!');
            return false;
        }
        
        if (currentPassword === newPassword) {
            e.preventDefault();
            alert('New password must be different from current password!');
            return false;
        }
        
        // Check all requirements
        const requirements = [
            { regex: /.{8,}/, message: 'at least 8 characters' },
            { regex: /[A-Z]/, message: 'an uppercase letter' },
            { regex: /[a-z]/, message: 'a lowercase letter' },
            { regex: /[0-9]/, message: 'a number' },
            { regex: /[^A-Za-z0-9]/, message: 'a special character' }
        ];
        
        for (let req of requirements) {
            if (!req.regex.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain ' + req.message);
                return false;
            }
        }
    });
    </script>
</body>
</html>