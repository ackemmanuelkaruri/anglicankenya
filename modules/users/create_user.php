<?php
/**
 * Create User Module
 */

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/scope_helpers.php';
require_once '../../includes/rbac.php';

start_secure_session();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

 $role_level = $_SESSION['role_level'] ?? 'member';

// Check permissions
if (!can_edit($_SESSION, 'user')) {
    header('Location: ../../access_denied.php');
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get available parishes for dropdown
 $allowed_parishes = allowedParishIds($_SESSION);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_level = $_POST['role_level'];
    $parish_id = !empty($_POST['parish_id']) ? (int)$_POST['parish_id'] : null;
    
    // Validate role assignment
    if (!can_assign_role($_SESSION['role_level'], $role_level)) {
        $_SESSION['error'] = "You don't have permission to assign this role";
        header('Location: create.php');
        exit;
    }
    
    // Validate email uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email already exists";
        header('Location: create.php');
        exit;
    }
    
    // Validate username uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Username already exists";
        header('Location: create.php');
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Set parish/diocese/etc. based on role
    $user_data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'username' => $username,
        'password' => $hashed_password,
        'role_level' => $role_level,
        'account_status' => 'pending'
    ];
    
    // Set hierarchy based on current user and role
    if ($role_level === 'parish_admin' && $parish_id) {
        $user_data['parish_id'] = $parish_id;
        
        // Get deanery, archdeaconry, diocese, province from parish
        $stmt = $pdo->prepare("
            SELECT d.deanery_id, a.archdeaconry_id, di.diocese_id, pr.province_id 
            FROM parishes p
            LEFT JOIN deaneries d ON p.deanery_id = d.deanery_id
            LEFT JOIN archdeaconries a ON d.archdeaconry_id = a.archdeaconry_id
            LEFT JOIN dioceses di ON a.diocese_id = di.diocese_id
            LEFT JOIN provinces pr ON di.province_id = pr.province_id
            WHERE p.parish_id = ?
        ");
        $stmt->execute([$parish_id]);
        $hierarchy = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hierarchy) {
            $user_data['deanery_id'] = $hierarchy['deanery_id'];
            $user_data['archdeaconry_id'] = $hierarchy['archdeaconry_id'];
            $user_data['diocese_id'] = $hierarchy['diocese_id'];
            $user_data['province_id'] = $hierarchy['province_id'];
        }
    } elseif ($role_level === 'deanery_admin' && isset($_SESSION['deanery_id'])) {
        $user_data['deanery_id'] = $_SESSION['deanery_id'];
        $user_data['archdeaconry_id'] = $_SESSION['archdeaconry_id'];
        $user_data['diocese_id'] = $_SESSION['diocese_id'];
        $user_data['province_id'] = $_SESSION['province_id'];
    } elseif ($role_level === 'archdeaconry_admin' && isset($_SESSION['archdeaconry_id'])) {
        $user_data['archdeaconry_id'] = $_SESSION['archdeaconry_id'];
        $user_data['diocese_id'] = $_SESSION['diocese_id'];
        $user_data['province_id'] = $_SESSION['province_id'];
    } elseif ($role_level === 'diocese_admin' && isset($_SESSION['diocese_id'])) {
        $user_data['diocese_id'] = $_SESSION['diocese_id'];
        $user_data['province_id'] = $_SESSION['province_id'];
    } elseif ($role_level === 'national_admin' && isset($_SESSION['province_id'])) {
        $user_data['province_id'] = $_SESSION['province_id'];
    }
    
    // Insert user
    try {
        $columns = implode(', ', array_keys($user_data));
        $placeholders = implode(', ', array_fill(0, count($user_data), '?'));
        
        $stmt = $pdo->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");
        $stmt->execute(array_values($user_data));
        
        $user_id = $pdo->lastInsertId();
        
        // Log action
        log_activity('CREATE_USER', 'users', $user_id);
        
        $_SESSION['success'] = "User created successfully";
        header('Location: list.php');
        exit;
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        $_SESSION['error'] = "Failed to create user: " . $e->getMessage();
    }
}

 $page_title = "Create User";

