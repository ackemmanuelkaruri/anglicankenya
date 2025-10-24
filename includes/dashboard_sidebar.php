<?php
/**
 * ============================================
 * DASHBOARD SIDEBAR NAVIGATION - WITH EVENTS MODULE
 * Fixes: Array offset on null warnings by using null coalescing (??)
 * ============================================
 */

// Determine base path based on current file location
 $current_path = $_SERVER['PHP_SELF'];
 $base_path = '';

// Count how many directories deep we are from root
if (strpos($current_path, '/modules/') !== false) {
    $base_path = '../../';
} else {
    $base_path = './';
}

// Determine active page
 $current_page = basename($_SERVER['PHP_SELF']);
 $is_dashboard = ($current_page === 'dashboard.php');
 $is_members = (strpos($current_path, '/modules/members/') !== false);
 $is_families = (strpos($current_path, '/modules/families/') !== false);
 $is_leadership = (strpos($current_path, '/modules/leadership/') !== false);
 $is_ministries = (strpos($current_path, '/modules/ministries/') !== false);
 $is_giving = (strpos($current_path, '/modules/giving/') !== false);
 $is_events = (strpos($current_path, '/modules/events/') !== false); // NEW: Events detection
?>

<div class="sidebar">
    
    <a href="<?php echo $base_path; ?>modules/members/profile.php" style="text-decoration: none;">
        <div class="user-info text-center <?php echo $role_class; ?>">
            <div class="user-details">
                <?php 
                    // ✅ FIX for Line 54 Warning: Safely access user data, defaulting to empty string if $user or key is null
                    $first_name = $user['first_name'] ?? '';
                    $last_name = $user['last_name'] ?? '';
                    $display_name = htmlspecialchars(trim($first_name . ' ' . $last_name) ?: 'Guest User');

                    // ✅ FIX for Line 55 Warning & Deprecated Notice: Safely access role, default to 'Member' if null
                    $role_level = $user['role_level'] ?? $_SESSION['role_level'] ?? 'member';
                    $display_role = htmlspecialchars(ucwords(str_replace('_', ' ', $role_level)));
                ?>
                <h5 class="mb-0 text-white"><?php echo $display_name; ?></h5> 
                <p class="role-text"><?php echo $display_role; ?></p>
            </div>
        </div>
    </a>

    <div class="sidebar-menu">
        <a class="nav-link <?php echo $is_dashboard ? 'active' : ''; ?>" href="<?php echo $base_path; ?>dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <?php if (can_view($_SESSION, 'member')): ?>
            <a class="nav-link <?php echo $is_members ? 'active' : ''; ?>" href="<?php echo $base_path; ?>modules/users/analytics_dashboard.php">
                <i class="fas fa-users"></i> Analytics
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'family')): ?>
            <a class="nav-link <?php echo $is_families ? 'active' : ''; ?>" href="<?php echo $base_path; ?>modules/families/list.php">
                <i class="fas fa-home"></i> Families
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'leadership')): ?>
            <a class="nav-link <?php echo $is_leadership ? 'active' : ''; ?>" href="<?php echo $base_path; ?>modules/leadership/list.php">
                <i class="fas fa-user-tie"></i> Leadership
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'ministry')): ?>
            <a class="nav-link <?php echo $is_ministries ? 'active' : ''; ?>" href="<?php echo $base_path; ?>modules/ministries/list.php">
                <i class="fas fa-hands-helping"></i> Ministries
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'giving')): ?>
            <a class="nav-link <?php echo $is_giving ? 'active' : ''; ?>" href="<?php echo $base_path; ?>modules/giving/index.php">
                <i class="fas fa-hand-holding-usd"></i> Giving
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'event')): ?>
            <a class="nav-link <?php echo $is_events ? 'active' : ''; ?>" href="<?php echo $base_path; ?>modules/events/index.php">
                <i class="fas fa-calendar-alt"></i> Events
            </a>
        <?php endif; ?>

        <?php if (can_view($_SESSION, 'province')): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Administration</div>
        
            <a class="nav-link" href="<?php echo $base_path; ?>manage-provinces.php">
                <i class="fas fa-globe"></i> Manage Provinces
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'diocese')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>manage-dioceses.php">
                <i class="fas fa-building"></i> Manage Dioceses
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'archdeaconry')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>manage-archdeaconries.php">
                <i class="fas fa-layer-group"></i> Manage Archdeaconries
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'deanery')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>manage-deaneries.php">
                <i class="fas fa-sitemap"></i> Manage Deaneries
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'parish')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>manage-parishes.php">
                <i class="fas fa-church"></i> Manage Parishes
            </a>
        <?php endif; ?>
        
        <a class="nav-link" href="<?php echo $base_path; ?>modules/users/list.php">
            <i class="fas fa-users"></i> <?php echo ($role_level == 'member') ? 'My Family' : 'Manage Users'; ?>
        </a>
        
        <?php if (can_view($_SESSION, 'reports')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>modules/events/email_logs.php">
                <i class="fas fa-envelope-open-text"></i> Email Logs
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'reports')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>reports.php">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        <?php endif; ?>
        
        <?php if (can_view($_SESSION, 'settings')): ?>
            <a class="nav-link" href="<?php echo $base_path; ?>settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
        <?php endif; ?>
        
        <a class="nav-link" href="<?php echo $base_path; ?>logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>