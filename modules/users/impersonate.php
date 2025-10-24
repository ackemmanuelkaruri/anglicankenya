<?php
/**
 * User Impersonation Module
 */

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/rbac.php';

start_secure_session();

// Authentication and permission check
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] !== 'super_admin') {
    header('Location: ../../access_denied.php');
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process impersonation request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    $target_user_id = (int)$_POST['user_id'];
    
    // Get target user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        $_SESSION['error'] = "User not found";
        header('Location: list.php');
        exit;
    }
    
    // Don't allow impersonating super admins
    if ($target_user['role_level'] === 'super_admin') {
        $_SESSION['error'] = "Cannot impersonate Super Admin";
        header('Location: list.php');
        exit;
    }
    
    // Start impersonation
    if (start_impersonation($target_user_id)) {
        $_SESSION['success'] = "Now impersonating " . htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name']);
        header('Location: ../../dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = "Failed to start impersonation";
        header('Location: list.php');
        exit;
    }
}

// GET request - show confirmation
 $user_id = (int)$_GET['id'];
if (!$user_id) {
    header('Location: list.php');
    exit;
}

// Get user details
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$user_id]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: list.php');
    exit;
}

 $page_title = "Impersonate User";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    
    <title><?php echo htmlspecialchars($page_title); ?> - Church Management System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-user-secret me-2"></i>
                            <?php echo htmlspecialchars($page_title); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> You are about to impersonate another user. All actions will be logged and attributed to you.
                        </div>
                        
                        <div class="mb-4">
                            <h5>User Details:</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_level']))); ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-user-secret me-2"></i>
                                    Yes, Impersonate This User
                                </button>
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>