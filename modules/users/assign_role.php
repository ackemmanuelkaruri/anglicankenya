<?php
require_once '../../db.php';
require_once '../../includes/init.php';  
require_once '../../includes/security.php';
require_once '../../includes/scope_helpers.php';

// Ensure only super_admin can assign roles
if (!isset($_SESSION['user_id']) || $_SESSION['role_level'] !== 'super_admin') {
    header('Location: ../../login.php');
    exit;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Validate user ID (must be a positive integer)
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id || $user_id <= 0) {
    $error = 'Invalid user ID.';
    $user_id = null;
}

// Define valid roles (whitelist)
$valid_roles = [
    'super_admin', 
    'national_admin', 
    'diocese_admin', 
    'archdeaconry_admin', 
    'deanery_admin', 
    'parish_admin', 
    'member'
];

if ($user_id) {
    // Fetch user details using prepared statement
    $stmt = $pdo->prepare("SELECT id, username, role_level FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'User not found.';
    } else {
        // Handle role assignment
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Protection
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die('Invalid CSRF token. Please refresh the page and try again.');
            }

            if (isset($_POST['role_level'])) {
                // Assign a new role
                $new_role = $_POST['role_level'] ?? '';

                // Validate role is in whitelist
                if (empty($new_role) || !in_array($new_role, $valid_roles, true)) {
                    $error = 'Please select a valid role.';
                } 
                // Prevent self-demotion
                elseif ($user_id == $_SESSION['user_id'] && $new_role !== 'super_admin') {
                    $error = 'You cannot change your own role to a lower privilege level.';
                } 
                else {
                    // Store old role for logging
                    $old_role = $user['role_level'];

                    // Update role using prepared statement
                    $stmt = $pdo->prepare("UPDATE users SET role_level = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$new_role, $user_id])) {
                        // Log the activity
                        try {
                            $stmt_log = $pdo->prepare("
                                INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
                                VALUES (?, 'role_change', ?, ?, NOW())
                            ");
                            $details = json_encode([
                                'target_user_id' => $user_id,
                                'target_username' => $user['username'],
                                'old_role' => $old_role,
                                'new_role' => $new_role,
                                'changed_by' => $_SESSION['user_id']
                            ]);
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $stmt_log->execute([$_SESSION['user_id'], $details, $ip_address]);
                        } catch (Exception $e) {
                            // Log error but don't fail the operation
                            error_log("Failed to log role change: " . $e->getMessage());
                        }

                        $success = 'Role updated successfully from ' . htmlspecialchars($old_role) . ' to ' . htmlspecialchars($new_role) . '.';
                        
                        // Refresh user data
                        $user['role_level'] = $new_role;
                    } else {
                        $error = 'Failed to update the role. Please try again.';
                    }
                }
            } elseif (isset($_POST['remove_admin'])) {
                // Prevent self-demotion
                if ($user_id == $_SESSION['user_id']) {
                    $error = 'You cannot remove your own admin privileges.';
                } else {
                    // Store old role for logging
                    $old_role = $user['role_level'];

                    // Remove admin privileges (set role to member)
                    $stmt = $pdo->prepare("UPDATE users SET role_level = 'member', updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$user_id])) {
                        // Log the activity
                        try {
                            $stmt_log = $pdo->prepare("
                                INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
                                VALUES (?, 'role_revoke', ?, ?, NOW())
                            ");
                            $details = json_encode([
                                'target_user_id' => $user_id,
                                'target_username' => $user['username'],
                                'old_role' => $old_role,
                                'new_role' => 'member',
                                'changed_by' => $_SESSION['user_id']
                            ]);
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $stmt_log->execute([$_SESSION['user_id'], $details, $ip_address]);
                        } catch (Exception $e) {
                            error_log("Failed to log role revoke: " . $e->getMessage());
                        }

                        $success = 'Admin privileges removed successfully.';
                        
                        // Refresh user data
                        $user['role_level'] = 'member';
                    } else {
                        $error = 'Failed to remove admin privileges. Please try again.';
                    }
                }
            }
        }
    }
} else {
    $error = 'Invalid user ID.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign or Remove Role - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="my-4">Assign or Remove Role for User</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($user): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">User Information</h5>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Current Role:</strong> 
                        <span class="badge bg-primary">
                            <?php echo htmlspecialchars($user['role_level'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Role Assignment Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Change Role</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="mb-3">
                            <label for="role_level" class="form-label">Select Role</label>
                            <select class="form-select" id="role_level" name="role_level" required>
                                <option value="">-- Select Role --</option>
                                <?php foreach ($valid_roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>" 
                                            <?php echo ($user['role_level'] === $role) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role)), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Role
                        </button>
                        <a href="../manage_users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </form>
                </div>
            </div>

            <!-- Remove Admin Form (if the user is an admin) -->
            <?php if ($user['role_level'] !== 'member' && $user_id != $_SESSION['user_id']): ?>
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5>Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove admin privileges from this user? This action will demote them to a regular member.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="remove_admin" value="1">
                            
                            <p class="text-danger">
                                <strong>Warning:</strong> This will remove all admin privileges and set the user to "Member" role.
                            </p>
                            
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-user-times"></i> Remove Admin Privileges
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>