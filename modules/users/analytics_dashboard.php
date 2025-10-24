<?php
/**
 * ============================================
 * ADVANCED MEMBERS ANALYTICS DASHBOARD
 * Multi-level Menu | Charts | Comparisons | Export
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../db.php';
require_once '../../includes/init.php';
require_once '../../includes/security.php';
require_once '../../includes/scope_helpers.php'; // Your provided helper file
require_once '../../includes/rbac.php';

start_secure_session();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$role_level = $_SESSION['role_level'] ?? 'member';
$current_user_id = $_SESSION['user_id'];

// Only admins can access analytics
if ($role_level === 'member') {
    header('Location: ../../dashboard.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================
// NEW SCOPE LOGIC IMPLEMENTATION
// (Code for get_analytics_scope_data remains unchanged from prior steps)
// ============================================

/**
 * Gets the current base scope and list of selectable scopes for analytics.
 * This is a wrapper using the functions defined in scope_helpers.php.
 * * @return array 
 */
function get_analytics_scope_data() {
    global $pdo; // Ensure $pdo is available for helper functions

    $user_scope = get_user_scope();
    $role_level = $user_scope['type'];
    $selectable_scopes = [];
    // Define which roles are allowed to switch scopes
    $can_select_scope = in_array($role_level, ['super_admin', 'national_admin', 'diocese_admin', 'archdeaconry_admin', 'deanery_admin']);
    
    $data = [
        'level' => 'parish', // Default base level
        'id' => $user_scope['parish_id'],
        'can_select_scope' => $can_select_scope,
        'selectable_scopes' => []
    ];

    // Determine the base scope level and ID
    if ($role_level === 'super_admin' || $role_level === 'national_admin') {
        $data['level'] = 'global';
        $data['id'] = 0; // Global is ID 0
    } elseif ($role_level === 'diocese_admin') {
        $data['level'] = 'diocese';
        $data['id'] = $user_scope['diocese_id'];
    } elseif ($role_level === 'archdeaconry_admin') {
        $data['level'] = 'archdeaconry';
        $data['id'] = $user_scope['archdeaconry_id'];
    } elseif ($role_level === 'deanery_admin') {
        $data['level'] = 'deanery';
        $data['id'] = $user_scope['deanery_id'];
    } elseif ($role_level === 'parish_admin') {
        $data['level'] = 'parish';
        $data['id'] = $user_scope['parish_id'];
    }

    // Build the selectable list if the user can select
    if ($can_select_scope) {
        if ($data['level'] === 'global') {
            $data['selectable_scopes'][] = ['level' => 'global', 'id' => 0, 'name' => 'All Church Data (Super Admin)'];
        }
        
        // Add the user's base scope (My Scope)
        $data['selectable_scopes'][] = ['level' => $data['level'], 'id' => (int)($data['id'] ?? 0), 'name' => 'My ' . ucfirst($data['level']) . ' Only'];

        // Fetch children scopes based on admin level (using your helper functions)
        if ($data['level'] === 'global' || $data['level'] === 'national' || $data['level'] === 'diocese') {
            $archdeaconries = get_accessible_archdeaconries();
            foreach ($archdeaconries as $a) {
                $data['selectable_scopes'][] = ['level' => 'archdeaconry', 'id' => (int)$a['archdeaconry_id'], 'name' => 'Archdeaconry: ' . $a['archdeaconry_name']];
            }
        }
        if ($data['level'] !== 'parish') {
            $deaneries = get_accessible_deaneries();
            foreach ($deaneries as $dn) {
                $data['selectable_scopes'][] = ['level' => 'deanery', 'id' => (int)$dn['deanery_id'], 'name' => 'Deanery: ' . $dn['deanery_name']];
            }
            $parishes = get_accessible_parishes();
            foreach ($parishes as $p) {
                $data['selectable_scopes'][] = ['level' => 'parish', 'id' => (int)$p['parish_id'], 'name' => 'Parish: ' . $p['parish_name']];
            }
        }
    } else {
        // For Parish Admin, only allow viewing own parish
        $data['selectable_scopes'][] = ['level' => $data['level'], 'id' => (int)($data['id'] ?? 0), 'name' => 'My Parish Only'];
    }

    // Remove duplicates and sort for cleaner list
    $data['selectable_scopes'] = array_unique($data['selectable_scopes'], SORT_REGULAR);
    usort($data['selectable_scopes'], function($a, $b) {
        $level_order = ['global' => 0, 'national' => 1, 'diocese' => 2, 'archdeaconry' => 3, 'deanery' => 4, 'parish' => 5];
        $a_order = $level_order[$a['level']] ?? 9;
        $b_order = $level_order[$b['level']] ?? 9;
        if ($a_order !== $b_order) {
            return $a_order - $b_order;
        }
        return strcasecmp($a['name'], $b['name']);
    });

    return $data;
}


