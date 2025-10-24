<?php
/**
 * Generate Giving Receipt
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

// Get giving ID
 $givingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($givingId <= 0) {
    die('Invalid giving ID');
}

// Get giving details
require_once __DIR__ . '/includes/giving_functions.php';
 $receipt = generateGivingReceipt($givingId);

if (!$receipt) {
    die('Receipt not found');
}

// Check if user has permission to view this receipt
 $userId = $_SESSION['user_id'];
 $roleLevel = $_SESSION['role_level'] ?? 'member';

// Users can only view their own receipts unless they are admins
if ($receipt['member_id'] != $userId && !in_array($roleLevel, ['parish_admin', 'deanery_admin', 'archdeaconry_admin', 'diocese_admin', 'national_admin', 'super_admin'])) {
    die('Access denied');
}

// Generate PDF receipt (using TCPDF or similar)
// For now, we'll create a simple HTML receipt

 $page_title = "Giving Receipt - " . htmlspecialchars($receipt['parish_name']);

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0"><?php echo htmlspecialchars($receipt['parish_name']); ?></h3>
                    <p class="mb-0">Giving Receipt</p>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Receipt Number:</strong> #<?php echo $receipt['giving_id']; ?></p>
                            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($receipt['created_at'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($receipt['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if ($receipt['status'] == 'completed'): ?>
                                <p><strong>M-Pesa Receipt:</strong> <?php echo htmlspecialchars($receipt['mpesa_receipt_number']); ?></p>
                                <p><strong>Transaction Date:</strong> <?php echo date('F d, Y', strtotime($receipt['transaction_date'])); ?></p>
                            <?php else: ?>
                                <p><strong>Status:</strong> <span class="badge bg-warning">Pending</span></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Donor Information</h5>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($receipt['member_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($receipt['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($receipt['phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Details</h5>
                            <p><strong>Purpose:</strong> <?php echo htmlspecialchars($receipt['purpose']); ?></p>
                            <p><strong>Paybill:</strong> <?php echo htmlspecialchars($receipt['paybill_number']); ?></p>
                            <?php if (!empty($receipt['account'])): ?>
                                <p><strong>Account:</strong> <?php echo htmlspecialchars($receipt['account']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-center mb-4">
                        <h2>KES <?php echo number_format($receipt['amount'], 2); ?></h2>
                        <p class="text-muted">Amount Paid</p>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center mb-4">
                        <p>Thank you for your generous contribution to <?php echo htmlspecialchars($receipt['parish_name']); ?>.</p>
                        <p>May God bless you abundantly!</p>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <p><strong>Authorized By:</strong></p>
                            <p>_________________________</p>
                            <p>Church Administrator</p>
                        </div>
                        <div class="text-end">
                            <p><strong>Receipt Generated:</strong></p>
                            <p><?php echo date('F d, Y h:i A'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Receipt
                    </button>
                    <button class="btn btn-success" onclick="downloadReceipt()">
                        <i class="fas fa-download me-2"></i> Download PDF
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Giving
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadReceipt() {
    // In a real implementation, this would generate and download a PDF
    // For now, we'll just show an alert
    alert('PDF download would be implemented here using a library like TCPDF or mPDF');
}
</script>

<?php
// Include footer
include __DIR__ . '/../includes/footer.php';
?>