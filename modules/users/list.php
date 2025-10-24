<?php
/**
 * ============================================
 * ENHANCED USERS LIST MODULE - COMPLETE OVERHAUL
 * Features: Advanced Filters, Server-side Pagination, 
 * Bulk Actions, Performance Optimization, Activity Logging
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/scope_helpers.php';

// Include RBAC functions
require_once '../../includes/rbac.php';

start_secure_session();

// ============================================
// HELPER FUNCTIONS - MUST BE DEFINED BEFORE USE
// ============================================

// Helper function to validate status
if (!function_exists('validate_status')) {
    function validate_status($status) {
        $valid_statuses = ['all', 'active', 'pending', 'suspended', 'inactive'];
        return in_array($status, $valid_statuses) ? $status : 'all';
    }
}

// Helper function to validate role level (local override for filters)
if (!function_exists('validate_role_level_filter')) {
    function validate_role_level_filter($role) {
        $valid_roles = ['all', 'super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin', 'member'];
        return in_array($role, $valid_roles) ? $role : 'all';
    }
}

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

 $role_level = $_SESSION['role_level'] ?? 'member';
 $current_user_id = $_SESSION['user_id'];

if ($role_level === 'member') {
    header('Location: ../../dashboard.php');
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

 $scope = get_user_scope();

// ============================================
// ADVANCED FILTER HANDLING
// ============================================
 $filters = [
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
    'status' => isset($_GET['status']) ? trim($_GET['status']) : 'all', // FIXED: Default to 'all'
    'role' => isset($_GET['role']) ? trim($_GET['role']) : 'all',     // FIXED: Default to 'all'
    'hierarchy_level' => isset($_GET['hierarchy_level']) ? trim($_GET['hierarchy_level']) : '',
    'hierarchy_id' => isset($_GET['hierarchy_id']) ? (int)$_GET['hierarchy_id'] : 0,
];

// Sanitize filters
 $filters['search'] = htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8');
 $filters['status'] = validate_status($filters['status']);
 $filters['role'] = validate_role_level_filter($filters['role']);

// ============================================
// PAGINATION SETUP
// ============================================
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $per_page = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 25;
 $offset = ($page - 1) * $per_page;

// ============================================
// BUILD OPTIMIZED QUERY WITH FILTERS
// ============================================
 $sql = "SELECT u.*, 
               p.province_name,
               d.diocese_name,
               a.archdeaconry_name,
               dn.deanery_name,
               pr.parish_name
        FROM users u
        LEFT JOIN provinces p ON u.province_id = p.province_id
        LEFT JOIN dioceses d ON u.diocese_id = d.diocese_id
        LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
        LEFT JOIN deaneries dn ON u.deanery_id = dn.deanery_id
        LEFT JOIN parishes pr ON u.parish_id = pr.parish_id
        WHERE 1=1";

 $params = [];
 $where_conditions = [];

// Scope filtering - FIXED: Added national_admin condition
if ($role_level === 'parish_admin') {
    $where_conditions[] = "u.parish_id = ?";
    $params[] = $_SESSION['parish_id'];
} elseif ($role_level === 'deanery_admin') {
    $where_conditions[] = "u.deanery_id = ?";
    $params[] = $_SESSION['deanery_id'];
} elseif ($role_level === 'archdeaconry_admin') {
    $where_conditions[] = "u.archdeaconry_id = ?";
    $params[] = $_SESSION['archdeaconry_id'];
} elseif ($role_level === 'diocese_admin') {
    $where_conditions[] = "u.diocese_id = ?";
    $params[] = $_SESSION['diocese_id'];
} 
// No filtering for super_admin and national_admin
// They can see all users

// Status filter
if (!empty($filters['status']) && $filters['status'] !== 'all') {
    $where_conditions[] = "u.account_status = ?";
    $params[] = $filters['status'];
}

// Role filter
if (!empty($filters['role']) && $filters['role'] !== 'all') {
    $where_conditions[] = "u.role_level = ?";
    $params[] = $filters['role'];
}
// Hierarchy filter
if (!empty($filters['hierarchy_level']) && $filters['hierarchy_id'] > 0) {
    switch ($filters['hierarchy_level']) {
        case 'province':
            $where_conditions[] = "u.province_id = ?";
            $params[] = $filters['hierarchy_id'];
            break;
        case 'diocese':
            $where_conditions[] = "u.diocese_id = ?";
            $params[] = $filters['hierarchy_id'];
            break;
        case 'archdeaconry':
            $where_conditions[] = "u.archdeaconry_id = ?";
            $params[] = $filters['hierarchy_id'];
            break;
        case 'deanery':
            $where_conditions[] = "u.deanery_id = ?";
            $params[] = $filters['hierarchy_id'];
            break;
        case 'parish':
            $where_conditions[] = "u.parish_id = ?";
            $params[] = $filters['hierarchy_id'];
            break;
    }
}

// Search filter
if (!empty($filters['search'])) {
    $search_term = '%' . $filters['search'] . '%';
    $where_conditions[] = "(
        LOWER(u.first_name) LIKE LOWER(?) OR 
        LOWER(u.last_name) LIKE LOWER(?) OR 
        LOWER(u.email) LIKE LOWER(?) OR 
        LOWER(u.username) LIKE LOWER(?)
    )";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Add WHERE conditions
if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

// Count total records for pagination (before LIMIT)
 $count_sql = "SELECT COUNT(*) " . substr($sql, strpos($sql, "FROM"));
try {
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting users: " . $e->getMessage());
    $total_records = 0;
}

 $total_pages = ceil($total_records / $per_page);

// Add pagination
 $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
 $params[] = $per_page;
 $params[] = $offset;

// Execute main query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $error_message = "Failed to load users. Please try again.";
}

// ============================================
// OPTIMIZED STATISTICS (Direct DB Query)
// ============================================
try {
    // Get role statistics
    $stats_sql = "SELECT role_level, COUNT(*) as count FROM users WHERE 1=1";
    $stats_params = [];
    
    if ($role_level === 'parish_admin') {
        $stats_sql .= " AND parish_id = ?";
        $stats_params[] = $_SESSION['parish_id'];
    } elseif ($role_level === 'deanery_admin') {
        $stats_sql .= " AND deanery_id = ?";
        $stats_params[] = $_SESSION['deanery_id'];
    } elseif ($role_level === 'archdeaconry_admin') {
        $stats_sql .= " AND archdeaconry_id = ?";
        $stats_params[] = $_SESSION['archdeaconry_id'];
    } elseif ($role_level === 'diocese_admin') {
        $stats_sql .= " AND diocese_id = ?";
        $stats_params[] = $_SESSION['diocese_id'];
    }
    // No filtering for super_admin and national_admin
    
    $stats_sql .= " GROUP BY role_level";
    
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute($stats_params);
    $role_stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get status statistics
    $status_sql = str_replace("role_level", "account_status", str_replace("GROUP BY role_level", "GROUP BY account_status", $stats_sql));
    $status_stmt = $pdo->prepare($status_sql);
    $status_stmt->execute($stats_params);
    $status_stats = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
    $role_stats = [];
    $status_stats = [];
}

// Page title
 $page_title = "Manage Users";
if ($role_level === 'parish_admin') {
    $page_title = "Parish Members";
} elseif ($role_level === 'deanery_admin') {
    $page_title = "Deanery Users";
} elseif ($role_level === 'archdeaconry_admin') {
    $page_title = "Archdeaconry Users";
} elseif ($role_level === 'diocese_admin') {
    $page_title = "Diocese Users";
}

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

// Status information
 $status_info = [
    'active' => 'User account is active and can access the system',
    'pending' => 'User registration is pending approval',
    'suspended' => 'User account has been suspended',
    'inactive' => 'User account is inactive'
];

// Get hierarchy options for filters
 $hierarchy_options = [];
if ($role_level === 'super_admin') {
    try {
        $hierarchy_options['provinces'] = $pdo->query("SELECT province_id, province_name FROM provinces ORDER BY province_name")->fetchAll(PDO::FETCH_ASSOC);
        $hierarchy_options['dioceses'] = $pdo->query("SELECT diocese_id, diocese_name FROM dioceses ORDER BY diocese_name")->fetchAll(PDO::FETCH_ASSOC);
        $hierarchy_options['archdeaconries'] = $pdo->query("SELECT archdeaconry_id, archdeaconry_name FROM archdeaconries ORDER BY archdeaconry_name")->fetchAll(PDO::FETCH_ASSOC);
        $hierarchy_options['deaneries'] = $pdo->query("SELECT deanery_id, deanery_name FROM deaneries ORDER BY deanery_name")->fetchAll(PDO::FETCH_ASSOC);
        $hierarchy_options['parishes'] = $pdo->query("SELECT parish_id, parish_name FROM parishes ORDER BY parish_name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading hierarchy options: " . $e->getMessage());
    }
}

// Build filter query string for pagination links
function build_filter_query($exclude = []) {
    global $filters, $per_page;
    $params = [];
    
    if (!in_array('search', $exclude) && !empty($filters['search'])) {
        $params['search'] = $filters['search'];
    }
    if (!in_array('status', $exclude) && !empty($filters['status']) && $filters['status'] !== 'all') {
        $params['status'] = $filters['status'];
    }
    if (!in_array('role', $exclude) && !empty($filters['role']) && $filters['role'] !== 'all') {
        $params['role'] = $filters['role'];
    }
    if (!in_array('hierarchy_level', $exclude) && !empty($filters['hierarchy_level'])) {
        $params['hierarchy_level'] = $filters['hierarchy_level'];
        $params['hierarchy_id'] = $filters['hierarchy_id'];
    }
    if (!in_array('per_page', $exclude)) {
        $params['per_page'] = $per_page;
    }
    
    return !empty($params) ? '&' . http_build_query($params) : '';
}

 $active_filters_count = 0;
if (!empty($filters['search'])) $active_filters_count++;
if (!empty($filters['status']) && $filters['status'] !== 'all') $active_filters_count++;
if (!empty($filters['role']) && $filters['role'] !== 'all') $active_filters_count++;
if (!empty($filters['hierarchy_level'])) $active_filters_count++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Church Management System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/list.css">
    
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border spinner-border-lg text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Processing...</p>
        </div>
    </div>

    <div class="main-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../dashboard.php" title="Return to main dashboard" data-bs-toggle="tooltip">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
                </li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-users-cog me-2" title="User Management Module" data-bs-toggle="tooltip"></i>
                        <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <?php 
                        if (!empty($scope['parish_name'])) {
                            echo "Parish: " . htmlspecialchars($scope['parish_name'], ENT_QUOTES, 'UTF-8');
                        } elseif (!empty($scope['deanery_name'])) {
                            echo "Deanery: " . htmlspecialchars($scope['deanery_name'], ENT_QUOTES, 'UTF-8');
                        } elseif (!empty($scope['archdeaconry_name'])) {
                            echo "Archdeaconry: " . htmlspecialchars($scope['archdeaconry_name'], ENT_QUOTES, 'UTF-8');
                        } elseif (!empty($scope['diocese_name'])) {
                            echo "Diocese: " . htmlspecialchars($scope['diocese_name'], ENT_QUOTES, 'UTF-8');
                        } else {
                            echo "All Users Across Anglican Church of Kenya";
                        }
                        ?>
                    </p>
                </div>
                <div>
                    <a href="create_user.php" class="btn btn-primary" title="Create a new user account" data-bs-toggle="tooltip">
                        <i class="fas fa-plus me-2"></i> Add New User
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" title="Total users in your scope" data-bs-toggle="tooltip">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Total Users</h5>
                            <h3 class="mb-0"><?php echo number_format($total_records); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" title="Active user accounts" data-bs-toggle="tooltip">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-check fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Active</h5>
                            <h3 class="mb-0"><?php echo number_format($status_stats['active'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" title="Pending approvals" data-bs-toggle="tooltip">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Pending</h5>
                            <h3 class="mb-0"><?php echo number_format($status_stats['pending'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" title="Suspended accounts" data-bs-toggle="tooltip">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-times fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Suspended</h5>
                            <h3 class="mb-0"><?php echo number_format($status_stats['suspended'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADVANCED FILTER PANEL -->
        <div class="filter-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Advanced Filters
                    <?php if ($active_filters_count > 0): ?>
                        <span class="badge bg-primary"><?php echo $active_filters_count; ?> active</span>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleFilters">
                    <i class="fas fa-chevron-up" id="filterToggleIcon"></i>
                </button>
            </div>
            
            <div id="filterContent">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3">
                        <!-- Search -->
                        <div class="col-md-3">
                            <div class="filter-section">
                                <label class="filter-label">
                                    <i class="fas fa-search me-1"></i>Search
                                </label>
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control" 
                                    placeholder="Name, email, username..." 
                                    value="<?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>"
                                    title="Search by name, email, or username"
                                    data-bs-toggle="tooltip"
                                >
                            </div>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <div class="filter-section">
                                <label class="filter-label">
                                    <i class="fas fa-toggle-on me-1"></i>Status
                                </label>
                                <select name="status" class="form-select" title="Filter by account status" data-bs-toggle="tooltip">
                                    <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active (<?php echo $status_stats['active'] ?? 0; ?>)</option>
                                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending (<?php echo $status_stats['pending'] ?? 0; ?>)</option>
                                    <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended (<?php echo $status_stats['suspended'] ?? 0; ?>)</option>
                                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (<?php echo $status_stats['inactive'] ?? 0; ?>)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Role Filter -->
                        <div class="col-md-3">
                            <div class="filter-section">
                                <label class="filter-label">
                                    <i class="fas fa-user-tag me-1"></i>Role Level
                                </label>
                                <select name="role" class="form-select" title="Filter by role level" data-bs-toggle="tooltip">
                                    <option value="all" <?php echo $filters['role'] === 'all' ? 'selected' : ''; ?>>All Roles</option>
                                    <?php foreach ($role_info as $role_key => $info): ?>
                                        <option value="<?php echo $role_key; ?>" <?php echo $filters['role'] === $role_key ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                            (<?php echo $role_stats[$role_key] ?? 0; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Hierarchy Level Filter (Super Admin only) -->
                        <?php if ($role_level === 'super_admin' && !empty($hierarchy_options)): ?>
                        <div class="col-md-2">
                            <div class="filter-section">
                                <label class="filter-label">
                                    <i class="fas fa-sitemap me-1"></i>Level
                                </label>
                                <select name="hierarchy_level" id="hierarchyLevel" class="form-select" title="Filter by hierarchy level" data-bs-toggle="tooltip">
                                    <option value="">All Levels</option>
                                    <option value="province" <?php echo $filters['hierarchy_level'] === 'province' ? 'selected' : ''; ?>>Province</option>
                                    <option value="diocese" <?php echo $filters['hierarchy_level'] === 'diocese' ? 'selected' : ''; ?>>Diocese</option>
                                    <option value="archdeaconry" <?php echo $filters['hierarchy_level'] === 'archdeaconry' ? 'selected' : ''; ?>>Archdeaconry</option>
                                    <option value="deanery" <?php echo $filters['hierarchy_level'] === 'deanery' ? 'selected' : ''; ?>>Deanery</option>
                                    <option value="parish" <?php echo $filters['hierarchy_level'] === 'parish' ? 'selected' : ''; ?>>Parish</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Hierarchy ID Filter (Dynamic based on level) -->
                        <div class="col-md-2">
                            <div class="filter-section">
                                <label class="filter-label">
                                    <i class="fas fa-building me-1"></i>Location
                                </label>
                                <select name="hierarchy_id" id="hierarchyId" class="form-select" title="Select specific location" data-bs-toggle="tooltip">
                                    <option value="">Select...</option>
                                    <?php if ($filters['hierarchy_level'] === 'province' && isset($hierarchy_options['provinces'])): ?>
                                        <?php foreach ($hierarchy_options['provinces'] as $item): ?>
                                            <option value="<?php echo $item['province_id']; ?>" <?php echo $filters['hierarchy_id'] == $item['province_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item['province_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php elseif ($filters['hierarchy_level'] === 'diocese' && isset($hierarchy_options['dioceses'])): ?>
                                        <?php foreach ($hierarchy_options['dioceses'] as $item): ?>
                                            <option value="<?php echo $item['diocese_id']; ?>" <?php echo $filters['hierarchy_id'] == $item['diocese_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item['diocese_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php elseif ($filters['hierarchy_level'] === 'archdeaconry' && isset($hierarchy_options['archdeaconries'])): ?>
                                        <?php foreach ($hierarchy_options['archdeaconries'] as $item): ?>
                                            <option value="<?php echo $item['archdeaconry_id']; ?>" <?php echo $filters['hierarchy_id'] == $item['archdeaconry_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item['archdeaconry_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php elseif ($filters['hierarchy_level'] === 'deanery' && isset($hierarchy_options['deaneries'])): ?>
                                        <?php foreach ($hierarchy_options['deaneries'] as $item): ?>
                                            <option value="<?php echo $item['deanery_id']; ?>" <?php echo $filters['hierarchy_id'] == $item['deanery_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item['deanery_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php elseif ($filters['hierarchy_level'] === 'parish' && isset($hierarchy_options['parishes'])): ?>
                                        <?php foreach ($hierarchy_options['parishes'] as $item): ?>
                                            <option value="<?php echo $item['parish_id']; ?>" <?php echo $filters['hierarchy_id'] == $item['parish_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item['parish_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Per Page -->
                        <div class="col-md-2">
                            <div class="filter-section">
                                <label class="filter-label">
                                    <i class="fas fa-list-ol me-1"></i>Per Page
                                </label>
                                <select name="per_page" class="form-select" title="Records per page" data-bs-toggle="tooltip">
                                    <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                            <!-- FIXED: Reset All button now clears all filters -->
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset All
                            </a>
                            <button type="button" class="btn btn-outline-info" id="exportBtn">
                                <i class="fas fa-download me-2"></i>Export Results
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Active Filters Display -->
                <?php if ($active_filters_count > 0): ?>
                <div class="active-filters">
                    <strong class="me-2">Active Filters:</strong>
                    
                    <?php if (!empty($filters['search'])): ?>
                    <span class="filter-badge">
                        <i class="fas fa-search"></i>
                        Search: <?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="remove-filter text-decoration-none">×</a>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($filters['status']) && $filters['status'] !== 'all'): ?>
                    <span class="filter-badge">
                        <i class="fas fa-toggle-on"></i>
                        Status: <?php echo ucfirst($filters['status']); ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'all'])); ?>" class="remove-filter text-decoration-none">×</a>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($filters['role']) && $filters['role'] !== 'all'): ?>
                    <span class="filter-badge">
                        <i class="fas fa-user-tag"></i>
                        Role: <?php echo $role_info[$filters['role']]['name'] ?? $filters['role']; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['role' => 'all'])); ?>" class="remove-filter text-decoration-none">×</a>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($filters['hierarchy_level'])): ?>
                    <span class="filter-badge">
                        <i class="fas fa-sitemap"></i>
                        Level: <?php echo ucfirst($filters['hierarchy_level']); ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['hierarchy_level' => '', 'hierarchy_id' => ''])); ?>" class="remove-filter text-decoration-none">×</a>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar" id="bulkActionsBar">
            <div>
                <strong id="selectedCount">0</strong> users selected
            </div>
            <div>
                <select class="form-select form-select-sm me-2" id="bulkAction" style="width: 200px; display: inline-block;">
                    <option value="">Choose action...</option>
                    <option value="activate">Activate Selected</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="delete">Delete Selected</option>
                    <option value="export">Export Selected</option>
                </select>
                <button type="button" class="btn btn-sm btn-primary" id="applyBulkAction">
                    <i class="fas fa-check me-1"></i>Apply
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelBulkAction">
                    Cancel
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Users List
                    </h5>
                    <small class="text-muted pagination-info">
                        Showing <?php echo number_format($offset + 1); ?> to 
                        <?php echo number_format(min($offset + $per_page, $total_records)); ?> of 
                        <?php echo number_format($total_records); ?> users
                    </small>
                </div>
                <div>
                    <label class="me-2">
                        <input type="checkbox" id="selectAll" class="form-check-input me-1">
                        Select All
                    </label>
                </div>
            </div>

            <?php if (empty($users)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo $active_filters_count > 0 ? 'No users found matching your filters.' : 'No users found in your scope.'; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                </th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Hierarchy</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $safe_user_id = (int)$user['id'];
                                $role = $user['role_level'];
                                $role_display = $role_info[$role]['name'] ?? 'Member';
                                $role_description = $role_info[$role]['description'] ?? '';
                            ?>
                            <tr class="role-<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>" data-user-id="<?php echo $safe_user_id; ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input user-checkbox" value="<?php echo $safe_user_id; ?>">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2 <?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                                             title="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                             data-bs-toggle="tooltip">
                                            <?php echo strtoupper(htmlspecialchars(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1), ENT_QUOTES, 'UTF-8')); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       title="Send email" data-bs-toggle="tooltip">
                                        <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                                          title="<?php echo htmlspecialchars($role_description, ENT_QUOTES, 'UTF-8'); ?>"
                                          data-bs-toggle="tooltip">
                                        <?php echo htmlspecialchars($role_display, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="hierarchy-breadcrumb" title="Full organizational path" data-bs-toggle="tooltip">
                                        <?php
                                        $hierarchy_parts = [];
                                        if ($user['province_name']) $hierarchy_parts[] = $user['province_name'];
                                        if ($user['diocese_name']) $hierarchy_parts[] = $user['diocese_name'];
                                        if ($user['archdeaconry_name']) $hierarchy_parts[] = $user['archdeaconry_name'];
                                        if ($user['deanery_name']) $hierarchy_parts[] = $user['deanery_name'];
                                        if ($user['parish_name']) $hierarchy_parts[] = $user['parish_name'];
                                        
                                        echo htmlspecialchars(implode(' › ', $hierarchy_parts), ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status = $user['account_status'];
                                    $status_class = '';
                                    switch($status) {
                                        case 'active': $status_class = 'badge-active'; break;
                                        case 'pending': $status_class = 'badge-pending'; break;
                                        case 'suspended': $status_class = 'badge-suspended'; break;
                                        default: $status_class = 'badge-inactive';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"
                                          title="<?php echo htmlspecialchars($status_info[$status] ?? 'User status', ENT_QUOTES, 'UTF-8'); ?>"
                                          data-bs-toggle="tooltip">
                                        <?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <small title="<?php echo htmlspecialchars(date('F j, Y \\a\\t g:i A', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8'); ?>"
                                           data-bs-toggle="tooltip">
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </small>
                                </td>
                                <td class="table-actions">
                                    <div class="btn-group btn-group-sm">
                                        <!-- VIEW BUTTON - Everyone can view -->
                                        <a href="view.php?id=<?php echo $safe_user_id; ?>" 
                                           class="btn btn-outline-primary"
                                           title="View user details"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- EDIT BUTTON - Role-based access -->
                                        <?php if (can_edit($_SESSION, 'user', $safe_user_id)): ?>
                                        <a href="edit.php?id=<?php echo $safe_user_id; ?>" 
                                           class="btn btn-outline-secondary"
                                           title="Edit user"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <!-- APPROVE BUTTON - For pending users -->
                                        <?php if ($user['account_status'] === 'pending' && can_edit($_SESSION, 'user', $safe_user_id)): ?>
                                        <a href="approve.php?id=<?php echo $safe_user_id; ?>" 
                                           class="btn btn-outline-success"
                                           title="Approve user"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <!-- IMPERSONATE BUTTON - Super Admin only -->
                                        <?php if (can_impersonate($_SESSION, $safe_user_id)): ?>
                                        <a href="impersonate.php?id=<?php echo $safe_user_id; ?>" 
                                           class="btn btn-outline-warning"
                                           title="Impersonate user"
                                           data-bs-toggle="tooltip"
                                           onclick="return confirm('Are you sure you want to impersonate this user? All actions will be logged.');">
                                            <i class="fas fa-user-secret"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <!-- ROLE ASSIGNMENT DROPDOWN - Admin users only -->
                                        <?php if (can_edit($_SESSION, 'user', $safe_user_id) && $safe_user_id != $current_user_id && $role_level !== 'member'): ?>
                                        <div class="role-dropdown">
                                            <button class="btn btn-outline-info dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    title="Assign role"
                                                    data-bs-toggle="tooltip">
                                                <i class="fas fa-user-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">
                                                    <i class="fas fa-shield-alt me-2"></i>Assign Role
                                                </h6></li>
                                                
                                                <?php if ($role_level === 'super_admin'): ?>
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="super_admin" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-crown me-2"></i>Super Admin
                                                </a></li>
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="national_admin" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-flag me-2"></i>National Admin
                                                </a></li>
                                                <?php endif; ?>
                                                
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="diocese_admin" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-building me-2"></i>Diocese Admin
                                                </a></li>
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="archdeaconry_admin" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-layer-group me-2"></i>Archdeaconry Admin
                                                </a></li>
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="deanery_admin" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-sitemap me-2"></i>Deanery Admin
                                                </a></li>
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="parish_admin" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-church me-2"></i>Parish Admin
                                                </a></li>
                                                <li><a class="dropdown-item role-change-link" href="#" 
                                                       data-role="member" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-user me-2"></i>Member
                                                </a></li>
                                            </ul>
                                        </div>
                                        
                                        <!-- STATUS CHANGE DROPDOWN - Admin users only -->
                                        <div class="status-dropdown">
                                            <button class="btn btn-outline-warning dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    title="Change status"
                                                    data-bs-toggle="tooltip">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">
                                                    <i class="fas fa-exchange-alt me-2"></i>Change Status
                                                </h6></li>
                                                <li><a class="dropdown-item status-change-link" href="#" 
                                                       data-status="active" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-check-circle me-2"></i>Set Active
                                                </a></li>
                                                <li><a class="dropdown-item status-change-link" href="#" 
                                                       data-status="pending" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-clock me-2"></i>Set Pending
                                                </a></li>
                                                <li><a class="dropdown-item status-change-link" href="#" 
                                                       data-status="suspended" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-ban me-2"></i>Suspend
                                                </a></li>
                                                <li><a class="dropdown-item status-change-link" href="#" 
                                                       data-status="inactive" 
                                                       data-user-id="<?php echo $safe_user_id; ?>"
                                                       data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-user-slash me-2"></i>Set Inactive
                                                </a></li>
                                            </ul>
                                        </div>
                                        
                                        <!-- DELETE BUTTON - Admin users only -->
                                        <a href="delete.php?id=<?php echo $safe_user_id; ?>" 
                                           class="btn btn-outline-danger confirm-delete" 
                                           title="Delete user"
                                           data-bs-toggle="tooltip"
                                           data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                           onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>? This action cannot be undone.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="pagination-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo build_filter_query(); ?>" aria-label="First">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo build_filter_query(); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        // Show limited page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                            $active_class = ($i == $page) ? 'active' : '';
                        ?>
                        <li class="page-item <?php echo $active_class; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo build_filter_query(); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        if ($end_page < $total_pages) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo build_filter_query(); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo build_filter_query(); ?>" aria-label="Last">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/enhanced_users.js"></script>


</body>
</html>