<?php
/**
 * ============================================
 * EVENTS MODULE - INDEX PAGE
 * Unified controller for all event views (List, Calendar, Create, Notifications, Logs)
 * All views open in the same window within the dashboard layout
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include common dependencies from the root directory
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/scope_helpers.php';
require_once __DIR__ . '/../../includes/dashboard_stats.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/includes/events_helper.php';

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

// --- VIEW CONTROLLER LOGIC ---
$view = $_GET['view'] ?? 'list'; // Default view is 'list'
$page_title = 'Events Management';

// Set page title based on view
switch ($view) {
    case 'list':
        $page_title = 'Events List';
        break;
    case 'calendar':
        $page_title = 'Events Calendar';
        break;
    case 'create':
        $page_title = 'Create New Event';
        break;
    case 'notifications':
        $page_title = 'Notification Preferences';
        break;
    case 'logs':
        $page_title = 'Email Logs';
        break;
}

// Fetch events only for the list view or calendar view
if ($view == 'list' || $view == 'calendar') {
    // Collect filters from GET request
    $filters = [
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'event_type' => $_GET['event_type'] ?? null,
        'status' => $_GET['status'] ?? null,
    ];
    
    // For calendar view, set month-specific filters
    if ($view == 'calendar') {
        $current_month = $_GET['month'] ?? date('m');
        $current_year = $_GET['year'] ?? date('Y');
        $filters['date_from'] = "$current_year-$current_month-01";
        $filters['date_to'] = date("Y-m-t", strtotime("$current_year-$current_month-01"));
    }
    
    $events = get_events($filters);
}

// Include header section
require_once __DIR__ . '/../../includes/dashboard_header.php';

// Start of the main dashboard layout
?>

<!-- Load Events Module Styles and Scripts -->
<link rel="stylesheet" href="css/events.css">
<script src="js/events.js" defer></script>

<div class="wrapper">
    <?php require_once __DIR__ . '/../../includes/dashboard_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header <?php echo $role_class ?? ''; ?>">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
        </div>
        
        <div class="module-nav mb-4">
            <a href="index.php?view=list" class="btn btn-sm <?php echo $view == 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-list"></i> Event List
            </a>
            <a href="index.php?view=calendar" class="btn btn-sm <?php echo $view == 'calendar' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-calendar-alt"></i> Calendar View
            </a>
            <a href="index.php?view=create" class="btn btn-sm <?php echo $view == 'create' ? 'btn-success' : 'btn-outline-success'; ?>">
                <i class="fas fa-plus"></i> Create New Event
            </a>
            <a href="index.php?view=notifications" class="btn btn-sm <?php echo $view == 'notifications' ? 'btn-info' : 'btn-outline-info'; ?>">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <?php 
            // Check if user can view logs
            $allowed_roles = ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin', 'parish_admin'];
            if (in_array($role_level, $allowed_roles)): 
            ?>
                <a href="index.php?view=logs" class="btn btn-sm <?php echo $view == 'logs' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-envelope-open-text"></i> Email Logs
                </a>
            <?php endif; ?>
        </div>

        <div class="module-content">
            <?php
            // Load the appropriate view content
            switch ($view) {
                case 'list':
                    include __DIR__ . '/templates/event_list.php';
                    break;
                    
                case 'calendar':
                    include __DIR__ . '/templates/events_calendar.php';
                    break;
                    
                case 'create':
                    include __DIR__ . '/create_event.php';
                    break;
                    
                case 'notifications':
                    include __DIR__ . '/user_preferences.php';
                    break;
                    
                case 'logs':
                    // Check permission again
                    if (in_array($role_level, $allowed_roles)) {
                        include __DIR__ . '/email_logs.php';
                    } else {
                        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> You do not have permission to view email logs.</div>';
                    }
                    break;
                    
                default:
                    include __DIR__ . '/templates/event_list.php';
            }
            ?>
        </div>
        
    </div>
</div>

<?php
// Include footer (same as dashboard)
include __DIR__ . '/../../includes/footer.php';
?>