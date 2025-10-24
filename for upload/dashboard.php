<?php
/**
 * ============================================
 * UNIFIED CENTRAL DASHBOARD - RBAC INTEGRATED
 * Role-Based Adaptive Dashboard with Theme Support
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/scope_helpers.php';
require_once __DIR__ . '/includes/dashboard_stats.php';
// ✅ ADD RBAC SUPPORT
require_once __DIR__ . '/includes/rbac.php';

start_secure_session();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ✅ CHECK FOR IMPERSONATION
if (isset($_SESSION['impersonating']) && isset($_SESSION['original_user_id'])) {
    $is_impersonating = true;
    $original_user_id = $_SESSION['original_user_id'];
} else {
    $is_impersonating = false;
}

// Handle theme change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $allowed_themes = ['light', 'dark', 'ocean', 'forest'];
    if (in_array($_POST['theme'], $allowed_themes)) {
        $_SESSION['theme'] = $_POST['theme'];
        // Optionally save to database
        $user_id = $_SESSION['user_id'];
        $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?")->execute([$_POST['theme'], $user_id]);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Get user details
$user_id = $_SESSION['user_id'];
$role_level = $_SESSION['role_level'] ?? 'member';

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
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get theme preference
$theme = $_SESSION['theme'] ?? $user['theme_preference'] ?? 'light';
$_SESSION['theme'] = $theme;

if (empty($role_level) && !empty($user['role_level'])) {
    $role_level = $user['role_level'];
    $_SESSION['role_level'] = $role_level;
}

$stats = get_dashboard_stats();
$role_class = "role-{$role_level}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Church Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/list.css" rel="stylesheet">
    <link href="css/themes.css" rel="stylesheet">
    <link href="css/role-colors.css" rel="stylesheet">
    <link href="css/footer-styles.css" rel="stylesheet">
    <style>
        /* ✅ IMPERSONATION BANNER STYLES */
        .impersonation-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 12px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-bottom: 3px solid #c92a2a;
        }
        .impersonation-banner .container-fluid {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .impersonation-banner strong {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .impersonation-banner .btn-stop-impersonate {
            background: white;
            color: #c92a2a;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 25px;
            border: none;
            transition: all 0.3s ease;
        }
        .impersonation-banner .btn-stop-impersonate:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        body.impersonating {
            padding-top: 60px;
        }
        body.impersonating .sidebar {
            top: 60px;
            height: calc(100vh - 60px);
        }
        body.impersonating .main-content {
            margin-top: 60px;
        }
    </style>
</head>
<body data-theme="<?php echo htmlspecialchars($theme); ?>" <?php echo $is_impersonating ? 'class="impersonating"' : ''; ?>>
    
    <?php if ($is_impersonating): ?>
    <!-- ✅ IMPERSONATION WARNING BANNER -->
    <div class="impersonation-banner">
        <div class="container-fluid">
            <div>
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>⚠️ IMPERSONATION MODE ACTIVE</strong>
                <span class="ms-3">
                    You are viewing as: <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                    (<?php echo htmlspecialchars($user['email']); ?>)
                </span>
            </div>
            <div>
                <a href="modules/users/stop_impersonate.php" class="btn btn-stop-impersonate">
                    <i class="fas fa-times-circle me-2"></i>EXIT IMPERSONATION
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-info text-center <?php echo $role_class; ?>">
            <div class="user-avatar <?php echo $role_level; ?>">
                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
            </div>
            <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
            <small class="text-white-50"><?php echo htmlspecialchars($user['email']); ?></small>
            <div class="mt-2">
                <span class="badge badge-<?php echo $role_level; ?>">
                    <?php echo get_role_display_name($role_level); ?>
                </span>
            </div>
            <small class="text-white-50 d-block mt-1">
                <i class="fas fa-map-marker-alt"></i> <?php echo get_scope_display($user); ?>
            </small>
        </div>

        <nav class="nav flex-column p-3">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <!-- ✅ RBAC-PROTECTED MENU ITEMS -->
            <?php if (can_view($_SESSION, 'province')): ?>
                <a class="nav-link" href="manage-provinces.php">
                    <i class="fas fa-globe"></i> Manage Provinces
                </a>
            <?php endif; ?>

            <?php if (can_view($_SESSION, 'diocese')): ?>
                <a class="nav-link" href="manage-dioceses.php">
                    <i class="fas fa-building"></i> Manage Dioceses
                </a>
            <?php endif; ?>

            <?php if (can_view($_SESSION, 'archdeaconry')): ?>
                <a class="nav-link" href="manage-archdeaconries.php">
                    <i class="fas fa-layer-group"></i> Manage Archdeaconries
                </a>
            <?php endif; ?>

            <?php if (can_view($_SESSION, 'deanery')): ?>
                <a class="nav-link" href="manage-deaneries.php">
                    <i class="fas fa-sitemap"></i> Manage Deaneries
                </a>
            <?php endif; ?>

            <?php if (can_view($_SESSION, 'parish')): ?>
                <a class="nav-link" href="manage-parishes.php">
                    <i class="fas fa-church"></i> Manage Parishes
                </a>
            <?php endif; ?>

            <a class="nav-link" href="./modules/users/list.php">
                <i class="fas fa-users"></i> <?php echo ($role_level == 'member') ? 'My Family' : 'Manage Users'; ?>
            </a>

            <?php if (can_view($_SESSION, 'reports')): ?>
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            <?php endif; ?>

            <?php if (can_view($_SESSION, 'ministry')): ?>
                <a class="nav-link" href="ministries.php">
                    <i class="fas fa-hands-helping"></i> Ministries
                </a>
                <a class="nav-link" href="events.php">
                    <i class="fas fa-calendar"></i> Events
                </a>
            <?php endif; ?>

            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> My Profile
            </a>
            
            <?php if (can_view($_SESSION, 'settings')): ?>
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            <?php endif; ?>
            
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
            
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header <?php echo $role_class; ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Dashboard Overview</h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                </div>
                <div>
                    <span class="text-muted">
                        <i class="fas fa-clock"></i> <?php echo date('l, F j, Y'); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Scope Information -->
        <?php if ($role_level == 'diocese_admin'): ?>
            <div class="scope-info <?php echo $role_class; ?>">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Your Scope:</strong> You are managing <strong><?php echo htmlspecialchars($user['diocese_name']); ?></strong> 
                <small class="d-block mt-1 opacity-75">
                    (1 of 38 dioceses in the Anglican Church of Kenya)
                </small>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <?php if (in_array($role_level, ['super_admin', 'national_admin'])): ?>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_provinces'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Provinces</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-green">
                            <i class="fas fa-building"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_dioceses'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Dioceses</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_archdeaconries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Archdeaconries</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_deaneries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Deaneries</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Parishes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-red">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            
            <?php elseif ($role_level == 'diocese_admin'): ?>
                <div class="col-md-3">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_archdeaconries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Archdeaconries</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_deaneries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Deaneries</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Parishes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            
            <?php elseif ($role_level == 'archdeaconry_admin'): ?>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_deaneries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Deaneries</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Parishes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            
            <?php elseif ($role_level == 'deanery_admin'): ?>
                <div class="col-md-6">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Parishes</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            
            <?php elseif ($role_level == 'parish_admin'): ?>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_members'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Members</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-green">
                            <i class="fas fa-home"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_families'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Families</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h4><?php echo number_format($stats['total_ministries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Ministries</p>
                    </div>
                </div>
            
            <?php else: // member ?>
                <div class="col-md-6">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-green">
                            <i class="fas fa-home"></i>
                        </div>
                        <h4><?php echo number_format($stats['family_members'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">Family Members</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card <?php echo $role_class; ?>">
                        <div class="stat-icon icon-purple">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h4><?php echo number_format($stats['my_ministries'] ?? 0); ?></h4>
                        <p class="text-muted mb-0">My Ministries</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="mt-4">
            <h5 class="mb-3">Quick Actions</h5>
            <div class="row">
                <!-- ✅ RBAC-PROTECTED QUICK ACTIONS -->
                <?php if (can_create($_SESSION, 'user')): ?>
                    <div class="col-md-3">
                        <a href="modules/users/create_user.php" class="quick-action-card <?php echo $role_class; ?>">
                            <i class="fas fa-user-plus text-primary"></i>
                            <p class="mb-0 mt-2">Add New User</p>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if (can_view($_SESSION, 'reports')): ?>
                    <div class="col-md-3">
                        <a href="reports.php" class="quick-action-card <?php echo $role_class; ?>">
                            <i class="fas fa-file-alt text-success"></i>
                            <p class="mb-0 mt-2">View Reports</p>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-3">
                    <a href="profile.php" class="quick-action-card <?php echo $role_class; ?>">
                        <i class="fas fa-user-edit text-info"></i>
                        <p class="mb-0 mt-2">Update Profile</p>
                    </a>
                </div>
                
                <?php if ($role_level == 'member'): ?>
                    <div class="col-md-3">
                        <a href="my-ministries.php" class="quick-action-card <?php echo $role_class; ?>">
                            <i class="fas fa-hands-helping text-warning"></i>
                            <p class="mb-0 mt-2">My Ministries</p>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="my-family.php" class="quick-action-card <?php echo $role_class; ?>">
                            <i class="fas fa-home text-danger"></i>
                            <p class="mb-0 mt-2">My Family</p>
                        </a>
                    </div>
                <?php else: ?>
                    <?php if (can_view($_SESSION, 'settings')): ?>
                    <div class="col-md-3">
                        <a href="settings.php" class="quick-action-card <?php echo $role_class; ?>">
                            <i class="fas fa-cog text-secondary"></i>
                            <p class="mb-0 mt-2">Settings</p>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>