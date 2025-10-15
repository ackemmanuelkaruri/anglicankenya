<?php
/**
 * ============================================
 * USERS LIST MODULE
 * Shows all 6 levels of users with hierarchy information
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../db.php';
require_once '../../includes/security.php';
require_once '../../includes/scope_helpers.php';

start_secure_session();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

 $role_level = $_SESSION['role_level'] ?? 'member';

// Members can't manage other users
if ($role_level === 'member') {
    header('Location: ../../dashboard.php');
    exit;
}

// Get accessible users based on scope
 $users = get_accessible_users();

// Get current user scope for display
 $scope = get_user_scope();

// Page title based on role
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

// Handle search
 $search = $_GET['search'] ?? '';
if (!empty($search)) {
    $users = array_filter($users, function($user) use ($search) {
        $search_lower = strtolower($search);
        return (
            stripos($user['first_name'], $search) !== false ||
            stripos($user['last_name'], $search) !== false ||
            stripos($user['email'], $search) !== false ||
            stripos($user['username'], $search) !== false
        );
    });
}

// Get statistics
 $total_users = count($users);
 $active_users = count(array_filter($users, fn($u) => $u['account_status'] === 'active'));
 $pending_users = count(array_filter($users, fn($u) => $u['account_status'] === 'pending'));

// Count users by role level
 $role_counts = [];
foreach ($users as $user) {
    $role = $user['role_level'];
    if (!isset($role_counts[$role])) {
        $role_counts[$role] = 0;
    }
    $role_counts[$role]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Church Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../css/list.css">
</head>
<body>
    <div class="main-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><?php echo $page_title; ?></h2>
                    <p class="text-muted mb-0">
                        <?php 
                        if ($scope['diocese_name']) {
                            echo "Diocese: " . htmlspecialchars($scope['diocese_name']);
                        } elseif ($scope['archdeaconry_name']) {
                            echo "Archdeaconry: " . htmlspecialchars($scope['archdeaconry_name']);
                        } elseif ($scope['deanery_name']) {
                            echo "Deanery: " . htmlspecialchars($scope['deanery_name']);
                        } elseif ($scope['parish_name']) {
                            echo "Parish: " . htmlspecialchars($scope['parish_name']);
                        } else {
                            echo "All Users Across Anglican Church of Kenya";
                        }
                        ?>
                    </p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Add New User
                    </a>
                </div>
            </div>
        </div>

        <!-- Role Color Legend -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Role Level Legend</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="role-super_admin me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span>Super Admin</span>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="role-diocese_admin me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span>Diocese Admin</span>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="role-archdeaconry_admin me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span>Archdeaconry Admin</span>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="role-deanery_admin me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span>Deanery Admin</span>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="role-parish_admin me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span>Parish Admin</span>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="role-member me-2" style="width: 20px; height: 20px; border-radius: 3px;"></div>
                            <span>Member</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics by Role Level -->
        <div class="row mb-4">
            <?php if (isset($role_counts['super_admin'])): ?>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-crown fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Super Admin</h5>
                            <h3 class="mb-0"><?php echo number_format($role_counts['super_admin']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($role_counts['diocese_admin'])): ?>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-building fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Diocese Admin</h5>
                            <h3 class="mb-0"><?php echo number_format($role_counts['diocese_admin']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($role_counts['archdeaconry_admin'])): ?>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-layer-group fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Archdeaconry Admin</h5>
                            <h3 class="mb-0"><?php echo number_format($role_counts['archdeaconry_admin']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($role_counts['deanery_admin'])): ?>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-sitemap fa-2x text-info"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Deanery Admin</h5>
                            <h3 class="mb-0"><?php echo number_format($role_counts['deanery_admin']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($role_counts['parish_admin'])): ?>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-church fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Parish Admin</h5>
                            <h3 class="mb-0"><?php echo number_format($role_counts['parish_admin']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($role_counts['member'])): ?>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-users fa-2x text-secondary"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1">Members</h5>
                            <h3 class="mb-0"><?php echo number_format($role_counts['member']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Users Table -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">All Users</h5>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex">
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control" 
                            placeholder="Search users..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (empty($users)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No users found in your scope.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Hierarchy</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="role-<?php echo $user['role_level']; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2 <?php echo $user['role_level']; ?>">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role_level']; ?>">
                                        <?php 
                                        $role_names = [
                                            'super_admin' => 'Super Admin',
                                            'diocese_admin' => 'Diocese Admin',
                                            'archdeaconry_admin' => 'Archdeaconry Admin',
                                            'deanery_admin' => 'Deanery Admin',
                                            'parish_admin' => 'Parish Admin',
                                            'member' => 'Member'
                                        ];
                                        echo $role_names[$user['role_level']] ?? 'Member';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="hierarchy-breadcrumb">
                                        <?php if ($user['province_name']): ?>
                                            <?php echo htmlspecialchars($user['province_name']); ?>
                                            <span class="separator">></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['diocese_name']): ?>
                                            <?php echo htmlspecialchars($user['diocese_name']); ?>
                                            <span class="separator">></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['archdeaconry_name']): ?>
                                            <?php echo htmlspecialchars($user['archdeaconry_name']); ?>
                                            <span class="separator">></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['deanery_name']): ?>
                                            <?php echo htmlspecialchars($user['deanery_name']); ?>
                                            <span class="separator">></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['parish_name']): ?>
                                            <?php echo htmlspecialchars($user['parish_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($user['account_status']) {
                                        case 'active':
                                            $status_class = 'badge-active';
                                            break;
                                        case 'pending':
                                            $status_class = 'badge-pending';
                                            break;
                                        case 'suspended':
                                            $status_class = 'badge-suspended';
                                            break;
                                        default:
                                            $status_class = 'badge-inactive';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($user['account_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                                </td>
                                <td class="table-actions">
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-outline-secondary" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['account_status'] === 'pending'): ?>
                                        <a href="approve.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-outline-success" 
                                           title="Approve">
                                            <i class="fas fa-check"></i>
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
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[0, 'asc']]
            });
        });
    </script>
</body>
</html>