$scope_data = get_analytics_scope_data();
$base_scope_level = $scope_data['level'];
$base_scope_id = $scope_data['id'];
$can_select_scope = $scope_data['can_select_scope'];
$selectable_scopes = $scope_data['selectable_scopes'];

// Get selected scope from URL, default to user's base scope.
$selected_scope_level = isset($_GET['scope_level']) && !empty($_GET['scope_level']) ? trim($_GET['scope_level']) : $base_scope_level;
$selected_scope_id = (isset($_GET['scope_id']) && is_numeric($_GET['scope_id'])) ? (int)$_GET['scope_id'] : (int)$base_scope_id;

// Force scope back to user's base if they don't have permission to change it.
if (!$can_select_scope && ($selected_scope_level !== $base_scope_level || $selected_scope_id !== $base_scope_id)) {
    $selected_scope_level = $base_scope_level;
    $selected_scope_id = $base_scope_id;
}

// Adjust ID for 'global' level to be 0
if ($selected_scope_level === 'global') {
    $selected_scope_id = 0;
}

// ============================================
// ANALYTICS MENU STRUCTURE (80+ Queries Derived from DB)
// ============================================
$analytics_menu = [
    'demographics_core' => [
        'title' => '1. Core Demographics (8)',
        'icon' => 'fa-users',
        'queries' => [
            'age_gender_pyramid' => 'Age & Gender Pyramid (users)',
            'youth_segment' => 'Youth (13-35) & Children (0-12) Count',
            'senior_segment' => 'Senior Members (60+) Breakdown',
            'marital_status_split' => 'Members by Marital Status (user_details)',
            'education_level_distribution' => 'Members by Education Level (user_details)',
            'occupation_status_chart' => 'Members by Occupation Status (user_details)',
            'geographic_density_parish' => 'Population Density by Parish',
            'data_completeness_report' => 'Profile Data Completeness Scores',
        ]
    ],
    'spiritual_milestones' => [
        'title' => '2. Sacraments & Spirituality (11)',
        'icon' => 'fa-cross',
        'queries' => [
            'baptism_status_count' => 'Baptized vs. Unbaptized (sacrament_records)',
            'confirmation_status_count' => 'Confirmed vs. Unconfirmed (sacrament_records)',
            'sacrament_funnel_progress' => 'Baptism -> Confirmation Progress Funnel',
            'certificate_holders' => 'Members with Both Certificates',
            'need_baptism_followup' => 'Members Requiring Baptism Follow-up',
            'need_confirmation_followup' => 'Members Requiring Confirmation Follow-up',
            'spiritual_gifts_breakdown' => 'Spiritual Gifts Distribution (spiritual_gifts)',
            'spiritual_gifts_by_ministry' => 'Spiritual Gifts Mapped to Ministries',
            'clergy_by_orders' => 'Clergy Count by Holy Orders/Rank',
            'clergy_active_inactive' => 'Active vs. Inactive Clergy Roles',
            'clergy_marital_status' => 'Clergy by Marital Status (user_details)',
        ]
    ],
    'involvement_ministry' => [
        'title' => '3. Ministry Participation (12)',
        'icon' => 'fa-hands-praying',
        'queries' => [
            'ministry_participation_total' => 'Participation Count by Ministry (user_ministries)',
            'participation_by_age' => 'Ministry Involvement by Age Group',
            'multi_ministry_serving' => 'Members Serving in Multiple Ministries',
            'not_in_any_ministry' => 'Members Not in Any Ministry (Gap Analysis)',
            'ministry_staffing_levels' => 'Ministry Headcount vs. Optimal Size',
            'recent_ministry_joiners' => 'Recent Ministry Joiners (Last 90 Days)',
            'ministry_retention_rate' => 'Ministry Retention Rate (YTD)',
            'ministry_leaders_by_parish' => 'Count of Ministry Leaders by Parish',
            'ministry_gender_split' => 'Gender Ratio per Ministry',
            'department_participation' => 'Participation Count by Department',
            'department_leaders' => 'Count of Departmental Leaders',
            'ministry_turnover_rate' => 'Ministry Member Turnover Rate (Exit/Entry)',
        ]
    ],
    'involvement_groups' => [
        'title' => '4. Groups & Fellowships (9)',
        'icon' => 'fa-people-group',
        'queries' => [
            'cell_group_penetration' => 'Cell Group Penetration Rate (members vs groups)',
            'group_by_service_type' => 'Members by Service Type Attended (groups)',
            'members_by_cell_group' => 'Headcount per Cell Group',
            'not_in_any_cell_group' => 'Members NOT in Any Cell Group',
            'cell_group_leader_count' => 'Count of Cell Group Leaders',
            'cell_group_size_distribution' => 'Distribution of Cell Group Sizes',
            'group_attendance_rate' => 'Average Group Meeting Attendance Rate',
            'group_growth_trend' => 'New Cell Groups Formed (Monthly)',
            'group_geographic_cluster' => 'Cell Group Location Cluster Density',
        ]
    ],
    'stewardship' => [
        'title' => '5. Stewardship & Giving (10)',
        'icon' => 'fa-hand-holding-dollar',
        'queries' => [
            'total_tithes_by_month' => 'Total Tithes & Offerings (Monthly Trend)',
            'giving_method_breakdown' => 'Giving by Payment Method (tithes_and_offerings)',
            'unique_donor_count' => 'Monthly Count of Unique Donors',
            'donor_retention_rate' => 'Donor Retention Rate (YTD vs. Prior Year)',
            'first_time_givers' => 'Count of First-Time Givers (Last 90 Days)',
            'inactive_givers_list' => 'Givers Inactive for 6+ Months',
            'average_contribution_value' => 'Average Contribution Value (Monthly)',
            'top_20_givers_list' => 'Top 20 Donors by Annual Total',
            'givers_by_age_group' => 'Giving Participation by Age Segment',
            'per_capita_giving' => 'Average Giving per Active Member',
        ]
    ],
    'family_relations' => [
        'title' => '6. Family & Relationships (10)',
        'icon' => 'fa-house-user',
        'queries' => [
            'family_head_count' => 'Total Family Heads by Parish',
            'relationship_type_breakdown' => 'Breakdown by Relationship Type (user_relationships)',
            'single_parent_families' => 'Single Parent Families Count',
            'average_family_size' => 'Average Family Size (Members + Dependents)',
            'members_with_dependents' => 'Members with Dependents Under 18',
            'dependents_in_school' => 'Count of Dependents Registered in School',
            'dependent_to_member_conversion' => 'Dependents Converted to Full Members',
            'orphaned_dependents_count' => 'Count of Dependents without Two Parents (user_relationships)',
            'dependents_age_distribution' => 'Age Distribution of Dependents',
            'family_status_report' => 'Complete Family Status by Family Head ID',
        ]
    ],
    'pastoral_visitor' => [
        'title' => '7. Pastoral & Visitors (9)',
        'icon' => 'fa-heart-circle-plus',
        'queries' => [
            'visitor_conversion_rate' => 'Visitor-to-Member Conversion Rate (visitors)',
            'visitor_followup_status' => 'Visitor Follow-Up Status (Open vs. Closed)',
            'visitor_retention_trend' => 'Returning Visitors Trend (by visit count)',
            'visitor_source_breakdown' => 'Visitors by Source/Referral Method',
            'followup_team_performance' => 'Follow-Up Team Performance (By Assigned User)',
            'unassigned_followup_list' => 'List of Visitors with Unassigned Follow-Up',
            'needs_pastoral_care_active' => 'Active Members Flagged for Pastoral Care',
            'pastoral_care_requests_trend' => 'Pastoral Care Request Volume (Monthly)',
            'visitor_geographic_source' => 'Visitor Origin Location/Area',
        ]
    ],
    'events_attendance' => [
        'title' => '8. Events & Attendance (8)',
        'icon' => 'fa-calendar-check',
        'queries' => [
            'monthly_service_attendance' => 'Monthly Average Service Attendance Trend',
            'attendance_vs_membership' => 'Attendance Rate vs. Total Membership',
            'regular_attendees_ratio' => 'Regular Attendees Ratio (80%+)',
            'absentee_list_90days' => 'Members Absent for 90+ Days',
            'events_volume_by_type' => 'Events Hosted Volume by Type (events)',
            'events_attendance_vs_target' => 'Average Event Attendance vs. Target',
            'annual_theme_adherence' => 'Annual Theme Adherence Tracking',
            'event_satisfaction_scores' => 'Average Event Satisfaction Scores',
        ]
    ],
    'system_security' => [
        'title' => '9. System & Security (7)',
        'icon' => 'fa-server',
        'queries' => [
            'active_vs_inactive_users' => 'Active vs. Inactive System User Accounts',
            'audit_log_volume_trend' => 'Audit Log Volume Trend (Data Changes)',
            'data_changes_by_admin' => 'Data Changes Count by Admin Role',
            'user_access_log' => 'Last Login Time by User Role',
            'unverified_member_accounts' => 'Unverified Member Accounts Count',
            'roles_by_diocese_arch' => 'System Roles Count by Diocese/Archdeaconry',
            'log_in_attempts_failures' => 'Login Success/Failure Rate',
        ]
    ]
];