// Role information
 $role_info = [
    'super_admin' => ['name' => 'Super Admin', 'icon' => 'fa-crown', 'description' => 'Full system access'],
    'national_admin' => ['name' => 'National Admin', 'icon' => 'fa-flag', 'description' => 'National-level operations'],
    'diocese_admin' => ['name' => 'Diocese Admin', 'icon' => 'fa-building', 'description' => 'Diocese-level operations'],
    'archdeaconry_admin' => ['name' => 'Archdeaconry Admin', 'icon' => 'fa-layer-group', 'description' => 'Archdeaconry-level operations'],
    'deanery_admin' => ['name' => 'Deanery Admin', 'icon' => 'fa-sitemap', 'description' => 'Deanery-level operations'],
        'parish_admin' => ['name' => 'Parish Admin', 'icon' => 'fa-church', 'description' => 'Parish-level operations'],
    'member' => ['name' => 'Member', 'icon' => 'fa-user', 'description' => 'Standard member access']
];
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
    <link rel="stylesheet" href="../../css/form.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            <?php echo htmlspecialchars($page_title); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="createUserForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">
                                    Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role_level" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role_level" name="role_level" required>
                                    <option value="">Select Role</option>
                                    <?php
                                    $roles = [
                                        'member' => 'Member',
                                        'parish_admin' => 'Parish Admin',
                                        'deanery_admin' => 'Deanery Admin',
                                        'archdeaconry_admin' => 'Archdeaconry Admin',
                                        'diocese_admin' => 'Diocese Admin',
                                        'national_admin' => 'National Admin'
                                    ];
                                    
                                    foreach ($roles as $role_key => $role_name) {
                                        if (can_assign_role($_SESSION['role_level'], $role_key)) {
                                            $selected = isset($_POST['role_level']) && $_POST['role_level'] === $role_key ? 'selected' : '';
                                            echo "<option value='$role_key' $selected>$role_name</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="parishField" style="display: none;">
                                <label for="parish_id" class="form-label">Parish</label>
                                <select class="form-select" id="parish_id" name="parish_id">
                                    <option value="">Select Parish</option>
                                    <?php
                                    if (!empty($allowed_parishes)) {
                                        $placeholders = implode(',', array_fill(0, count($allowed_parishes), '?'));
                                        $stmt = $pdo->prepare("SELECT parish_id, parish_name FROM parishes WHERE parish_id IN ($placeholders) ORDER BY parish_name");
                                        $stmt->execute($allowed_parishes);
                                        while ($parish = $stmt->fetch()) {
                                            $selected = isset($_POST['parish_id']) && $_POST['parish_id'] == $parish['parish_id'] ? 'selected' : '';
                                            echo "<option value='{$parish['parish_id']}' $selected>" . htmlspecialchars($parish['parish_name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="list.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    
    <script>
        $(document).ready(function() {
            // Show/hide parish field based on role selection
            $('#role_level').change(function() {
                const selectedRole = $(this).val();
                if (selectedRole === 'parish_admin') {
                    $('#parishField').slideDown();
                    $('#parish_id').prop('required', true);
                } else {
                    $('#parishField').slideUp();
                    $('#parish_id').prop('required', false).val('');
                }
            });
            
            // Password strength checker
            $('#password').keyup(function() {
                const password = $(this).val();
                const result = zxcvbn(password);
                const strength = result.score;
                
                let strengthText = '';
                let strengthClass = '';
                
                switch(strength) {
                    case 0:
                        strengthText = 'Very Weak';
                        strengthClass = 'bg-danger';
                        break;
                    case 1:
                        strengthText = 'Weak';
                        strengthClass = 'bg-danger';
                        break;
                    case 2:
                        strengthText = 'Fair';
                        strengthClass = 'bg-warning';
                        break;
                    case 3:
                        strengthText = 'Good';
                        strengthClass = 'bg-info';
                        break;
                    case 4:
                        strengthText = 'Strong';
                        strengthClass = 'bg-success';
                        break;
                }
                
                $('#passwordStrength')
                    .removeClass('bg-danger bg-warning bg-info bg-success')
                    .addClass(strengthClass)
                    .css('width', (strength * 25) + '%')
                    .attr('aria-valuenow', strength * 25)
                    .text(strengthText);
            });
            
            // Form validation
            $('#createUserForm').on('submit', function(e) {
                e.preventDefault();
                
                const password = $('#password').val();
                const result = zxcvbn(password);
                
                if (result.score < 2) {
                    alert('Password is too weak. Please choose a stronger password.');
                    return false;
                }
                
                this.submit();
            });
        });
    </script>
</body>
</html>