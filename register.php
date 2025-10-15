<?php
/**
 * ============================================
 * MULTI-TENANT REGISTRATION SYSTEM
 * Phase 3: User Registration with Email Verification
 * ============================================
 */
require_once 'db.php';
require_once 'includes/security.php';
require_once 'includes/email_helper.php'; // PHPMailer email helper
require_once 'anglican_province.php'; // Added file-based hierarchy system


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

// Process registration form - ONLY on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Validation
    if (empty($diocese) || empty($archdeaconry) || empty($deanery) || empty($parish)) {
        $error = 'Please select your church (diocese, archdeaconry, deanery, and parish).';
    } elseif (empty($first_name) || empty($last_name)) {
        $error = 'Please enter your full name.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($username) || strlen($username) < 4) {
        $error = 'Username must be at least 4 characters long.';
    } elseif (empty($phone)) {
        $error = 'Please enter your phone number.';
    } elseif (empty($password) || strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Validate hierarchy using file-based system
            $dioceseData = loadDiocese($diocese);
            if (!$dioceseData) {
                throw new Exception("Diocese not found: " . htmlspecialchars($diocese) . ". Please contact support.");
            }

            // Validate archdeaconry exists in the diocese
            $archdeaconryFound = false;
            foreach ($dioceseData['archdeaconries'] as $a) {
                if ($a['name'] === $archdeaconry) {
                    $archdeaconryFound = true;

                    // Validate deanery exists in the archdeaconry
                    $deaneryFound = false;
                    if (isset($a['deaneries'])) {
                        foreach ($a['deaneries'] as $de) {
                            if ($de['name'] === $deanery) {
                                $deaneryFound = true;

                                // Validate parish exists in the deanery
                                $parishFound = false;
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

            if (!$archdeaconryFound) {
                throw new Exception("Archdeaconry not found: " . htmlspecialchars($archdeaconry));
            }
            if (!$deaneryFound) {
                throw new Exception("Deanery not found: " . htmlspecialchars($deanery));
            }
            if (!$parishFound) {
                throw new Exception("Parish not found: " . htmlspecialchars($parish));
            }

            // Now get or create the database entries
            $stmt = $pdo->prepare("SELECT diocese_id FROM dioceses WHERE diocese_name = ?");
            $stmt->execute([$diocese]);
            $diocese_id = $stmt->fetchColumn();

            if (!$diocese_id) {
                // Create diocese in database
                $stmt = $pdo->prepare("INSERT INTO dioceses (diocese_name) VALUES (?)");
                $stmt->execute([$diocese]);
                $diocese_id = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("SELECT archdeaconry_id FROM archdeaconries WHERE archdeaconry_name = ? AND diocese_id = ?");
            $stmt->execute([$archdeaconry, $diocese_id]);
            $archdeaconry_id = $stmt->fetchColumn();

            if (!$archdeaconry_id) {
                // Create archdeaconry in database
                $stmt = $pdo->prepare("INSERT INTO archdeaconries (diocese_id, archdeaconry_name) VALUES (?, ?)");
                $stmt->execute([$diocese_id, $archdeaconry]);
                $archdeaconry_id = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("SELECT deanery_id FROM deaneries WHERE deanery_name = ? AND archdeaconry_id = ?");
            $stmt->execute([$deanery, $archdeaconry_id]);
            $deanery_id = $stmt->fetchColumn();

            if (!$deanery_id) {
                // Create deanery in database
                $stmt = $pdo->prepare("INSERT INTO deaneries (archdeaconry_id, deanery_name) VALUES (?, ?)");
                $stmt->execute([$archdeaconry_id, $deanery]);
                $deanery_id = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE parish_name = ? AND deanery_id = ?");
            $stmt->execute([$parish, $deanery_id]);
            $parish_id = $stmt->fetchColumn();

            if (!$parish_id) {
                // Create parish in database
                $stmt = $pdo->prepare("INSERT INTO parishes (deanery_id, parish_name) VALUES (?, ?)");
                $stmt->execute([$deanery_id, $parish]);
                $parish_id = $pdo->lastInsertId();
            }

            // Find organization based on parish selection
            $stmt = $pdo->prepare("
                SELECT id FROM organizations 
                WHERE diocese_id = ? AND archdeaconry_id = ? AND deanery_id = ? AND parish_id = ?
            ");
            $stmt->execute([$diocese_id, $archdeaconry_id, $deanery_id, $parish_id]);
            $org = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$org) {
                // Create organization if it doesn't exist
                $org_code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $diocese), 0, 3)) . 
                           strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $archdeaconry), 0, 3)) . 
                           strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $deanery), 0, 3)) . 
                           strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $parish), 0, 3));
                
                $stmt = $pdo->prepare("
                    INSERT INTO organizations (
                        org_name, org_code, diocese_id, archdeaconry_id, deanery_id, parish_id, 
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $parish . " Parish",
                    $org_code,
                    $diocese_id,
                    $archdeaconry_id,
                    $deanery_id,
                    $parish_id
                ]);
                
                $org_id = $pdo->lastInsertId();
            } else {
                $org_id = $org['id'];
            }

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists. Please choose another.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered. Please use another or login.';
                } else {
                    // Generate email verification token
                    $email_verification_token = bin2hex(random_bytes(32));
                    
                    // Hash password
                    if (defined('PASSWORD_ARGON2ID')) {
                        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    // Insert new user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (
                            organization_id, 
                            province_id,
                            diocese_id,
                            archdeaconry_id,
                            deanery_id,
                            parish_id,
                            first_name, 
                            last_name, 
                            email, 
                            username, 
                            phone_number, 
                            gender, 
                            password, 
                            role_level, 
                            account_status,
                            email_verification_token,
                            email_verified,
                            created_at
                        ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'pending', ?, 0, NOW())
                    ");
                                        
                    $stmt->execute([
                        $org_id,
                        $diocese_id,
                        $archdeaconry_id,
                        $deanery_id,
                        $parish_id,
                        $first_name,
                        $last_name,
                        $email,
                        $username,
                        $phone,
                        $gender,
                        $hashed_password,
                        $email_verification_token
                    ]);
                    
                    $new_user_id = $pdo->lastInsertId();
                    
                    // --- NEW: store token expiry (24 hours) ---
                    try {
                        $expiry = (new DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');
                        $u = $pdo->prepare("UPDATE users SET email_token_expires_at = ? WHERE id = ?");
                        $u->execute([$expiry, $new_user_id]);
                    } catch (Exception $ex) {
                        // not critical — log and continue
                        error_log("Failed to set token expiry: " . $ex->getMessage());
                    }
                    
                    // Send verification email via PHPMailer helper
                    // Ensure includes/email.php defines sendVerificationEmail($email, $first_name, $verification_token)
                    $sent = false;
                    try {
                        $sent = sendVerificationEmail($email, $first_name, $email_verification_token);
                    } catch (Exception $e) {
                        error_log("sendVerificationEmail error: " . $e->getMessage());
                        $sent = false;
                    }
                    
                    if ($sent) {
                        $success = 'Registration successful! Please check your email to verify your account.';
                    } else {
                        // If sending failed, keep the user but notify
                        $error = 'Registration saved, but we could not send the verification email. Please contact support.';
                        error_log("Verification email failed to send for user id {$new_user_id}, email: {$email}");
                    }
                    
                    // Clear form fields after success/fail to avoid refilling
                    $first_name = $last_name = $email = $username = $phone = $gender = '';
                    $diocese = $archdeaconry = $deanery = $parish = '';
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}

// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));
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
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                <hr>
                <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="" id="registrationForm">
                <!-- Church Selection -->
                <div class="form-section">
                    <h5>Select Your Church</h5>
                    <div class="church-selection">
                        <label for="diocese" class="form-label">Diocese *</label>
                        <select class="form-select" id="diocese" name="diocese" required>
                            <option value="">-- Select Diocese --</option>
                        </select>
                        
                        <label for="archdeaconry" class="form-label">Archdeaconry *</label>
                        <select class="form-select" id="archdeaconry" name="archdeaconry" required disabled>
                            <option value="">-- Select Archdeaconry --</option>
                        </select>
                        
                        <label for="deanery" class="form-label">Deanery *</label>
                        <select class="form-select" id="deanery" name="deanery" required disabled>
                            <option value="">-- Select Deanery --</option>
                        </select>
                        
                        <label for="parish" class="form-label">Parish *</label>
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
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone); ?>" 
                                   placeholder="0712345678" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender *</label>
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
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username); ?>" 
                               minlength="4" required>
                        <small class="text-muted">At least 4 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="8" required>
                        <div class="password-strength" id="passwordStrength"></div>
                        <small class="text-muted">At least 8 characters with mixed case, numbers, and symbols</small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
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