// Get selected category and query
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$query_type = isset($_GET['query']) ? trim($_GET['query']) : '';
$comparison_period = isset($_GET['compare']) ? trim($_GET['compare']) : 'none';
$view_mode = isset($_GET['view']) ? trim($_GET['view']) : 'table';

$page_title = "Members Analytics";

// Build the scope query string to append to all menu links
$scope_qstring = '';
if ($selected_scope_level) {
    $scope_qstring = '&scope_level=' . urlencode($selected_scope_level) . '&scope_id=' . urlencode($selected_scope_id);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Church Management</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../../css/analytics.css">
</head>
<body>
    <button class="btn btn-primary sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="analytics-sidebar" id="analyticsSidebar">
        <div class="sidebar-header">
            <h4>
                <i class="fas fa-chart-line me-2"></i>
                Members Analytics
            </h4>
            <small>Insights & Reports</small>
        </div>

        <div class="sidebar-menu">
            <?php foreach ($analytics_menu as $cat_key => $category_data): ?>
            <div class="menu-category">
                <div class="category-header <?php echo $category === $cat_key ? 'active' : ''; ?>" 
                     data-category="<?php echo $cat_key; ?>">
                    <span>
                        <i class="fas <?php echo $category_data['icon']; ?> category-icon"></i>
                        <?php echo htmlspecialchars($category_data['title']); ?>
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="submenu <?php echo $category === $cat_key ? 'show' : ''; ?>" id="submenu-<?php echo $cat_key; ?>">
                    <?php foreach ($category_data['queries'] as $query_key => $query_title): ?>
                    <a href="?category=<?php echo $cat_key; ?>&query=<?php echo $query_key; ?><?php echo $scope_qstring; ?>" 
                       class="submenu-item <?php echo ($category === $cat_key && $query_type === $query_key) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($query_title); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="main-content">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="list.php"><i class="fas fa-users"></i> Members</a>
                </li>
                <li class="breadcrumb-item active">Analytics</li>
            </ol>
        </nav>

        <?php if (empty($category) || empty($query_type)): ?>
        <div class="page-header">
            <h2 class="mb-3">
                <i class="fas fa-chart-pie me-2 text-primary"></i>
                Members Analytics Dashboard
            </h2>
            <p class="text-muted mb-4">
                Powerful insights into your church membership. Select a category from the sidebar to begin.
            </p>
            
            <?php if ($selected_scope_level && $selected_scope_level !== 'global'): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-shield-alt me-2"></i>
                You are currently viewing data scoped to the **<?php echo strtoupper($selected_scope_level); ?> (ID: <?php echo $selected_scope_id; ?>)**.
            </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">
                        <?php
                        // NOTE: This query MUST be updated to use $selected_scope_level and $selected_scope_id for filtering in a real setup.
                        try {
                            $total_query = "SELECT COUNT(*) FROM users u WHERE role_level = 'member'";
                            $scope_params = [];
                            
                            // Simple scope filtering logic
                            if ($selected_scope_level === 'parish' && $selected_scope_id) {
                                $total_query .= " AND u.parish_id = ?";
                                $scope_params[] = $selected_scope_id;
                            } elseif ($selected_scope_level === 'deanery' && $selected_scope_id) {
                                $total_query .= " AND u.deanery_id = ?";
                                $scope_params[] = $selected_scope_id;
                            } // ... and so on for other levels
                            
                            // Assuming $pdo is correctly initialized elsewhere
                            $stmt = $pdo->prepare($total_query);
                            $stmt->execute($scope_params);
                            $total_members = $stmt->fetchColumn();
                            echo number_format($total_members);
                        } catch (PDOException $e) {
                            echo "0";
                        }
                        ?>
                    </div>
                    <div class="stat-label">Total Members (Current Scope)</div>
                </div>

                <div class="stat-card" style="border-left-color: var(--success-color);">
                    <div class="stat-value" style="color: var(--success-color);">
                        <?php echo count($analytics_menu); ?>
                    </div>
                    <div class="stat-label">Query Categories</div>
                </div>

                <div class="stat-card" style="border-left-color: var(--warning-color);">
                    <div class="stat-value" style="color: var(--warning-color);">
                        <?php 
                        $total_queries = 0;
                        foreach ($analytics_menu as $cat) {
                            $total_queries += count($cat['queries']);
                        }
                        echo $total_queries;
                        ?>+
                    </div>
                    <div class="stat-label">Available Queries</div>
                </div>

                <div class="stat-card" style="border-left-color: var(--info-color);">
                    <div class="stat-value" style="color: var(--info-color);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-label">Visual Reports</div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Pro Tip:</strong> Use the sidebar to explore different member insights. 
                <?php if ($can_select_scope): ?>
                Use the **Data Scope** selector in the control panel when you select a query to switch your view.
                <?php endif; ?>
            </div>
        </div>

        <?php else: 
            // =========================================================================
            // *** FIX APPLIED HERE ***: Use null coalescing to safely get menu data
            // =========================================================================
            $current_category_data = $analytics_menu[$category] ?? ['icon' => 'fa-bug', 'title' => 'Error', 'queries' => []];
            $current_query_title = $current_category_data['queries'][$query_type] ?? 'Query Not Found';
            // =========================================================================
        ?>
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="mb-2">
                        <i class="fas <?php echo htmlspecialchars($current_category_data['icon']); ?> me-2 text-primary"></i>
                        <?php echo htmlspecialchars($current_query_title); ?>
                    </h2>
                    <p class="text-muted mb-0">
                        <small>Category: <?php echo htmlspecialchars($current_category_data['title']); ?></small>
                        <?php if ($selected_scope_level): ?>
                        <br><small class="text-danger">Active Scope: <?php echo strtoupper($selected_scope_level); ?> (ID: <?php echo $selected_scope_id ?? 'N/A'; ?>)</small>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <button class="btn btn-export" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Export to Excel
                    </button>
                </div>
            </div>
        </div>

        <div class="control-panel">
            <div class="control-group">

                <?php if (!empty($selectable_scopes)): ?>
                <div class="control-item control-item-scope">
                    <label class="form-label small text-muted">Data Scope</label>
                    <select class="form-select" id="scopeSelector" onchange="changeScope(this.value)">
                        <?php foreach ($selectable_scopes as $scope_item): 
                            // The value format is level_id (e.g., 'diocese_1', 'global_0')
                            $value = $scope_item['level'] . '_' . (int)($scope_item['id'] ?? 0);
                            $is_selected = ($selected_scope_level === $scope_item['level'] && (int)$selected_scope_id === (int)($scope_item['id'] ?? 0));
                            $selected = $is_selected ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($scope_item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="control-item">
                    <label class="form-label small text-muted">View Mode</label>
                    <select class="form-select" id="viewMode" onchange="changeView(this.value)">
                        <option value="both" <?php echo $view_mode === 'both' ? 'selected' : ''; ?>>Table + Chart</option>
                        <option value="chart" <?php echo $view_mode === 'chart' ? 'selected' : ''; ?>>Chart Only</option>
                        <option value="table" <?php echo $view_mode === 'table' ? 'selected' : ''; ?>>Table Only</option>
                    </select>
                </div>

                <div class="control-item">
                    <label class="form-label small text-muted">Compare With</label>
                    <select class="form-select" id="compareMode" onchange="changeComparison(this.value)">
                        <option value="none" <?php echo $comparison_period === 'none' ? 'selected' : ''; ?>>No Comparison</option>
                        <option value="last_month" <?php echo $comparison_period === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                        <option value="last_quarter" <?php echo $comparison_period === 'last_quarter' ? 'selected' : ''; ?>>Last Quarter</option>
                        <option value="last_year" <?php echo $comparison_period === 'last_year' ? 'selected' : ''; ?>>Last Year</option>
                        <option value="custom">Custom Period...</option>
                    </select>
                </div>

                <div class="control-item">
                    <label class="form-label small text-muted">Chart Type</label>
                    <select class="form-select" id="chartType">
                        <option value="bar">Bar Chart</option>
                        <option value="pie">Pie Chart</option>
                        <option value="line">Line Chart</option>
                        <option value="doughnut">Doughnut Chart</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-database"></i>
            </div>
            <h3 class="empty-state-title">Query Processing</h3>
            <p class="empty-state-text">
                The actual data query for "<?php echo htmlspecialchars($current_query_title); ?>" is active for the **<?php echo strtoupper($selected_scope_level); ?> (ID: <?php echo $selected_scope_id ?? 'N/A'; ?>)** scope.<br>
                **Action Required:** Implement the database query logic in a separate file using `$selected_scope_level` and `$selected_scope_id` to filter results.
            </p>
            <button class="btn btn-primary mt-3" onclick="alert('This will execute the database query and display results using the active scope parameters.')">
                <i class="fas fa-play me-2"></i>Run Query
            </button>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script src="../../js/analytics.js"></script> 
</body>
</html>