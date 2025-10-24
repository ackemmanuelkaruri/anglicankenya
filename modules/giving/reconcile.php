<?php
/**
 * Transaction Reconciliation Page
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/security.php';

start_secure_session();

// Ensure user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

 $userId = $_SESSION['user_id'];
 $roleLevel = $_SESSION['role_level'] ?? 'member';

// Only parish admins and above can access this page
if (!in_array($roleLevel, ['parish_admin', 'deanery_admin', 'archdeaconry_admin', 'diocese_admin', 'national_admin', 'super_admin'])) {
    header('Location: ../dashboard.php');
    exit;
}

// Get user details
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$userId]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['statement_file'])) {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }
    
    $file = $_FILES['statement_file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload error: " . $file['error'];
    } else {
        $fileType = mime_content_type($file['tmp_name']);
        if ($fileType !== 'text/csv' && $fileType !== 'application/vnd.ms-excel') {
            $error = "Only CSV files are allowed";
        } else {
            // Process the CSV file
            $csvData = array_map('str_getcsv', file($file['tmp_name']));
            
            // Skip header row if present
            if (isset($_POST['has_header']) && $_POST['has_header'] == '1') {
                array_shift($csvData);
            }
            
            // Process each row
            $reconciled = 0;
            $unmatched = 0;
            
            foreach ($csvData as $row) {
                // Assuming CSV format: Date, Description, Amount, Reference
                if (count($row) >= 4) {
                    $date = $row[0];
                    $description = $row[1];
                    $amount = $row[2];
                    $reference = $row[3];
                    
                    // Try to match with our transactions
                    $stmt = $pdo->prepare("
                        SELECT * FROM givings gt
                        JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
                        WHERE gt.parish_id = ? 
                        AND gt.status = 'completed'
                        AND mt.mpesa_receipt_number = ?
                    ");
                    $stmt->execute([$user['parish_id'], $reference]);
                    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($transaction) {
                        // Mark as reconciled
                        $stmt = $pdo->prepare("
                            UPDATE givings 
                            SET is_reconciled = 1, 
                                reconciled_at = NOW(),
                                reconciled_by = ?
                            WHERE giving_id = ?
                        ");
                        $stmt->execute([$userId, $transaction['giving_id']]);
                        $reconciled++;
                    } else {
                        $unmatched++;
                    }
                }
            }
            
            $success = "Reconciliation completed: $reconciled transactions matched, $unmatched unmatched";
        }
    }
}

// Get unreconciled transactions
 $stmt = $pdo->prepare("
    SELECT gt.*, pp.purpose, CONCAT(u.first_name, ' ', u.last_name) as member_name,
           mt.mpesa_receipt_number
    FROM givings gt
    JOIN parish_paybills pp ON gt.paybill_id = pp.id
    JOIN users u ON gt.member_id = u.id
    JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
    WHERE gt.parish_id = ? 
    AND gt.status = 'completed'
    AND (gt.is_reconciled = 0 OR gt.is_reconciled IS NULL)
    ORDER BY gt.created_at DESC
");
 $stmt->execute([$user['parish_id']]);
 $unreconciledTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get reconciliation statistics
 $stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN is_reconciled = 1 THEN 1 ELSE 0 END) as reconciled_count,
        SUM(CASE WHEN is_reconciled = 0 OR is_reconciled IS NULL THEN 1 ELSE 0 END) as unreconciled_count,
        SUM(CASE WHEN is_reconciled = 1 THEN amount ELSE 0 END) as reconciled_amount,
        SUM(CASE WHEN is_reconciled = 0 OR is_reconciled IS NULL THEN amount ELSE 0 END) as unreconciled_amount
    FROM givings 
    WHERE parish_id = ? AND status = 'completed'
");
 $stmt->execute([$user['parish_id']]);
 $stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Page title
 $page_title = "Transaction Reconciliation - " . htmlspecialchars($user['parish_name']);

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo $page_title; ?></h2>
                <a href="admin/index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Giving Dashboard
                </a>
            </div>
            
            <!-- Reconciliation Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Reconciled</h5>
                            <h3><?php echo $stats['reconciled_count'] ?? 0; ?></h3>
                            <p class="card-text">transactions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Unreconciled</h5>
                            <h3><?php echo $stats['unreconciled_count'] ?? 0; ?></h3>
                            <p class="card-text">transactions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Reconciled Amount</h5>
                            <h3>KES <?php echo number_format($stats['reconciled_amount'] ?? 0, 2); ?></h3>
                            <p class="card-text">total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Unreconciled Amount</h5>
                            <h3>KES <?php echo number_format($stats['unreconciled_amount'] ?? 0, 2); ?></h3>
                            <p class="card-text">total</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Upload Statement Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Upload Bank/M-Pesa Statement</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="statement_file" class="form-label">Statement File (CSV)</label>
                            <input type="file" class="form-control" id="statement_file" name="statement_file" accept=".csv" required>
                            <div class="form-text">Upload a CSV file exported from your bank or M-Pesa statement</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="has_header" name="has_header" value="1" checked>
                            <label class="form-check-label" for="has_header">
                                File has header row
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Expected CSV Format:</h6>
                            <p>Date, Description, Amount, Reference</p>
                            <small class="text-muted">The system will match transactions using the Reference column (M-Pesa receipt number)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i> Upload and Reconcile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Unreconciled Transactions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Unreconciled Transactions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($unreconciledTransactions)): ?>
                        <p class="text-muted">All transactions have been reconciled.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Member</th>
                                        <th>Purpose</th>
                                        <th>Amount</th>
                                        <th>M-Pesa Receipt</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unreconciledTransactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['member_name']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['purpose']); ?></td>
                                            <td>KES <?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['mpesa_receipt_number']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary mark-reconciled" 
                                                        data-giving-id="<?php echo $transaction['giving_id']; ?>">
                                                    <i class="fas fa-check"></i> Mark Reconciled
                                                </button>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark as reconciled
    const markReconciledBtns = document.querySelectorAll('.mark-reconciled');
    markReconciledBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const givingId = this.dataset.givingId;
            
            fetch('api/mark_reconciled.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                },
                body: JSON.stringify({
                    giving_id: givingId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row
                    this.closest('tr').remove();
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success';
                    alert.innerHTML = '<i class="fas fa-check-circle me-2"></i> Transaction marked as reconciled';
                    document.querySelector('.container').prepend(alert);
                    
                    // Hide alert after 3 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the transaction as reconciled');
            });
        });
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/../includes/footer.php';
?>