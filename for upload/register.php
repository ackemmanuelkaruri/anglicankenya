<?php
/**
 * ============================================
 * MULTI-TENANT REGISTRATION SYSTEM
 * Phase 3: User Registration with Email Verification
 * WITH FULL SECURITY PROTECTION
 * ============================================
 */
define('DB_INCLUDED', true);

require_once 'db.php';
require_once 'includes/security.php';
require_once 'includes/email_helper.php';
require_once 'anglican_province.php';
require_once 'includes/scope_helpers.php'; // For CSRF protection

start_secure_session();

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: /user/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Initialize form variables
$diocese = '';
$archdeaconry = '';
$deanery = '';
$parish = '';
$first_name = '';
$last_name = '';
$email = '';
$username = '';
$phone = '';
$gender = '';

// ============================================
// RATE LIMITING FUNCTION
// ============================================
function check_registration_rate_limit($ip_address, $email = null) {
    global $pdo;
    
    try {
        // Check IP-based rate limiting (max 3 registrations per hour)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM registration_attempts 
            WHERE ip_address = ? 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip_address]);
        $result = $stmt->fetch();
        
        if ($result['attempt_count'] >= 3) {
            return false;
        }
        
        // If email provided, check email-based rate limiting
        if ($email) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM registration_attempts 
                WHERE email = ? 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$email]);
            $result = $stmt->fetch();
            
            if ($result['attempt_count'] >= 2) {
                return false;
            }
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true; // Fail open
    }
}

function log_registration_attempt($ip_address, $email, $username, $success) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO registration_attempts 
            (ip_address, email, username, success, attempted_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$ip_address, $email, $username, $success ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Failed to log registration attempt: " . $e->getMessage());
    }
}

// ============================================
// INPUT VALIDATION FUNCTIONS
// ============================================
function validate_phone_number($phone) {
    // Kenya phone format: 07XXXXXXXX or 01XXXXXXXX or +2547XXXXXXXX or +2541XXXXXXXX
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    if (preg_match('/^(\+254|254|0)?[71]\d{8}$/', $phone)) {
        return true;
    }
    
    return false;
}

function validate_username($username) {
    // Username: 4-30 characters, alphanumeric, underscore, dash only
    if (strlen($username) < 4 || strlen($username) > 30) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return false;
    }
    
    // Prevent reserved usernames
    $reserved = ['admin', 'administrator', 'root', 'system', 'user', 'moderator', 'mod'];
    if (in_array(strtolower($username), $reserved)) {
        return false;
    }
    
    return true;
}

function validate_name($name) {
    // Names: 2-50 characters, letters, spaces, hyphens, apostrophes only
    if (strlen($name) < 2 || strlen($name) > 50) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z\s\'-]+$/', $name)) {
        return false;
    }
    
    return true;
}

function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "at least 8 characters";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "one special character";
    }
    
    return $errors;
}

// ============================================
// HONEYPOT CHECK (Bot Detection)
// ============================================
function is_bot_submission() {
    // Honeypot field should be empty
    if (!empty($_POST['website'])) {
        return true;
    }
    
    // Check form submission time (should take at least 5 seconds)
    if (isset($_POST['form_start_time'])) {
        $time_taken = time() - (int)$_POST['form_start_time'];
        if ($time_taken < 5) {
            return true;
        }
    }
    
    return false;
}

