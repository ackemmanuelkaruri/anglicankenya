<?php
/**
 * Giving Admin Dashboard with Modern Design
 */
require_once __DIR__ . '/../../../db.php';
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/security.php';

start_secure_session();

// Ensure user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

 $userId = $_SESSION['user_id'];
 $roleLevel = $_SESSION['role_level'] ?? 'member';

// Only parish admins and above can access this page
if (!in_array($roleLevel, ['parish_admin', 'deanery_admin', 'archdeaconry_admin', 'diocese_admin', 'national_admin', 'super_admin'])) {
    header('Location: ../../dashboard.php');
    exit;
}

// Get user details
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$userId]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get giving statistics
require_once __DIR__ . '/../includes/giving_functions.php';

// Get total giving for current month
 $stmt = $pdo->prepare("
    SELECT SUM(amount) as total_amount, COUNT(*) as total_count
    FROM givings 
    WHERE parish_id = ? 
    AND status = 'completed'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
 $stmt->execute([$user['parish_id']]);
 $monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total giving for current year
 $stmt = $pdo->prepare("
    SELECT SUM(amount) as total_amount, COUNT(*) as total_count
    FROM givings 
    WHERE parish_id = ? 
    AND status = 'completed'
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
 $stmt->execute([$user['parish_id']]);
 $yearlyStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent transactions
 $recentTransactions = getParishGivings($user['parish_id'], ['limit' => 10]);

// Get active campaigns
 $campaigns = getParishCampaigns($user['parish_id']);

// Page title
$page_title = "Giving Dashboard - " . htmlspecialchars($user['parish_name'] ?? 'Parish');

// Include header with modern design
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title><?php echo htmlspecialchars($page_title); ?> - Church Management System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    
    <!-- Custom Giving CSS -->
    <link rel="stylesheet" href="../css/giving.css">
</head>
<body>
    <!-- Giving Header -->
    <div class="giving-header">
        <div class="container">
            <h1><i class="fas fa-chart-line me-3"></i>Giving Dashboard</h1>
            <p>Monitor and manage all giving activities for your parish</p>
        </div>
    </div>
    
    <!-- Giving Container -->
    <div class="giving-container">
        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card primary">
                <div class="stat-icon primary">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value">KES <?php echo number_format($monthlyStats['total_amount'] ?? 0, 2); ?></div>
                <div class="stat-label">This Month</div>
                <div class="mt-2"><?php echo $monthlyStats['total_count'] ?? 0; ?> transactions</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon success">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">KES <?php echo number_format($yearlyStats['total_amount'] ?? 0, 2); ?></div>
                <div class="stat-label">This Year</div>
                <div class="mt-2"><?php echo $yearlyStats['total_count'] ?? 0; ?> transactions</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon info">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="stat-value"><?php echo count($campaigns); ?></div>
                <div class="stat-label">Active Campaigns</div>
                <div class="mt-2">Currently running</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon warning">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value"><?php echo count(getParishPaybills($user['parish_id'])); ?></div>
                <div class="stat-label">Payment Options</div>
                <div class="mt-2">Available Paybills</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="paybills.php" class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-money-bill-wave me-2"></i> Manage Paybills
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="transactions.php" class="btn btn-outline-success w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-list-alt me-2"></i> View Transactions
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="reports.php" class="btn btn-outline-info w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-chart-bar me-2"></i> Giving Reports
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="reconcile.php" class="btn btn-outline-warning w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-sync-alt me-2"></i> Reconcile
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Campaigns -->
        <?php if (!empty($campaigns)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Active Campaigns</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($campaigns as $campaign): ?>
                            <div class="col-md-4 mb-3">
                                <div class="campaign-card">
                                    <div class="campaign-header">
                                        <h5><?php echo htmlspecialchars($campaign['title']); ?></h5>
                                    </div>
                                    <div class="campaign-body">
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo min($campaign['progress_percentage'], 100); ?>%"
                                                 aria-valuenow="<?php echo $campaign['progress_percentage']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="campaign-stats">
                                            <div class="campaign-stat">
                                                <div class="campaign-stat-value"><?php echo round($campaign['progress_percentage']); ?>%</div>
                                                <div class="campaign-stat-label">Completed</div>
                                            </div>
                                            <div class="campaign-stat">
                                                <div class="campaign-stat-value">KES <?php echo number_format($campaign['current_amount'], 2); ?></div>
                                                <div class="campaign-stat-label">Raised</div>
                                            </div>
                                            <div class="campaign-stat">
                                                <div class="campaign-stat-value">KES <?php echo number_format($campaign['target_amount'], 2); ?></div>
                                                <div class="campaign-stat-label">Target</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <small class="text-muted">Ends: <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Transactions -->
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentTransactions)): ?>
                    <div class="p-4 text-center">
                        <p class="text-muted">No recent transactions found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Member</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['member_name']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['purpose']); ?></td>
                                        <td>KES <?php echo number_format($transaction['amount'], 2); ?></td>
                                        <td>
                                            <?php if ($transaction['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($transaction['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['status'] == 'completed'): ?>
                                                <a href="../receipt.php?id=<?php echo $transaction['giving_id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-file-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Giving JS -->
   <script src="js/giving.js"></script>
</body>
</html>