<?php
session_start();
require_once '../db.php';
require_once '../includes/data_helper.php'; // Include the data helper

// Enable error reporting for detailed debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debugging information
function debugLog($message) {
    error_log($message, 3, '../debug_responsibilities.log');
    echo $message . "<br>";
}

// Check if user is logged in
if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
    $id = $_SESSION['id'];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
} else {
    // Redirect to login if no valid ID found
    header("Location: login.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Get leadership roles with display names
$leadership_roles = getUserLeadershipRoles($pdo, $id);

// Get clergy information with display names
$clergy_roles = getUserClergyInfo($pdo, $id);

// Filter only active leadership roles (where is_current = '1' or end date is in the future/null)
$active_leadership_roles = array_filter($leadership_roles, function($role) {
    if (isset($role['is_current']) && $role['is_current'] == '1') {
        return true;
    }
    if (isset($role['to_date']) && !empty($role['to_date'])) {
        return strtotime($role['to_date']) >= strtotime(date('Y-m-d'));
    }
    return true; // If no end date, consider it active
});

// Filter only active clergy roles (where is_current = '1' or end date is in the future/null)
$active_clergy_roles = array_filter($clergy_roles, function($role) {
    if (isset($role['is_current']) && $role['is_current'] == '1') {
        return true;
    }
    if (isset($role['to_date']) && !empty($role['to_date'])) {
        return strtotime($role['to_date']) >= strtotime(date('Y-m-d'));
    }
    return true; // If no end date, consider it active
});

// Get current page filename for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Responsibilities - <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>
    <?php include '../includes/styles.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .responsibility-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .responsibility-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }
        
        .responsibility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .responsibility-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .responsibility-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .responsibility-title i {
            margin-right: 10px;
            color: #3498db;
        }
        
        .responsibility-count {
            background: #3498db;
            color: white;
            border-radius: 20px;
            padding: 5px 12px;
            font-weight: bold;
        }
        
        .role-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
            transition: all 0.3s ease;
        }
        
        .role-item:hover {
            background: #e1f0fa;
            transform: translateX(5px);
        }
        
        .role-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .role-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .role-detail {
            display: flex;
            align-items: center;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .role-detail i {
            margin-right: 5px;
            color: #3498db;
        }
        
        .no-responsibilities {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .no-responsibilities i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .back-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-banner h2 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            margin: 0;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .responsibility-container {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .back-link {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container-fluid">
        <!-- Include breadcrumb navigation -->
        <?php include '../breadcrumb.php'; ?>
        
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-2 bg-info" style="min-height: 100vh; margin-left: -30px;">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'dashboard.php') ? 'sidebar-active' : ''; ?>">Dashboard</a>
                    <a href="profile.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'profile.php') ? 'sidebar-active' : ''; ?>">Profile</a>
                    <a href="upload_details.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'upload_details.php') ? 'sidebar-active' : ''; ?>">Upload Details</a>
                    <a href="responsibilities.php" class="list-group-item list-group-item-action bg-info text-white text-center <?php echo ($currentPage == 'responsibilities.php') ? 'sidebar-active' : ''; ?>">Active Responsibilities</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="page-header mt-4">
                    <h1 class="page-title">Active Responsibilities</h1>
                    <a href="dashboard.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                
                <div class="welcome-banner">
                    <h2>Welcome, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>!</h2>
                    <p>Here's an overview of your current leadership and clergy responsibilities in the church.</p>
                </div>
                
                <div class="responsibility-container">
                    <!-- Leadership Roles Section -->
                    <div class="responsibility-card">
                        <div class="responsibility-header">
                            <h3 class="responsibility-title">
                                <i class="fas fa-users-cog"></i> Leadership Roles
                            </h3>
                            <span class="responsibility-count"><?= count($active_leadership_roles) ?></span>
                        </div>
                        
                        <?php if (empty($active_leadership_roles)): ?>
                            <div class="no-responsibilities">
                                <i class="fas fa-users-cog"></i>
                                <h4>No Active Leadership Roles</h4>
                                <p>You don't have any active leadership roles at the moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($active_leadership_roles as $role): ?>
                                <div class="role-item">
                                    <div class="role-name"><?= htmlspecialchars($role['display_role']) ?></div>
                                    
                                    <div class="role-details">
                                        <div class="role-detail">
                                            <i class="fas fa-building"></i>
                                            <span><?= htmlspecialchars($role['display_entity']) ?></span>
                                        </div>
                                        
                                        <div class="role-detail">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>
                                                Since: <?= isset($role['from_date']) ? date("F j, Y", strtotime($role['from_date'])) : 'Unknown' ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (isset($role['is_current']) && $role['is_current'] == '1'): ?>
                                            <div class="role-detail">
                                                <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                                                <span>Current Role</span>
                                            </div>
                                        <?php elseif (isset($role['to_date']) && !empty($role['to_date'])): ?>
                                            <div class="role-detail">
                                                <i class="fas fa-hourglass-end"></i>
                                                <span>Until: <?= date("F j, Y", strtotime($role['to_date'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Clergy Roles Section -->
                    <div class="responsibility-card">
                        <div class="responsibility-header">
                            <h3 class="responsibility-title">
                                <i class="fas fa-cross"></i> Clergy Roles
                            </h3>
                            <span class="responsibility-count"><?= count($active_clergy_roles) ?></span>
                        </div>
                        
                        <?php if (empty($active_clergy_roles)): ?>
                            <div class="no-responsibilities">
                                <i class="fas fa-cross"></i>
                                <h4>No Active Clergy Roles</h4>
                                <p>You don't have any active clergy roles at the moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($active_clergy_roles as $role): ?>
                                <div class="role-item">
                                    <div class="role-name"><?= htmlspecialchars($role['display_role']) ?></div>
                                    
                                    <div class="role-details">
                                        <div class="role-detail">
                                            <i class="fas fa-church"></i>
                                            <span><?= htmlspecialchars($role['display_entity'] ?? 'Parish') ?></span>
                                        </div>
                                        
                                        <div class="role-detail">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>
                                                Since: <?= isset($role['serving_from_date']) ? date("F j, Y", strtotime($role['serving_from_date'])) : 'Unknown' ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (isset($role['is_current']) && $role['is_current'] == '1'): ?>
                                            <div class="role-detail">
                                                <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                                                <span>Current Role</span>
                                            </div>
                                        <?php elseif (isset($role['to_date']) && !empty($role['to_date'])): ?>
                                            <div class="role-detail">
                                                <i class="fas fa-hourglass-end"></i>
                                                <span>Until: <?= date("F j, Y", strtotime($role['to_date'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Additional Information Section -->
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4>Role Information</h4>
                    </div>
                    <div class="card-body">
                        <p>This page shows only your active leadership and clergy roles. Active roles are those that are currently ongoing, meaning:</p>
                        <ul>
                            <li>The role is marked as "current" in the system, or</li>
                            <li>The end date is in the future or not set</li>
                        </ul>
                        <p>To view all your roles (including past roles), please visit your <a href="profile.php">profile page</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/scripts.php'; ?>
</body>
</html>