// ============================================
// MAIN FORM PROCESSING
// ============================================
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF Protection
    verify_csrf_token();
    
    // Bot Detection
    if (is_bot_submission()) {
        error_log("Bot submission detected from IP: {$ip_address}");
        $success = 'Registration successful! Please check your email to verify your account.';
    } else {
        
        // Sanitize inputs
        $diocese = sanitize_input($_POST['diocese'] ?? '');
        $archdeaconry = sanitize_input($_POST['archdeaconry'] ?? '');
        $deanery = sanitize_input($_POST['deanery'] ?? '');
        $parish = sanitize_input($_POST['parish'] ?? '');
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $username = sanitize_input($_POST['username'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $gender = sanitize_input($_POST['gender'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Check rate limiting
        if (!check_registration_rate_limit($ip_address, $email)) {
            $error = 'Too many registration attempts. Please try again in 1 hour.';
            log_registration_attempt($ip_address, $email, $username, false);
        } else {
            
            // Input Validation
            if (empty($diocese) || empty($archdeaconry) || empty($deanery) || empty($parish)) {
                $error = 'Please select your church hierarchy completely.';
            } elseif (!validate_name($first_name)) {
                $error = 'First name must be 2-50 characters and contain only letters.';
            } elseif (!validate_name($last_name)) {
                $error = 'Last name must be 2-50 characters and contain only letters.';
            } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($email) > 255) {
                $error = 'Email address is too long.';
            } elseif (!validate_username($username)) {
                $error = 'Username must be 4-30 characters and contain only letters, numbers, underscore, or dash.';
            } elseif (!validate_phone_number($phone)) {
                $error = 'Please enter a valid Kenya phone number (e.g., 0712345678).';
            } elseif (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
                $error = 'Please select a valid gender.';
            } elseif (empty($password)) {
                $error = 'Password is required.';
            } else {
                $password_errors = validate_password_strength($password);
                
                if (!empty($password_errors)) {
                    $error = 'Password must contain: ' . implode(', ', $password_errors) . '.';
                } elseif ($password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } else {
                    
                    try {
                        $pdo->beginTransaction();
                        
                        // Validate hierarchy
                        $dioceseData = loadDiocese($diocese);
                        if (!$dioceseData) {
                            throw new Exception("Invalid diocese selection.");
                        }
                        
                        $archdeaconryFound = false;
                        $deaneryFound = false;
                        $parishFound = false;
                        
                        foreach ($dioceseData['archdeaconries'] as $a) {
                            if ($a['name'] === $archdeaconry) {
                                $archdeaconryFound = true;
                                if (isset($a['deaneries'])) {
                                    foreach ($a['deaneries'] as $de) {
                                        if ($de['name'] === $deanery) {
                                            $deaneryFound = true;
                                            if (isset($de['parishes'])) {
                                                foreach ($de['parishes'] as $p) {
                                                    if ($p === $parish) {
                                                        $parishFound = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }
                                break;
                            }
                        }
                        
                        if (!$archdeaconryFound || !$deaneryFound || !$parishFound) {
                            throw new Exception("Invalid church hierarchy selection.");
                        }
                        
                        // Get or create diocese
                        $stmt = $pdo->prepare("SELECT diocese_id FROM dioceses WHERE diocese_name = ? LIMIT 1");
                        $stmt->execute([$diocese]);
                        $diocese_id = $stmt->fetchColumn();
                        
                        if (!$diocese_id) {
                            $stmt = $pdo->prepare("INSERT INTO dioceses (diocese_name, created_at) VALUES (?, NOW())");
                            $stmt->execute([$diocese]);
                            $diocese_id = $pdo->lastInsertId();
                        }
                        
                        // Get or create archdeaconry
                        $stmt = $pdo->prepare("SELECT archdeaconry_id FROM archdeaconries WHERE archdeaconry_name = ? AND diocese_id = ? LIMIT 1");
                        $stmt->execute([$archdeaconry, $diocese_id]);
                        $archdeaconry_id = $stmt->fetchColumn();
                        
                        if (!$archdeaconry_id) {
                            $stmt = $pdo->prepare("INSERT INTO archdeaconries (diocese_id, archdeaconry_name, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$diocese_id, $archdeaconry]);
                            $archdeaconry_id = $pdo->lastInsertId();
                        }
                        
                        // Get or create deanery
                        $stmt = $pdo->prepare("SELECT deanery_id FROM deaneries WHERE deanery_name = ? AND archdeaconry_id = ? LIMIT 1");
                        $stmt->execute([$deanery, $archdeaconry_id]);
                        $deanery_id = $stmt->fetchColumn();
                        
                        if (!$deanery_id) {
                            $stmt = $pdo->prepare("INSERT INTO deaneries (archdeaconry_id, deanery_name, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$archdeaconry_id, $deanery]);
                            $deanery_id = $pdo->lastInsertId();
                        }
                        
                        // Get or create parish
                        $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE parish_name = ? AND deanery_id = ? LIMIT 1");
                        $stmt->execute([$parish, $deanery_id]);
                        $parish_id = $stmt->fetchColumn();
                        
                        if (!$parish_id) {
                            $stmt = $pdo->prepare("INSERT INTO parishes (deanery_id, parish_name, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$deanery_id, $parish]);
                            $parish_id = $pdo->lastInsertId();
                        }
                        
                        // Find or create organization
                        $stmt = $pdo->prepare("
                            SELECT id FROM organizations 
                            WHERE diocese_id = ? AND archdeaconry_id = ? AND deanery_id = ? AND parish_id = ?
                            LIMIT 1
                        ");
                        $stmt->execute([$diocese_id, $archdeaconry_id, $deanery_id, $parish_id]);
                        $org = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$org) {
                            $org_code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $diocese), 0, 3)) . 
                                       strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $archdeaconry), 0, 3)) . 
                                       strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $deanery), 0, 3)) . 
                                       strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $parish), 0, 3));
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO organizations (
                                    org_name, org_code, diocese_id, archdeaconry_id, deanery_id, parish_id, created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->execute([$parish . " Parish", $org_code, $diocese_id, $archdeaconry_id, $deanery_id, $parish_id]);
                            $org_id = $pdo->lastInsertId();
                        } else {
                            $org_id = $org['id'];
                        }
                        
                        // Check username/email exists
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                        $stmt->execute([$username]);
                        $username_exists = $stmt->fetch();
                        
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                        $stmt->execute([$email]);
                        $email_exists = $stmt->fetch();
                        
                        if ($username_exists || $email_exists) {
                            $error = 'This username or email is already registered. Please use different credentials.';
                            log_registration_attempt($ip_address, $email, $username, false);
                            $pdo->rollBack();
                        } else {
                            
                            // Generate token
                            $email_verification_token = bin2hex(random_bytes(32));
                            
                            // Hash password
                            if (defined('PASSWORD_ARGON2ID')) {
                                $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                                    'memory_cost' => 65536,
                                    'time_cost' => 4,
                                    'threads' => 3
                                ]);
                            } else {
                                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                            }
                            
                            // Normalize phone
                            $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
                            if (substr($phone, 0, 1) === '0') {
                                $phone = '+254' . substr($phone, 1);
                            } elseif (substr($phone, 0, 3) === '254') {
                                $phone = '+' . $phone;
                            }
                            
                            // Insert user
                            $stmt = $pdo->prepare("
                                INSERT INTO users (
                                    organization_id, province_id, diocese_id, archdeaconry_id, deanery_id, parish_id,
                                    first_name, last_name, email, username, phone_number, gender, password, 
                                    role_level, account_status, email_verification_token, email_verified, 
                                    email_token_expires_at, is_active, created_at
                                ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'pending', ?, 0, ?, 1, NOW())
                            ");
                            
                            $token_expiry = (new DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');
                            
                            $stmt->execute([
                                $org_id, $diocese_id, $archdeaconry_id, $deanery_id, $parish_id,
                                $first_name, $last_name, $email, $username, $phone, $gender,
                                $hashed_password, $email_verification_token, $token_expiry
                            ]);
                            
                            $new_user_id = $pdo->lastInsertId();
                            $pdo->commit();
                            
                            // Send email
                            $sent = false;
                            try {
                                $sent = sendVerificationEmail($email, $first_name, $email_verification_token);
                            } catch (Exception $e) {
                                error_log("Email error for user {$new_user_id}: " . $e->getMessage());
                            }
                            
                            if ($sent) {
                                $success = 'Registration successful! Please check your email to verify your account. The link expires in 24 hours.';
                                log_registration_attempt($ip_address, $email, $username, true);
                                error_log("New user registered: ID {$new_user_id}, Email: {$email}, IP: {$ip_address}");
                                
                                // Clear form
                                $first_name = $last_name = $email = $username = $phone = $gender = '';
                                $diocese = $archdeaconry = $deanery = $parish = '';
                            } else {
                                $error = 'Account created, but verification email failed. Please contact support with username: ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
                                error_log("Email failed for user {$new_user_id}");
                            }
                        }
                        
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log("Registration error: " . $e->getMessage());
                        $error = 'An error occurred during registration. Please try again later.';
                        log_registration_attempt($ip_address, $email, $username, false);
                        
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log("Registration error: " . $e->getMessage());
                        $error = 'An error occurred. Please verify your selections and try again.';
                        log_registration_attempt($ip_address, $email, $username, false);
                    }
                }
            }
        }
    }
}

// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));
$form_start_time = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Church Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/register.css" rel="stylesheet">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'nonce-<?php echo $nonce; ?>' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Create Account</h2>
            <p>Join your church community</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                <hr>
                <div class="mt-3">
                    <a href="login.php" class="btn btn-success">Go to Login</a>
                </div>
                <div class="mt-3 alert alert-info">
                    <small>
                        <strong>Next Steps:</strong><br>
                        1. Check your email inbox (and spam folder)<br>
                        2. Click the verification link within 24 hours<br>
                        3. Log in with your credentials
                    </small>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="" id="registrationForm">
                <?php echo csrf_field(); ?>
                
                <!-- Honeypot (hidden from users, bots fill it) -->
                <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
                <input type="hidden" name="form_start_time" value="<?php echo $form_start_time; ?>">
                
                <!-- Church Selection -->
                <div class="form-section">
                    <h5>Select Your Church</h5>
                    <div class="church-selection">
                        <label for="diocese" class="form-label">Diocese <span class="text-danger">*</span></label>
                        <select class="form-select" id="diocese" name="diocese" required>
                            <option value="">-- Select Diocese --</option>
                        </select>
                        
                        <label for="archdeaconry" class="form-label mt-3">Archdeaconry <span class="text-danger">*</span></label>
                        <select class="form-select" id="archdeaconry" name="archdeaconry" required disabled>
                            <option value="">-- Select Archdeaconry --</option>
                        </select>
                        
                        <label for="deanery" class="form-label mt-3">Deanery <span class="text-danger">*</span></label>
                        <select class="form-select" id="deanery" name="deanery" required disabled>
                            <option value="">-- Select Deanery --</option>
                        </select>
                        
                        <label for="parish" class="form-label mt-3">Parish <span class="text-danger">*</span></label>
                        <select class="form-select" id="parish" name="parish" required disabled>
                            <option value="">-- Select Parish --</option>
                        </select>
                    </div>
                    <small class="text-muted">Can't find your church? Contact the church administrator.</small>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <h5>Personal Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8'); ?>" 
                                   minlength="2" maxlength="50" pattern="[a-zA-Z\s\'-]+" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8'); ?>" 
                                   minlength="2" maxlength="50" pattern="[a-zA-Z\s\'-]+" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" 
                               maxlength="255" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" 
                                   placeholder="0712345678" pattern="^(\+254|254|0)?[71]\d{8}$" required>
                            <small class="text-muted">Format: 0712345678 or +254712345678</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">-- Select --</option>
                                <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($gender === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Account Credentials -->
                <div class="form-section">
                    <h5>Account Credentials</h5>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" 
                               minlength="4" maxlength="30" pattern="[a-zA-Z0-9_-]+" required>
                        <small class="text-muted">4-30 characters: letters, numbers, underscore, or dash only</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="8" maxlength="128" required>
                        <div class="password-strength mt-2" id="passwordStrength"></div>
                        <small class="text-muted">At least 8 characters with uppercase, lowercase, number, and special character</small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <small class="text-danger d-none" id="passwordMismatch">Passwords do not match</small>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#" target="_blank">Terms & Conditions</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-register w-100">
                    Create Account
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="mb-0">
                    Already have an account? 
                    <a href="login.php" class="text-decoration-none fw-bold">Sign In</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/register.js" nonce="<?php echo $nonce; ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $nonce; ?>"></script>
</body>
</html>