<?php
/**
 * Giving History Page for Members
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/security.php';

start_secure_session();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get user details
 $userId = $_SESSION['user_id'];
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$userId]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get giving history
require_once __DIR__ . '/includes/giving_functions.php';

// Pagination
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $perPage = 20;
 $offset = ($page - 1) * $perPage;

// Get total count
 $stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM givings 
    WHERE member_id = ?
");
 $stmt->execute([$userId]);
 $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
 $totalPages = ceil($total / $perPage);

// Get giving history with pagination
 $stmt = $pdo->prepare("
    SELECT gt.*, pp.purpose, mt.mpesa_receipt_number
    FROM givings gt
    JOIN parish_paybills pp ON gt.paybill_id = pp.id
    LEFT JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
    WHERE gt.member_id = ?
    ORDER BY gt.created_at DESC
    LIMIT ? OFFSET ?
");
 $stmt->execute([$userId, $perPage, $offset]);
 $givings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
 $page_title = "My Giving History - " . htmlspecialchars($user['parish_name']);

// Include header with modern design
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <h1><i class="fas fa-history me-3"></i>My Giving History</h1>
            <p>View and download all your past donations</p>
        </div>
    </div>
    
    <!-- Giving Container -->
    <div class="giving-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Your Donations</h3>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Giving
            </a>
        </div>
        
        <?php if (empty($givings)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                You haven't made any donations yet.
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
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
                                <?php foreach ($givings as $giving): ?>
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
                                                <a href="receipt.php?id=<?php echo $giving['giving_id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-file-download"></i> Receipt
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?>" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Giving JS -->
    <script src="js/giving.js"></script>
</body>
</html>