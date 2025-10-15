<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * ============================================
 * MULTI-TENANT LOGIN SYSTEM
 * Phase 4: Updated with Unified Dashboard & Scope Sessions
 * ============================================
 */

define('DB_INCLUDED', true); // Add this before including security.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/security.php';

start_secure_session();

// Redirect if already logged in - NOW GOES TO UNIFIED DASHBOARD
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check for lockout
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_attempts 
            FROM login_attempts 
            WHERE (email_entered = ? OR user_id = (SELECT id FROM users WHERE username = ? OR email = ?))
            AND was_successful = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$username, $username, $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['failed_attempts'] >= 20) {
            $error = 'Too many failed attempts. Account temporarily locked. Try again later.';
        } else {
            try {
                // Log attempt (initially as failed)
                $stmt = $pdo->prepare("
                    INSERT INTO login_attempts (user_id, email_entered, ip_address, attempt_time, was_successful)
                    VALUES ((SELECT id FROM users WHERE username = ? OR email = ?), ?, ?, NOW(), 0)
                ");
                $stmt->execute([$username, $username, $username, $ip_address]);
                $attempt_id = $pdo->lastInsertId();

                // Get user data with ALL hierarchy information
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

                if ($user && password_verify($password, $user['password'])) {
                    // Check account status
                    if ($user['account_status'] === 'suspended') {
                        $error = 'Your account has been suspended. Please contact your administrator.';
                    } elseif ($user['account_status'] === 'pending') {
                        $error = 'Your account is pending email verification. Please check your email.';
                    } elseif ($user['account_status'] === 'inactive') {
                        $error = 'Your account is inactive. Please contact support.';
                    } elseif ($user['email_verified'] == 0) {
                        $error = 'Please verify your email address before logging in.';
                    } else {
                        // All checks passed - proceed with login
                        if (empty($error)) {
                            // Update login attempt as successful
                            $stmt = $pdo->prepare("
                                UPDATE login_attempts 
                                SET was_successful = 1 
                                WHERE attempt_id = ?
                            ");
                            $stmt->execute([$attempt_id]);

                            // Regenerate session ID for security
                            session_regenerate_id(true);

                            // Set session variables - INCLUDES ALL SCOPE IDs
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['first_name'] = $user['first_name'];
                            $_SESSION['last_name'] = $user['last_name'];
                            $_SESSION['phone_number'] = $user['phone_number'];
                            $_SESSION['gender'] = $user['gender'];
                            
                            // Organization info
                            $_SESSION['organization_id'] = $user['organization_id'];
                            $_SESSION['org_name'] = $user['org_name'];
                            $_SESSION['org_code'] = $user['org_code'];
                            
                            // Role information
                            $_SESSION['role_level'] = $user['role_level'];
                            
                            // SCOPE IDs - Critical for scope helpers
                            $_SESSION['province_id'] = $user['province_id'];
                            $_SESSION['diocese_id'] = $user['diocese_id'];
                            $_SESSION['archdeaconry_id'] = $user['archdeaconry_id'];
                            $_SESSION['deanery_id'] = $user['deanery_id'];
                            $_SESSION['parish_id'] = $user['parish_id'];
                            
                            // Scope Names (for display)
                            $_SESSION['province_name'] = $user['province_name'];
                            $_SESSION['diocese_name'] = $user['diocese_name'];
                            $_SESSION['archdeaconry_name'] = $user['archdeaconry_name'];
                            $_SESSION['deanery_name'] = $user['deanery_name'];
                            $_SESSION['parish_name'] = $user['parish_name'];
                            
                            $_SESSION['login_time'] = time();

                            // Update last login
                            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            $stmt->execute([$user['id']]);

                            // Log activity
                            log_activity('LOGIN_SUCCESS');

                            // Redirect to UNIFIED DASHBOARD
                            if (!empty($redirect)) {
                                header('Location: ' . $redirect);
                            } else {
                                header('Location: dashboard.php');
                            }
                            exit;
                        }
                    }
                } else {
                    $error = 'Invalid username or password.';
                    log_activity('LOGIN_FAILED', null, null, ['username' => $username]);
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
                // For debugging only - remove in production
                // $error .= ' Debug: ' . $e->getMessage();
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
    <title>Login - Church Management System</title>
     <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Login Styles -->
    <link href="css/login.css" rel="stylesheet">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/anglicankenya/manifest.json">
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
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username or Email</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    value="<?php echo htmlspecialchars($username ?? ''); ?>"
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
                >
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <p><a href="reset_password.php" class="text-decoration-none">Forgot Password?</a></p>
            <p>Don't have an account? <a href="register.php" class="fw-bold text-decoration-none">Register Here</a></p>
            <small class="text-muted d-block mt-3">Secured by Multi-Tenant Authentication</small>
        </div>
    </div>
    <script src="js/login.js"></script>


</body>
</html>