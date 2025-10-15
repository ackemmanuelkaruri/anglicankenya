<?php
session_start();
require_once '../db.php'; // Database connection
require_once 'functions.php'; // Functions file
// Check for "Remember Me" cookie
if (isset($_COOKIE['remember_user']) && !isset($_SESSION['username'])) {
    $_SESSION['username'] = $_COOKIE['remember_user'];
}
// Fetch actual counts from the database
try {
    // Fetch pending approvals count
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE status = 'pending'");
    $pendingApprovals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    // Fetch total users
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    // Fetch total administrators
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE role = 'admin'");
    $totalAdministrators = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    // Fetch total parishioners
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE role = 'parishioner'");
    $totalParishioners = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // NEW: Fetch pending minor activation requests count
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM minor_activation_requests WHERE status = 'pending'");
    $pendingMinorActivations = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Initialize default values for statistics that might not have tables
    $reportsGenerated = 0;
    $settingsUpdates = 0;
    $baptizedMembers = 0;
    $confirmedMembers = 0;
    $activeEmployees = 0;
    
    // Try to fetch reports generated if table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM reports");
        $reportsGenerated = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Table doesn't exist, keep default value
    }
    
    // Try to fetch settings updates if table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM settings_updates");
        $settingsUpdates = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Table doesn't exist, keep default value
    }
    
    // Fetch baptized members from users table (not members table)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE baptized = 'yes'");
        $baptizedMembers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Field might not exist, keep default value
    }
    
    // Fetch confirmed members from users table (not members table)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE confirmed = 'yes'");
        $confirmedMembers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Field might not exist, keep default value
    }
    
    // Try to fetch active employees if table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM employees WHERE status_id = 'active'");
        $activeEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Table doesn't exist, keep default value
    }
    
    // Try to fetch marital status from users table
    try {
        $stmt = $pdo->query("SELECT marital_status FROM users LIMIT 1");
        $maritalStatus = $stmt->fetch(PDO::FETCH_ASSOC)['marital_status'] ?? null;
    } catch (Exception $e) {
        $maritalStatus = null;
    }
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background-image: url('../img/face.jpg'); 
            background-size: cover; 
            background-attachment: fixed;
        }
        .dashboard-box { 
            width: 150px; 
            height: 150px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            border-radius: 10px; 
            font-size: 20px; 
            text-decoration: none; 
            transition: transform 0.2s ease-in-out;
            position: relative;
            margin: 0 auto 15px;
        }
        .dashboard-box:hover { transform: scale(1.1); }
        .icon { font-size: 40px; }
        .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: red;
            color: white;
            padding: 5px 10px;
            border-radius: 50%;
            font-size: 14px;
        }
        .dashboard-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card h5 {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stats-card h2 {
            margin: 0;
            font-weight: 700;
        }
        .comprehensive-dashboard {
            margin-top: 30px;
        }
        /* NEW: Minor activation card styles */
        .minor-activation-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .minor-activation-card h5 {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .minor-activation-card h2 {
            margin: 0;
            font-weight: 700;
        }
        .minor-activation-card .btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            margin-top: 10px;
        }
        .minor-activation-card .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        .recent-requests {
            margin-top: 15px;
        }
        .recent-requests .request-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-info" style="min-height: 100vh; margin-left: -30px;">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action bg-info text-white text-center">Dashboard</a>
                    <a href="administrators.php" class="list-group-item list-group-item-action bg-info text-white text-center">Administrator</a>
                    <a href="users.php" class="list-group-item list-group-item-action bg-info text-white text-center">Users</a>
                    <a href="parishioners.php" class="list-group-item list-group-item-action bg-info text-white text-center">Parishioners</a>
                    <!-- NEW: Add link to minor activation requests -->
                    <a href="admin_activation_requests.php" class="list-group-item list-group-item-action bg-info text-white text-center">Minor Activations</a>
                </div>
            </div>
            <div class="col-md-10">
                <div class="dashboard-container">
                    <h3 class="text-center mt-2">Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                    
                    <!-- Original Dashboard Boxes -->
                    <div class="row text-center mt-4">
                        <!-- Users -->
                        <div class="col-md-2">
                            <a href="users.php" class="dashboard-box bg-primary text-white">
                                <i class="fas fa-users icon"></i>
                                <p><?php echo $totalUsers; ?></p>
                            </a>
                            <p>Users</p>
                        </div>
                        <!-- Pending Approvals -->
                        <div class="col-md-2">
                            <a href="pending_approvals.php" class="dashboard-box bg-danger text-white position-relative">
                                <i class="fas fa-exclamation-circle icon"></i>
                                <p><?php echo $pendingApprovals; ?></p>
                                <?php if ($pendingApprovals > 0): ?>
                                    <span class="badge"><?php echo $pendingApprovals; ?></span>
                                <?php endif; ?>
                            </a>
                            <p>Pending Approvals</p>
                        </div>
                        <!-- Other Statistics -->
                        <div class="col-md-2">
                            <a href="administrators.php" class="dashboard-box bg-success text-white">
                                <i class="fas fa-user-shield icon"></i>
                                <p><?php echo $totalAdministrators; ?></p>
                            </a>
                            <p>Administrators</p>
                        </div>
                        <div class="col-md-2">
                            <a href="parishioners.php" class="dashboard-box bg-warning text-white">
                                <i class="fas fa-church icon"></i>
                                <p><?php echo $totalParishioners; ?></p>
                            </a>
                            <p>Parishioners</p>
                        </div>
                        <div class="col-md-2">
                            <a href="#" class="dashboard-box bg-info text-white">
                                <i class="fas fa-chart-line icon"></i>
                                <p><?php echo $reportsGenerated; ?></p>
                            </a>
                            <p>Reports</p>
                        </div>
                        <div class="col-md-2">
                            <a href="#" class="dashboard-box bg-secondary text-white">
                                <i class="fas fa-cogs icon"></i>
                                <p><?php echo $settingsUpdates; ?></p>
                            </a>
                            <p>Settings Updates</p>
                        </div>
                    </div>
                    
                    <!-- NEW: Minor Activation Requests Card -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="minor-activation-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5>Minor Activation Requests</h5>
                                        <h2><?php echo $pendingMinorActivations; ?></h2>
                                        <p>Pending Requests</p>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-child" style="font-size: 2.5rem; opacity: 0.7;"></i>
                                    </div>
                                </div>
                                <a href="admin_activation_requests.php" class="btn btn-sm">Manage Requests</a>
                                
                                <?php if ($pendingMinorActivations > 0): ?>
                                    <div class="recent-requests">
                                        <h6>Recent Requests:</h6>
                                        <?php
                                        // Fetch recent minor activation requests
                                        try {
                                            $stmt = $pdo->query("
                                                SELECT mar.id, fm.minor_first_name, fm.minor_last_name, 
                                                       FLOOR(DATEDIFF(CURDATE(), fm.minor_date_of_birth) / 365.25) as current_age,
                                                       mar.created_at
                                                FROM minor_activation_requests mar
                                                JOIN minor_profiles mp ON mar.minor_profile_id = mp.id
                                                JOIN family_members fm ON mp.family_member_id = fm.id
                                                WHERE mar.status = 'pending'
                                                ORDER BY mar.created_at DESC
                                                LIMIT 3
                                            ");
                                            $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach ($recentRequests as $request) {
                                                echo '<div class="request-item">';
                                                echo htmlspecialchars($request['minor_first_name'] . ' ' . $request['minor_last_name']);
                                                echo ' <span class="badge bg-light text-dark">' . $request['current_age'] . ' years</span>';
                                                echo '</div>';
                                            }
                                        } catch (Exception $e) {
                                            echo '<div class="request-item">Error loading requests</div>';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Additional Statistics Card -->
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <h5>Baptized Members</h5>
                                <h2 class="text-primary"><?php echo $baptizedMembers; ?></h2>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <h5>Confirmed Members</h5>
                                <h2 class="text-success"><?php echo $confirmedMembers; ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Statistics Row -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <h5>Active Employees</h5>
                                <h2 class="text-warning"><?php echo $activeEmployees; ?></h2>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <h5>Marital Status</h5>
                                <h2 class="text-info"><?php echo $maritalStatus ?? 'N/A'; ?></h2>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <h5>Total Minors</h5>
                                <h2 class="text-danger">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM family_members WHERE is_minor = 1");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    } catch (Exception $e) {
                                        echo 'N/A';
                                    }
                                    ?>
                                </h2>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card text-center">
                                <h5>Minors Ready</h5>
                                <h2 class="text-success">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT COUNT(*) AS count 
                                            FROM minor_profiles 
                                            WHERE is_ready_for_activation = 1
                                        ");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    } catch (Exception $e) {
                                        echo 'N/A';
                                    }
                                    ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comprehensive Dashboard Section -->
                    <div class="comprehensive-dashboard">
                        <h2 class="section-title">Comprehensive Leadership & Family Dashboard</h2>
                        <?php include 'dashboard_content.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>