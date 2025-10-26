<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

/**
 * SECURE MULTI-TENANT LOGIN SYSTEM
 * Render + Supabase Compatible
 */

define('DB_INCLUDED', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_session.php';
require_once __DIR__ . '/includes/security.php';

// Security Headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Start secure session
start_secure_session();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? '';

// Validate redirect URL
if (!empty($redirect)) {
    $parsed_url = parse_url($redirect);
    if (isset($parsed_url['host']) && $parsed_url['host'] !== $_SERVER['HTTP_HOST']) {
        $redirect = 'dashboard.php';
    }
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF validation
        $submitted_token = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'] ?? '';
        
        if (empty($submitted_token)) {
            throw new Exception('Security token missing. Please try again.');
        }
        
        if (empty($session_token)) {
            throw new Exception('Session expired. Please refresh the page.');
        }
        
        if (!hash_equals($session_token, $submitted_token)) {
            // Log the mismatch for debugging
            error_log("CSRF mismatch - Session: " . substr($session_token, 0, 10) . "... vs Submitted: " . substr($submitted_token, 0, 10) . "...");
            throw new Exception('Security token invalid. Please refresh and try again.');
        }
        
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Input validation
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password.');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_@.]{3,50}$/', $username)) {
            throw new Exception('Invalid username format.');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters.');
        }

        // Check for lockout
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_attempts 
            FROM login_attempts 
            WHERE (email_entered = ? OR user_id = (SELECT id FROM users WHERE username = ? OR email = ?))
            AND was_successful = FALSE 
            AND attempt_time > NOW() - INTERVAL '15 minutes'
        ");
        $stmt->execute([$username, $username, $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['failed_attempts'] >= 5) {
            throw new Exception('Too many failed attempts. Account temporarily locked.');
        }

        // Log attempt (initially as failed)
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (user_id, email_entered, ip_address, attempt_time, was_successful)
            VALUES ((SELECT id FROM users WHERE username = ? OR email = ?), ?, ?, NOW(), FALSE)
            RETURNING attempt_id
        ");
        $stmt->execute([$username, $username, $username, $ip_address]);
        $attempt_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $attempt_id = $attempt_result['attempt_id'];

        // Get user with all hierarchy data
        $stmt = $pdo->prepare("
            SELECT 
                u.*,
                o.org_name,
                o.org_code,
                p.parish_name,
                d.deanery_name,
                a.archdeaconry_name,
                dio.diocese_name,
                prov.province_name
            FROM users u
            LEFT JOIN organizations o ON u.organization_id = o.id
            LEFT JOIN parishes p ON u.parish_id = p.parish_id
            LEFT JOIN deaneries d ON u.deanery_id = d.deanery_id
            LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN dioceses dio ON u.diocese_id = dio.diocese_id
            LEFT JOIN provinces prov ON u.province_id = prov.province_id
            WHERE u.username = ? OR u.email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            log_activity('LOGIN_FAILED', null, null, ['username' => $username, 'ip' => $ip_address]);
            throw new Exception('Invalid username or password.');
        }

        // Check account status
        if ($user['account_status'] === 'suspended') {
            throw new Exception('Your account has been suspended.');
        }
        if ($user['account_status'] === 'pending') {
            throw new Exception('Your account is pending verification.');
        }
        if ($user['account_status'] === 'inactive') {
            throw new Exception('Your account is inactive.');
        }
        if ($user['email_verified'] == FALSE) {
            throw new Exception('Please verify your email address first.');
        }

        // Success! Update attempt
        $stmt = $pdo->prepare("UPDATE login_attempts SET was_successful = TRUE WHERE attempt_id = ?");
        $stmt->execute([$attempt_id]);

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set all session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['phone_number'] = $user['phone_number'];
        $_SESSION['gender'] = $user['gender'];
        $_SESSION['organization_id'] = $user['organization_id'];
        $_SESSION['org_name'] = $user['org_name'];
        $_SESSION['org_code'] = $user['org_code'];
        $_SESSION['role_level'] = $user['role_level'];
        $_SESSION['province_id'] = $user['province_id'];
        $_SESSION['diocese_id'] = $user['diocese_id'];
        $_SESSION['archdeaconry_id'] = $user['archdeaconry_id'];
        $_SESSION['deanery_id'] = $user['deanery_id'];
        $_SESSION['parish_id'] = $user['parish_id'];
        $_SESSION['province_name'] = $user['province_name'];
        $_SESSION['diocese_name'] = $user['diocese_name'];
        $_SESSION['archdeaconry_name'] = $user['archdeaconry_name'];
        $_SESSION['deanery_name'] = $user['deanery_name'];
        $_SESSION['parish_name'] = $user['parish_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Log successful login
        log_activity('LOGIN_SUCCESS', null, null, ['ip' => $ip_address]);

        // Redirect
        header('Location: ' . (!empty($redirect) ? $redirect : 'dashboard.php'));
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Login error: " . $error . " | User: " . ($username ?? 'unknown'));
        
        // Regenerate token on error
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Get fresh token for form
$csrf_token_for_form = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token_for_form;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Church Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="css/login.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4A90E2">
</head>
<body>
    <div class="login-container">
        <div class="login-header text-center mb-4">
            <h2>Welcome Back</h2>
            <p>Church Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                <?php if (strpos($error, 'token') !== false || strpos($error, 'Session') !== false): ?>
                    <hr>
                    <small>
                        <a href="?" class="alert-link">Click here to refresh and try again</a>
                    </small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_for_form, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="mb-3">
                <label for="username" class="form-label">Username or Email</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    pattern="[a-zA-Z0-9_@.]{3,50}"
                    title="3-50 characters, only letters, numbers, and @._ allowed"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    required
                    minlength="8"
                >
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>

        <div class="text-center mt-4">
            <p><a href="reset_password.php">Forgot Password?</a></p>
            <p>Don't have an account? <a href="register.php" class="fw-bold">Register Here</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>
</html>
