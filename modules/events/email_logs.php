<?php
/**
 * ============================================
 * EMAIL DELIVERY LOGS
 * Shows email campaign statistics and delivery logs
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include common dependencies from the root directory
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/scope_helpers.php';
require_once __DIR__ . '/../../includes/rbac.php';

start_secure_session();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Load user details (needed for sidebar and scope)
$user_id = $_SESSION['user_id'];
$role_level = $_SESSION['role_level'] ?? 'member';
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        p.parish_name,
        d.deanery_name,
        a.archdeaconry_name,
        dio.diocese_name,
        prov.province_name
    FROM users u
    LEFT JOIN parishes p ON u.parish_id = p.parish_id
    LEFT JOIN deaneries d ON u.deanery_id = d.deanery_id
    LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
    LEFT JOIN dioceses dio ON u.diocese_id = dio.diocese_id
    LEFT JOIN provinces prov ON u.province_id = prov.province_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set theme preference
$theme = $_SESSION['theme'] ?? $user['theme_preference'] ?? 'light';
$_SESSION['theme'] = $theme;

// Handle impersonation check
$is_impersonating = isset($_SESSION['impersonating']) && isset($_SESSION['original_user_id']);

// Page settings
$page_title = 'Email Delivery Logs';

// Check if user has permission to view logs
// Only admins can view email logs
$allowed_roles = ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin'];
if (!in_array($role_level, $allowed_roles)) {
    // Include header first
    require_once __DIR__ . '/../../includes/dashboard_header.php';
    ?>
    <link rel="stylesheet" href="css/events.css">
    <div class="wrapper">
        <?php require_once __DIR__ . '/../../includes/dashboard_sidebar.php'; ?>
        <div class="main-content">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> You do not have permission to view email logs. This feature is only available to administrators.
            </div>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Get logs with filters
$campaign_id = $_GET['campaign_id'] ?? null;
$status_filter = $_GET['status'] ?? '';
$logs = [];

try {
    $sql = "
        SELECT el.*, ec.campaign_name, ec.campaign_type, u.first_name, u.last_name
        FROM email_logs el
        JOIN email_campaigns ec ON el.campaign_id = ec.campaign_id
        LEFT JOIN users u ON el.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($campaign_id) {
        $sql .= " AND el.campaign_id = ?";
        $params[] = $campaign_id;
    }
    
    if (!empty($status_filter)) {
        $sql .= " AND el.status = ?";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY el.created_at DESC LIMIT 200";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching email logs: " . $e->getMessage());
}

// Get campaign statistics
$campaign_stats = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            campaign_id,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened,
            SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked,
            SUM(CASE WHEN status = 'bounced' OR status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM email_logs
        GROUP BY campaign_id
    ");
    $stmt->execute();
    $campaign_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching campaign stats: " . $e->getMessage());
}

// Include header section
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<!-- Load Events Module Styles -->
<link rel="stylesheet" href="css/events.css">

<div class="wrapper">
    <?php require_once __DIR__ . '/../../includes/dashboard_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header <?php echo $role_class ?? ''; ?>">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
        </div>
        
        <div class="module-nav mb-4">
            <a href="index.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
        </div>
        
        <!-- Campaign Statistics -->
        <div class="campaign-stats">
            <h3>Campaign Statistics</h3>
            <?php if (empty($campaign_stats)): ?>
                <div class="alert alert-info">No campaign statistics available yet.</div>
            <?php else: ?>
                <div class="stats-grid">
                    <?php foreach ($campaign_stats as $stats): ?>
                        <div class="stat-card">
                            <h4>Campaign #<?php echo htmlspecialchars($stats['campaign_id']); ?></h4>
                            <div class="stat-row">
                                <span>Total:</span>
                                <span class="stat-number"><?php echo $stats['total']; ?></span>
                            </div>
                            <div class="stat-row">
                                <span>Sent:</span>
                                <span class="stat-number success"><?php echo $stats['sent']; ?></span>
                            </div>
                            <div class="stat-row">
                                <span>Delivered:</span>
                                <span class="stat-number info"><?php echo $stats['delivered']; ?></span>
                            </div>
                            <div class="stat-row">
                                <span>Opened:</span>
                                <span class="stat-number primary"><?php echo $stats['opened']; ?></span>
                            </div>
                            <div class="stat-row">
                                <span>Clicked:</span>
                                <span class="stat-number warning"><?php echo $stats['clicked']; ?></span>
                            </div>
                            <div class="stat-row">
                                <span>Failed:</span>
                                <span class="stat-number danger"><?php echo $stats['failed']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Filters -->
        <div class="logs-filters">
            <form method="get" action="email_logs.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="campaign_id">Campaign</label>
                        <select id="campaign_id" name="campaign_id" class="form-control">
                            <option value="">All Campaigns</option>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT campaign_id, campaign_name FROM email_campaigns ORDER BY created_at DESC LIMIT 50");
                                $stmt->execute();
                                $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($campaigns as $campaign) {
                                    $selected = ($campaign_id == $campaign['campaign_id']) ? 'selected' : '';
                                    echo "<option value=\"{$campaign['campaign_id']}\" $selected>" . htmlspecialchars($campaign['campaign_name']) . "</option>";
                                }
                            } catch (PDOException $e) {
                                error_log("Error fetching campaigns: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="queued" <?php echo ($status_filter == 'queued') ? 'selected' : ''; ?>>Queued</option>
                            <option value="sent" <?php echo ($status_filter == 'sent') ? 'selected' : ''; ?>>Sent</option>
                            <option value="delivered" <?php echo ($status_filter == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="opened" <?php echo ($status_filter == 'opened') ? 'selected' : ''; ?>>Opened</option>
                            <option value="clicked" <?php echo ($status_filter == 'clicked') ? 'selected' : ''; ?>>Clicked</option>
                            <option value="bounced" <?php echo ($status_filter == 'bounced') ? 'selected' : ''; ?>>Bounced</option>
                            <option value="failed" <?php echo ($status_filter == 'failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Logs Table -->
        <?php if (empty($logs)): ?>
            <div class="alert alert-info">No email logs found matching your criteria.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Campaign</th>
                            <th>Recipient</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Sent</th>
                            <th>Delivered</th>
                            <th>Opened</th>
                            <th>Clicked</th>
                            <th>Opens</th>
                            <th>Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <a href="email_logs.php?campaign_id=<?php echo $log['campaign_id']; ?>">
                                        <?php echo htmlspecialchars(substr($log['campaign_name'], 0, 30) . (strlen($log['campaign_name']) > 30 ? '...' : '')); ?>
                                    </a>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['campaign_type']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['recipient_email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $log['status'] == 'sent' ? 'success' : 
                                            ($log['status'] == 'delivered' ? 'info' : 
                                            ($log['status'] == 'opened' ? 'primary' : 
                                            ($log['status'] == 'clicked' ? 'warning' : 
                                            ($log['status'] == 'bounced' ? 'secondary' : 'danger')))); 
                                    ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $log['sent_at'] ? date('M j, g:i A', strtotime($log['sent_at'])) : '-'; ?></td>
                                <td><?php echo $log['delivered_at'] ? date('M j, g:i A', strtotime($log['delivered_at'])) : '-'; ?></td>
                                <td><?php echo $log['opened_at'] ? date('M j, g:i A', strtotime($log['opened_at'])) : '-'; ?></td>
                                <td><?php echo $log['clicked_at'] ? date('M j, g:i A', strtotime($log['clicked_at'])) : '-'; ?></td>
                                <td><?php echo $log['open_count'] ?: '0'; ?></td>
                                <td><?php echo $log['click_count'] ?: '0'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
.campaign-stats {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-card h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2c3e50;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.stat-number {
    font-weight: 600;
}

.stat-number.success { color: #2ecc71; }
.stat-number.info { color: #3498db; }
.stat-number.primary { color: #9b59b6; }
.stat-number.warning { color: #f39c12; }
.stat-number.danger { color: #e74c3c; }

.logs-filters {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.table-responsive {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.table {
    width: 100%;
    margin-bottom: 0;
}

.table th {
    background: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #2c3e50;
    white-space: nowrap;
    padding: 12px;
}

.table td {
    padding: 12px;
    vertical-align: middle;
}

.badge {
    font-size: 0.8rem;
    padding: 5px 10px;
    border-radius: 4px;
}

.badge-success { background-color: #2ecc71; color: white; }
.badge-info { background-color: #3498db; color: white; }
.badge-primary { background-color: #9b59b6; color: white; }
.badge-warning { background-color: #f39c12; color: white; }
.badge-secondary { background-color: #95a5a6; color: white; }
.badge-danger { background-color: #e74c3c; color: white; }
</style>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>