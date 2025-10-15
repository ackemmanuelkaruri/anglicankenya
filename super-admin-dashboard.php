<?php
/**
 * ============================================
 * SUPER ADMIN DASHBOARD
 * Phase 4: Multi-Church Management Interface
 * ============================================
 * 
 * Create new file: /admin/super-admin-dashboard.php
 */

require_once 'db.php';
require_once 'includes/security.php';

start_secure_session();
require_login();

// Only Super Admin and National Admin can access
if (!is_super_admin() && !is_national_admin()) {
    header('Location: /admin/admin-dashboard.php');
    exit;
}

// Get statistics
$stats = [];

try {
    // Total churches
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM organizations WHERE id > 1");
    $stats['total_churches'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active churches
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM organizations WHERE subscription_status = 'active' AND id > 1");
    $stats['active_churches'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total users across all churches
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE org_id > 1");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending approvals across all churches
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE account_status = 'pending'");
    $stats['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get recent churches
    $stmt = $pdo->query("
        SELECT 
            id, 
            org_name, 
            org_code, 
            city, 
            subscription_status,
            created_at,
            (SELECT COUNT(*) FROM users WHERE org_id = organizations.id) as user_count
        FROM organizations 
        WHERE id > 1
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_churches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

$user = get_user_details();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Church Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .icon-blue { background: #e3f2fd; color: #2196f3; }
        .icon-green { background: #e8f5e9; color: #4caf50; }
        .icon-orange { background: #fff3e0; color: #ff9800; }
        .icon-red { background: #ffebee; color: #f44336; }
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .badge-active { background: #4caf50; }
        .badge-suspended { background: #f44336; }
        .badge-expired { background: #9e9e9e; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3" style="width: 250px;">
            <div class="text-center mb-4">
                <h4 class="mb-1">CMS Admin</h4>
                <small><?php echo get_role_badge(); ?></small>
            </div>
            
            <div class="mb-4">
                <p class="mb-1"><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></p>
                <small class="text-white-50"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>

            <nav class="nav flex-column">
                <a class="nav-link active" href="super-admin-dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link" href="manage-churches.php">
                    <i class="fas fa-church me-2"></i> Manage Churches
                </a>
                <a class="nav-link" href="manage-admins.php">
                    <i class="fas fa-user-shield me-2"></i> Church Admins
                </a>
                <a class="nav-link" href="all-users.php">
                    <i class="fas fa-users me-2"></i> All Users
                </a>
                <a class="nav-link" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
                <a class="nav-link" href="system-settings.php">
                    <i class="fas fa-cog me-2"></i> System Settings
                </a>
                <a class="nav-link" href="audit-logs.php">
                    <i class="fas fa-history me-2"></i> Audit Logs
                </a>
                <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Dashboard Overview</h2>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChurchModal">
                            <i class="fas fa-plus me-2"></i> Add New Church
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total Churches</p>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_churches']); ?></h3>
                                </div>
                                <div class="stat-icon icon-blue">
                                    <i class="fas fa-church"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Active Churches</p>
                                    <h3 class="mb-0"><?php echo number_format($stats['active_churches']); ?></h3>
                                </div>
                                <div class="stat-icon icon-green">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total Users</p>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_users']); ?></h3>
                                </div>
                                <div class="stat-icon icon-orange">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Pending Approvals</p>
                                    <h3 class="mb-0"><?php echo number_format($stats['pending_approvals']); ?></h3>
                                </div>
                                <div class="stat-icon icon-red">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Churches Table -->
                <div class="table-responsive">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Churches</h5>
                        <a href="manage-churches.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Church Name</th>
                                <th>Church Code</th>
                                <th>Location</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_churches as $church): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($church['org_name']); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($church['org_code']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($church['city'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $church['user_count']; ?> users
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($church['subscription_status']) {
                                        case 'active':
                                            $status_class = 'badge-active';
                                            break;
                                        case 'suspended':
                                            $status_class = 'badge-suspended';
                                            break;
                                        default:
                                            $status_class = 'badge-expired';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($church['subscription_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($church['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view-church.php?id=<?php echo $church['id']; ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-church.php?id=<?php echo $church['id']; ?>" 
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Church Modal -->
    <div class="modal fade" id="addChurchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Church</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="create-church.php" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Church Name *</label>
                                <input type="text" class="form-control" name="org_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Church Code *</label>
                                <input type="text" class="form-control" name="org_code" 
                                       placeholder="e.g., ACK_NAIROBI" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Diocese</label>
                                <input type="text" class="form-control" name="diocese">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Archdeaconry</label>
                                <input type="text" class="form-control" name="archdeaconry">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" value="Kenya">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Users</label>
                                <input type="number" class="form-control" name="max_users" value="500">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subscription Status</label>
                                <select class="form-select" name="subscription_status">
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Church</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>