<?php
/**
 * ============================================
 * UNIFIED CENTRAL DASHBOARD - CONSOLIDATED VERSION
 * Role-Based Adaptive Dashboard with Complete Feature Set
 * Includes: RBAC, Impersonation, Theme Support, Phase 3 Features
 * ============================================
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// CRITICAL: Define DB_INCLUDED before any includes
define('DB_INCLUDED', true);

// Load database and session FIRST
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db_session.php';
require_once __DIR__ . '/includes/security.php';

// Start secure session with database support
start_secure_session();

// Debug logging
error_log("Dashboard loaded - Session ID: " . session_id());
error_log("Dashboard - User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Dashboard - is_logged_in(): " . (is_logged_in() ? 'YES' : 'NO'));

// Ensure user is logged in
if (!is_logged_in()) {
    error_log("Dashboard: User not logged in, redirecting to login.php");
    header('Location: login.php');
    exit;
}

// Now load other includes
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/scope_helpers.php';
require_once __DIR__ . '/includes/dashboard_stats.php';
require_once __DIR__ . '/includes/rbac.php';

// Handle impersonation check
$is_impersonating = isset($_SESSION['impersonating']) && isset($_SESSION['original_user_id']);
$original_user_id = $is_impersonating ? $_SESSION['original_user_id'] : null;

// Handle theme change via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $allowed_themes = ['light', 'dark', 'ocean', 'forest'];
    if (in_array($_POST['theme'], $allowed_themes)) {
        $_SESSION['theme'] = $_POST['theme'];
        $user_id = $_SESSION['user_id'];
        $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?")
            ->execute([$_POST['theme'], $user_id]);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Load user details with all hierarchical information
$user_id = $_SESSION['user_id'];
$role_level = $_SESSION['role_level'] ?? 'member';

$stmt = $pdo->prepare("
    SELECT 
        u.*,
        o.org_name,
        o.org_code,
        p.parish_name,
        d.deanery_name,
        a.archdeaconry_name,
        dio.diocese_name,
        prov.province_name
    FROM users u
    LEFT JOIN organizations o ON u.organization_id = o.id
    LEFT JOIN parishes p ON u.parish_id = p.parish_id
    LEFT JOIN deaneries d ON u.deanery_id = d.deanery_id
    LEFT JOIN archdeaconries a ON u.archdeaconry_id = a.archdeaconry_id
    LEFT JOIN dioceses dio ON u.diocese_id = dio.diocese_id
    LEFT JOIN provinces prov ON u.province_id = prov.province_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    error_log("Dashboard: User data not found for user_id: " . $user_id);
    session_destroy();
    header('Location: login.php?error=user_not_found');
    exit;
}

// Set theme preference
$theme = $_SESSION['theme'] ?? $user['theme_preference'] ?? 'light';
$_SESSION['theme'] = $theme;

// Ensure role_level is set
if (empty($role_level) && !empty($user['role_level'])) {
    $role_level = $user['role_level'];
    $_SESSION['role_level'] = $role_level;
}

// Get dashboard statistics
$stats = get_dashboard_stats();
$role_class = "role-{$role_level}";

// Log successful dashboard load
error_log("Dashboard loaded successfully for user: " . $user['username'] . " (ID: " . $user_id . ")");

// Include header section
require_once __DIR__ . '/includes/dashboard_header.php';

// Include impersonation banner if active
if ($is_impersonating) {
    require_once __DIR__ . '/includes/dashboard_impersonation_banner.php';
}

// Include sidebar navigation
require_once __DIR__ . '/includes/dashboard_sidebar.php';

// Include main content area
require_once __DIR__ . '/includes/dashboard_main_content.php';

// Include footer
include __DIR__ . '/includes/footer.php';
?>
