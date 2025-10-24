<?php
/**
 * Giving Reports Page
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/security.php';

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

// Handle report generation
 $reportData = [];
 $reportType = $_GET['report_type'] ?? 'summary';
 $year = $_GET['year'] ?? date('Y');
 $month = $_GET['month'] ?? date('m');

// Get giving functions
require_once __DIR__ . '/../includes/giving_functions.php';

// Generate report based on type
switch ($reportType) {
    case 'summary':
        // Monthly summary for the year
        for ($m = 1; $m <= 12; $m++) {
            $stmt = $pdo->prepare("
                SELECT SUM(amount) as total_amount, COUNT(*) as total_count
                FROM givings 
                WHERE parish_id = ? 
                AND status = 'completed'
                AND MONTH(created_at) = ?
                AND YEAR(created_at) = ?
            ");
            $stmt->execute([$user['parish_id'], $m, $year]);
            $monthData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $reportData[] = [
                'month' => date('F', mktime(0, 0, 0, $m, 1)),
                'amount' => $monthData['total_amount'] ?? 0,
                'count' => $monthData['total_count'] ?? 0
            ];
        }
        break;
        
    case 'detailed':
        // Detailed transactions for selected month
        $stmt = $pdo->prepare("
            SELECT gt.*, pp.purpose, CONCAT(u.first_name, ' ', u.last_name) as member_name
            FROM givings gt
            JOIN parish_paybills pp ON gt.paybill_id = pp.id
            JOIN users u ON gt.member_id = u.id
            WHERE gt.parish_id = ? 
            AND gt.status = 'completed'
            AND MONTH(gt.created_at) = ?
            AND YEAR(gt.created_at) = ?
            ORDER BY gt.created_at DESC
        ");
        $stmt->execute([$user['parish_id'], $month, $year]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'by_purpose':
        // Report grouped by purpose
        $stmt = $pdo->prepare("
            SELECT pp.purpose, SUM(gt.amount) as total_amount, COUNT(*) as total_count
            FROM givings gt
            JOIN parish_paybills pp ON gt.paybill_id = pp.id
            WHERE gt.parish_id = ? 
            AND gt.status = 'completed'
            AND MONTH(gt.created_at) = ?
            AND YEAR(gt.created_at) = ?
            GROUP BY pp.purpose
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$user['parish_id'], $month, $year]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}

// Page title
 $page_title = "Giving Reports - " . htmlspecialchars($user['parish_name']);

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo $page_title; ?></h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Giving Dashboard
                </a>
            </div>
            
            <!-- Report Options -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Report Options</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <option value="summary" <?php echo $reportType == 'summary' ? 'selected' : ''; ?>>Monthly Summary</option>
                                <option value="detailed" <?php echo $reportType == 'detailed' ? 'selected' : ''; ?>>Detailed Transactions</option>
                                <option value="by_purpose" <?php echo $reportType == 'by_purpose' ? 'selected' : ''; ?>>By Purpose</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="month" class="form-label">Month</label>
                            <select class="form-select" id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-chart-bar me-2"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Report Display -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php 
                        switch ($reportType) {
                            case 'summary':
                                echo 'Monthly Summary for ' . $year;
                                break;
                            case 'detailed':
                                echo 'Detailed Transactions for ' . date('F Y', mktime(0, 0, 0, $month, 1, $year));
                                break;
                            case 'by_purpose':
                                echo 'Giving by Purpose for ' . date('F Y', mktime(0, 0, 0, $month, 1, $year));
                                break;
                        }
                        ?>
                    </h5>
                    <button class="btn btn-sm btn-outline-success" id="printBtn">
                        <i class="fas fa-print me-2"></i> Print
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($reportType == 'summary'): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Total Amount</th>
                                        <th>Transaction Count</th>
                                        <th>Average</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $data): ?>
                                        <tr>
                                            <td><?php echo $data['month']; ?></td>
                                            <td>KES <?php echo number_format($data['amount'], 2); ?></td>
                                            <td><?php echo $data['count']; ?></td>
                                            <td>KES <?php echo $data['count'] > 0 ? number_format($data['amount'] / $data['count'], 2) : '0.00'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th>Total</th>
                                        <th>KES <?php echo number_format(array_sum(array_column($reportData, 'amount')), 2); ?></th>
                                        <th><?php echo array_sum(array_column($reportData, 'count')); ?></th>
                                        <th>KES <?php 
                                            $totalCount = array_sum(array_column($reportData, 'count'));
                                            $totalAmount = array_sum(array_column($reportData, 'amount'));
                                            echo $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : '0.00'; 
                                        ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Chart -->
                        <div class="mt-4">
                            <canvas id="monthlyChart" height="100"></canvas>
                        </div>
                        
                    <?php elseif ($reportType == 'detailed'): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Member</th>
                                        <th>Purpose</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $data): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($data['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($data['member_name']); ?></td>
                                            <td><?php echo htmlspecialchars($data['purpose']); ?></td>
                                            <td>KES <?php echo number_format($data['amount'], 2); ?></td>
                                            <td>
                                                <?php if ($data['method'] == 'mpesa'): ?>
                                                    <i class="fab fa-mpesa"></i> M-Pesa
                                                <?php else: ?>
                                                    <?php echo ucfirst($data['method']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="../receipt.php?id=<?php echo $data['giving_id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-file-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                    <?php elseif ($reportType == 'by_purpose'): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Purpose</th>
                                        <th>Total Amount</th>
                                        <th>Transaction Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalAmount = array_sum(array_column($reportData, 'total_amount'));
                                    foreach ($reportData as $data): 
                                        $percentage = $totalAmount > 0 ? ($data['total_amount'] / $totalAmount) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['purpose']); ?></td>
                                            <td>KES <?php echo number_format($data['total_amount'], 2); ?></td>
                                            <td><?php echo $data['total_count']; ?></td>
                                            <td><?php echo number_format($percentage, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th>Total</th>
                                        <th>KES <?php echo number_format($totalAmount, 2); ?></th>
                                        <th><?php echo array_sum(array_column($reportData, 'total_count')); ?></th>
                                        <th>100%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Pie Chart -->
                        <div class="mt-4">
                            <canvas id="purposeChart" height="100"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print button
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    // Monthly Summary Chart
    <?php if ($reportType == 'summary'): ?>
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($reportData, 'month')); ?>,
                datasets: [{
                    label: 'Total Amount (KES)',
                    data: <?php echo json_encode(array_column($reportData, 'amount')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'KES ' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>
    
    // Purpose Pie Chart
    <?php if ($reportType == 'by_purpose'): ?>
        const purposeCtx = document.getElementById('purposeChart').getContext('2d');
        const purposeChart = new Chart(purposeCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($reportData, 'purpose')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($reportData, 'total_amount')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return label + ': KES ' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>
});
</script>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>