<?php
/**
 * ============================================
 * USER NOTIFICATION PREFERENCES
 * Allows users to manage their email notification settings
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
$page_title = 'Notification Preferences';

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE users SET
                event_reminders = ?,
                sunday_preview = ?,
                event_notifications = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            isset($_POST['event_reminders']) ? 1 : 0,
            isset($_POST['sunday_preview']) ? 1 : 0,
            isset($_POST['event_notifications']) ? 1 : 0,
            $user_id
        ]);
        
        $success_message = 'Your notification preferences have been updated successfully.';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error updating preferences: " . $e->getMessage());
        $error_message = 'Error updating preferences. Please try again.';
    }
}

// Get current preferences
try {
    $stmt = $pdo->prepare("
        SELECT event_reminders, sunday_preview, event_notifications, email_opt_in
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error getting preferences: " . $e->getMessage());
    $preferences = [
        'event_reminders' => 1,
        'sunday_preview' => 1,
        'event_notifications' => 1,
        'email_opt_in' => 1
    ];
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

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="preferences-form">
            <form method="post" action="user_preferences.php">
                <div class="preference-section">
                    <h3><i class="fas fa-bell"></i> Email Notifications</h3>
                    <p>Choose which types of email notifications you'd like to receive:</p>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" id="event_reminders" name="event_reminders" class="custom-control-input" <?php echo $preferences['event_reminders'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="event_reminders">
                                <strong>Event Reminders</strong>
                                <small class="form-text text-muted">Receive reminder emails 24 hours before events you're interested in</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" id="sunday_preview" name="sunday_preview" class="custom-control-input" <?php echo $preferences['sunday_preview'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="sunday_preview">
                                <strong>Sunday Service Preview</strong>
                                <small class="form-text text-muted">Receive weekly emails with upcoming Sunday service details</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" id="event_notifications" name="event_notifications" class="custom-control-input" <?php echo $preferences['event_notifications'] ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="event_notifications">
                                <strong>Event Notifications</strong>
                                <small class="form-text text-muted">Receive notifications when new events are created in your area</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="preference-section">
                    <h3><i class="fas fa-envelope"></i> Email Delivery</h3>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" id="email_opt_in" name="email_opt_in" class="custom-control-input" <?php echo $preferences['email_opt_in'] ? 'checked' : ''; ?> disabled>
                            <label class="custom-control-label" for="email_opt_in">
                                <strong>Receive Emails</strong>
                                <small class="form-text text-muted">
                                    <?php if ($preferences['email_opt_in']): ?>
                                        You are currently subscribed to receive emails
                                    <?php else: ?>
                                        You have unsubscribed from all emails
                                    <?php endif; ?>
                                </small>
                            </label>
                        </div>
                        <?php if (!$preferences['email_opt_in']): ?>
                            <p class="text-muted">
                                To resubscribe, please contact your church administrator.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>
        
    </div>
</div>

<style>
.preferences-form {
    max-width: 800px;
    margin: 0 auto;
}

.preference-section {
    background: white;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.preference-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.preference-section h3 i {
    color: #3498db;
}

.custom-control {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #f8f9fa;
}

.custom-control:hover {
    background: #e9ecef;
}

.custom-control-input {
    margin-right: 10px;
}

.custom-control-input:checked ~ .custom-control-label {
    color: #2c3e50;
    font-weight: 600;
}

.custom-control-label {
    cursor: pointer;
}

.form-text {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.9rem;
}

.alert {
    margin-bottom: 20px;
}
</style>

<?php
// Include footer
include __DIR__ . '/../../includes/footer.php';
?>