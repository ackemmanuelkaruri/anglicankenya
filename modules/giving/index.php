<?php
/**
 * Giving Module - Main Page with Modern Design
 * Allows users to make donations to their parish
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/security.php';

start_secure_session();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Get user details with parish name
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, p.parish_name 
    FROM users u
    LEFT JOIN parishes p ON u.parish_id = p.parish_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If no parish_name from join, set a default
if (empty($user['parish_name'])) {
    $user['parish_name'] = 'Your Parish';
}

// Get role level
$role_level = $_SESSION['role_level'] ?? 'member';



// Initialize variables
$paybills = [];
$campaigns = [];

// Check if user has a parish assigned
if (!empty($user['parish_id'])) {
    // Get parish Paybills
    require_once __DIR__ . '/includes/giving_functions.php';
    $paybills = getParishPaybills($user['parish_id']);
    
    // Get donation campaigns
    $campaigns = getParishCampaigns($user['parish_id']);
}

// Page title
$page_title = "Give to " . htmlspecialchars($user['parish_name'] ?? 'Your Parish');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title><?php echo htmlspecialchars($page_title); ?> - Church Management System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    
    <!-- Custom Giving CSS -->
    <link rel="stylesheet" href="css/giving.css">
</head>
<body>
    <!-- Giving Header -->
    <div class="giving-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1><i class="fas fa-hand-holding-heart me-3"></i>Church Giving</h1>
                    <p class="mb-0">Support your parish through secure and convenient digital payments</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <?php if ($role_level === 'parish_admin' || $role_level === 'diocese_admin' || $role_level === 'super_admin'): ?>
                        <a href="admin/index.php" class="btn btn-light me-2">
                            <i class="fas fa-cog me-2"></i>Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="../../dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Giving Container -->
    <div class="giving-container">
        <?php if (empty($user['parish_id'])): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You are not assigned to a parish. Please contact your administrator.
            </div>
        <?php elseif (empty($paybills)): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No payment options are currently available for your parish. Please contact your parish administrator.
            </div>
        <?php else: ?>
            <!-- Paybill Selection -->
            <div class="paybill-selection">
                <h3 class="mb-4">Select Payment Purpose</h3>
                <div class="row">
                    <?php foreach ($paybills as $paybill): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="paybill-card" 
                                 data-paybill-id="<?php echo $paybill['id']; ?>"
                                 data-paybill-number="<?php echo htmlspecialchars($paybill['paybill_number']); ?>"
                                 data-account="<?php echo htmlspecialchars($paybill['account'] ?? ''); ?>"
                                 data-purpose="<?php echo htmlspecialchars($paybill['purpose']); ?>">
                                <div class="paybill-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="paybill-details">
                                    <h5><?php echo htmlspecialchars($paybill['purpose']); ?></h5>
                                    <p>Paybill: <?php echo htmlspecialchars($paybill['paybill_number']); ?>
                                        <?php if (!empty($paybill['account'])): ?>
                                            | Account: <?php echo htmlspecialchars($paybill['account']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div class="payment-form">
                <h3 class="mb-4">Payment Details</h3>
                <form id="givingForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount (KES)</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number" class="form-control" id="amount" min="10" step="0.01" required>
                            </div>
                            <div class="form-text">Minimum amount is KES 10.00</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">M-Pesa Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                <input type="tel" class="form-control" id="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="form-text">Enter the M-Pesa number to receive the payment prompt</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($campaigns)): ?>
                        <div class="mb-3">
                            <label for="campaign" class="form-label">Optional: Donate to a Campaign</label>
                            <select class="form-select" id="campaign">
                                <option value="">Select a campaign (optional)</option>
                                <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?php echo $campaign['campaign_id']; ?>">
                                        <?php echo htmlspecialchars($campaign['title']); ?> 
                                        (Target: KES <?php echo number_format($campaign['target_amount'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" class="payment-button" id="submitBtn">
                            <i class="fas fa-mobile-alt"></i> Send M-Pesa Prompt
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Payment Status -->
            <div class="payment-status" id="paymentStatus">
                <!-- Payment status will be inserted here dynamically -->
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions for Admins -->
        <?php if ($role_level === 'parish_admin' || $role_level === 'diocese_admin' || $role_level === 'super_admin'): ?>
        <div class="row mt-4 mb-4">
            <div class="col-12">
                <h4 class="mb-3">Admin Quick Actions</h4>
            </div>
            <div class="col-md-3 mb-3">
                <a href="admin/index.php" class="quick-action-card">
                    <i class="fas fa-tachometer-alt text-primary"></i>
                    <p class="mb-0 mt-2">Admin Dashboard</p>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="admin/paybills.php" class="quick-action-card">
                    <i class="fas fa-wallet text-success"></i>
                    <p class="mb-0 mt-2">Manage Paybills</p>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="admin/transactions.php" class="quick-action-card">
                    <i class="fas fa-exchange-alt text-info"></i>
                    <p class="mb-0 mt-2">View Transactions</p>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="admin/reports.php" class="quick-action-card">
                    <i class="fas fa-chart-bar text-warning"></i>
                    <p class="mb-0 mt-2">Generate Reports</p>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Giving History -->
        <div class="giving-history">
            <h3>Recent Giving History</h3>
            <?php
            if (!empty($user['parish_id'])) {
                $stmt = $pdo->prepare("
                    SELECT gt.*, pp.purpose, mt.mpesa_receipt_number
                    FROM givings gt
                    JOIN parish_paybills pp ON gt.paybill_id = pp.id
                    LEFT JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
                    WHERE gt.member_id = ?
                    ORDER BY gt.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$userId]);
                $recentGivings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $recentGivings = [];
            }
            
            if (empty($recentGivings)): ?>
                <p class="text-muted">You have no recent giving history.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table giving-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Purpose</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentGivings as $giving): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($giving['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($giving['purpose']); ?></td>
                                    <td>KES <?php echo number_format($giving['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($giving['status'] == 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($giving['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($giving['status'] == 'completed'): ?>
                                            <a href="receipt.php?id=<?php echo $giving['giving_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="history.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-history me-2"></i>View All Giving History
                    </a>
                    <?php if ($role_level === 'parish_admin' || $role_level === 'diocese_admin' || $role_level === 'super_admin'): ?>
                        <a href="reconcile.php" class="btn btn-outline-success">
                            <i class="fas fa-sync-alt me-2"></i>Reconcile Payments
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Giving JS -->
    <script src="js/giving.js"></script>
    
    <style>
    .quick-action-card {
        display: block;
        padding: 2rem 1rem;
        text-align: center;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
    }
    
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        color: #333;
    }
    
    .quick-action-card i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .quick-action-card p {
        font-weight: 600;
        font-size: 0.9rem;
    }
    </style>
</body>
</html>