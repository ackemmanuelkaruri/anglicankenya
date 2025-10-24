<?php
/**
 * ============================================
 * ACCESS DENIED PAGE
 * Friendly error page for unauthorized access attempts
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/security.php';

start_secure_session();

// Get denial details from session
$reason = $_SESSION['access_denied_reason'] ?? 'You do not have permission to access this resource.';
$resource = $_SESSION['access_denied_resource'] ?? null;
$action = $_SESSION['access_denied_action'] ?? null;
$required_roles = $_SESSION['access_denied_required_roles'] ?? [];

// Get user info if logged in
$user_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'Guest';
$user_role = $_SESSION['role_level'] ?? 'guest';

// Clear session variables
unset($_SESSION['access_denied_reason']);
unset($_SESSION['access_denied_resource']);
unset($_SESSION['access_denied_action']);
unset($_SESSION['access_denied_required_roles']);

// Get referer for back button
$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';

// Get super admin contact info
try {
    $stmt = $pdo->prepare("
        SELECT email, first_name, last_name, phone_number 
        FROM users 
        WHERE role_level = 'super_admin' 
        LIMIT 1
    ");
    $stmt->execute();
    $super_admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $super_admin = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Church Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .access-denied-container {
            max-width: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .access-denied-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .access-denied-header i {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        .access-denied-body {
            padding: 40px 30px;
        }
        .error-code {
            font-size: 48px;
            font-weight: bold;
            color: #f5576c;
            text-align: center;
            margin-bottom: 20px;
        }
        .reason-box {
            background: #f8f9fa;
            border-left: 4px solid #f5576c;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .contact-admin {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .btn-custom {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            color: white;
        }
        .required-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .role-badge {
            background: #f5576c;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="access-denied-header">
            <i class="fas fa-ban"></i>
            <h1 class="mb-0">Access Denied</h1>
        </div>
        
        <div class="access-denied-body">
            <div class="error-code">403</div>
            
            <h4 class="text-center mb-4">Sorry, <?php echo htmlspecialchars($user_name); ?>!</h4>
            
            <div class="reason-box">
                <strong><i class="fas fa-exclamation-circle me-2"></i>Reason:</strong>
                <p class="mb-0 mt-2"><?php echo htmlspecialchars($reason); ?></p>
            </div>
            
            <?php if ($resource && $action): ?>
            <div class="info-box">
                <strong><i class="fas fa-info-circle me-2"></i>What you tried to do:</strong>
                <p class="mb-0 mt-2">
                    Action: <strong><?php echo htmlspecialchars(ucfirst($action)); ?></strong><br>
                    Resource: <strong><?php echo htmlspecialchars(ucfirst($resource)); ?></strong>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($required_roles)): ?>
            <div class="info-box">
                <strong><i class="fas fa-user-shield me-2"></i>Required Role(s):</strong>
                <div class="required-roles">
                    <?php foreach ($required_roles as $role): ?>
                    <span class="role-badge">
                        <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($role, '_'))); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    <strong>Your current role:</strong> 
                    <span class="badge bg-info text-dark">
                        <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($user_role, '_'))); ?>
                    </span>
                </p>
            </div>
            
            <?php if ($super_admin): ?>
            <div class="contact-admin">
                <h6 class="mb-3">
                    <i class="fas fa-phone-alt me-2"></i>Need Access?
                </h6>
                <p class="mb-2">Contact your system administrator:</p>
                <ul class="mb-0">
                    <li>
                        <strong>Name:</strong> 
                        <?php echo htmlspecialchars($super_admin['first_name'] . ' ' . $super_admin['last_name']); ?>
                    </li>
                    <li>
                        <strong>Email:</strong> 
                        <a href="mailto:<?php echo htmlspecialchars($super_admin['email']); ?>">
                            <?php echo htmlspecialchars($super_admin['email']); ?>
                        </a>
                    </li>
                    <?php if ($super_admin['phone_number']): ?>
                    <li>
                        <strong>Phone:</strong> 
                        <?php echo htmlspecialchars($super_admin['phone_number']); ?>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="d-grid gap-3 mt-4">
                <a href="<?php echo htmlspecialchars($referer); ?>" class="btn btn-secondary-custom btn-custom">
                    <i class="fas fa-arrow-left me-2"></i>Go Back
                </a>
                <a href="dashboard.php" class="btn btn-primary-custom btn-custom">
                    <i class="fas fa-home me-2"></i>Return to Dashboard
                </a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn btn-primary-custom btn-custom">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    If you believe this is an error, please contact the system administrator.
                </small>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-dismiss after reading (optional)
        setTimeout(function() {
            var backBtn = document.querySelector('a[href*="Go Back"]');
            if (backBtn) {
                backBtn.classList.add('pulse');
            }
        }, 3000);
    </script>
</body>
</html>