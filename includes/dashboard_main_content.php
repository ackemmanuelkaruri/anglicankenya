<!-- Main Content -->
<div class="main-content">
    <!-- Page Header -->
    <div class="page-header <?php echo $role_class; ?>">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">Dashboard Overview</h2>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
            </div>
            <div>
                <span class="text-muted">
                    <i class="fas fa-clock"></i> <?php echo date('l, F j, Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Scope Information -->
    <?php if ($role_level == 'diocese_admin'): ?>
        <div class="scope-info <?php echo $role_class; ?>">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Your Scope:</strong> You are managing <strong><?php echo htmlspecialchars($user['diocese_name']); ?></strong> 
            <small class="d-block mt-1 opacity-75">
                (1 of 38 dioceses in the Anglican Church of Kenya)
            </small>
        </div>
    <?php endif; ?>

    <!-- NEW: EVENTS PRIORITY SECTION (Moved to top) -->
    <div class="mt-4">
        <h5 class="mb-3">Upcoming Events</h5>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-icon primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['weekly_events'] ?? 0; ?></div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['user_rsvps'] ?? 0; ?></div>
                    <div class="stat-label">My RSVPs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-icon info">
                        <i class="fas fa-church"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['sunday_services'] ?? 0; ?></div>
                    <div class="stat-label">Services</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-icon warning">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['upcoming_reminders'] ?? 0; ?></div>
                    <div class="stat-label">Reminders</div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <a href="modules/events/index.php" class="btn btn-primary">
                <i class="fas fa-calendar-alt me-2"></i> View All Events
            </a>
            <?php if (can_create($_SESSION, 'event')): ?>
                <a href="modules/events/create_event.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i> Create New Event
                </a>
            <?php endif; ?>
            <a href="modules/events/user_preferences.php" class="btn btn-info">
                <i class="fas fa-bell me-2"></i> Notification Settings
            </a>
        </div>
    </div>

    <!-- COMPACT STATISTICS CARDS (Smaller, more efficient layout) -->
    <div class="mt-4">
        <h5 class="mb-3">Statistics Overview</h5>
        <div class="row">
            <!-- Role-based Statistics -->
            <?php if (in_array($role_level, ['super_admin', 'national_admin'])): ?>
                <!-- Super Admin & National Admin Stats (Compact) -->
                <div class="col-md-2">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon blue">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_provinces'] ?? 0); ?></div>
                        <div class="compact-stat-label">Provinces</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon green">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_dioceses'] ?? 0); ?></div>
                        <div class="compact-stat-label">Dioceses</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon purple">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_archdeaconries'] ?? 0); ?></div>
                        <div class="compact-stat-label">Archdeaconries</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_deaneries'] ?? 0); ?></div>
                        <div class="compact-stat-label">Deaneries</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_parishes'] ?? 0); ?></div>
                        <div class="compact-stat-label">Parishes</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon red">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                        <div class="compact-stat-label">Users</div>
                    </div>
                </div>
                
            <?php elseif ($role_level == 'diocese_admin'): ?>
                <!-- Diocese Admin Stats (Compact) -->
                <div class="col-md-3">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon purple">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_archdeaconries'] ?? 0); ?></div>
                        <div class="compact-stat-label">Archdeaconries</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_deaneries'] ?? 0); ?></div>
                        <div class="compact-stat-label">Deaneries</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_parishes'] ?? 0); ?></div>
                        <div class="compact-stat-label">Parishes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                        <div class="compact-stat-label">Users</div>
                    </div>
                </div>
                
            <?php elseif ($role_level == 'archdeaconry_admin'): ?>
                <!-- Archdeaconry Admin Stats (Compact) -->
                <div class="col-md-4">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_deaneries'] ?? 0); ?></div>
                        <div class="compact-stat-label">Deaneries</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_parishes'] ?? 0); ?></div>
                        <div class="compact-stat-label">Parishes</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                        <div class="compact-stat-label">Users</div>
                    </div>
                </div>
                
            <?php elseif ($role_level == 'deanery_admin'): ?>
                <!-- Deanery Admin Stats (Compact) -->
                <div class="col-md-6">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon teal">
                            <i class="fas fa-church"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_parishes'] ?? 0); ?></div>
                        <div class="compact-stat-label">Parishes</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                        <div class="compact-stat-label">Users</div>
                    </div>
                </div>
                
            <?php elseif ($role_level == 'parish_admin'): ?>
                <!-- Parish Admin Stats (Compact) -->
                <div class="col-md-4">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_members'] ?? 0); ?></div>
                        <div class="compact-stat-label">Membersssss</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon green">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_families'] ?? 0); ?></div>
                        <div class="compact-stat-label">Families</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon purple">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['total_ministries'] ?? 0); ?></div>
                        <div class="compact-stat-label">Ministries</div>
                    </div>
                </div>
                
            <?php else: // member ?>
                <!-- Member Stats (Compact) -->
                <div class="col-md-6">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon green">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['family_members'] ?? 0); ?></div>
                        <div class="compact-stat-label">Family Members</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="compact-stat-card">
                        <div class="compact-stat-icon purple">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div class="compact-stat-value"><?php echo number_format($stats['my_ministries'] ?? 0); ?></div>
                        <div class="compact-stat-label">My Ministries</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Giving Overview (Compact) -->
    <?php if (can_view($_SESSION, 'giving')): ?>
    <div class="mt-4">
        <h5 class="mb-3">Giving Overview</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="compact-stat-card">
                    <div class="compact-stat-icon primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="compact-stat-value">KES <?php echo number_format($stats['monthly_giving'] ?? 0, 2); ?></div>
                    <div class="compact-stat-label">This Month</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="compact-stat-card">
                    <div class="compact-stat-icon success">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="compact-stat-value"><?php echo $stats['active_campaigns'] ?? 0; ?></div>
                    <div class="compact-stat-label">Active Campaigns</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="compact-stat-card">
                    <div class="compact-stat-icon info">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <div class="compact-stat-value">KES <?php echo number_format($stats['user_giving'] ?? 0, 2); ?></div>
                    <div class="compact-stat-label">My Giving</div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <a href="modules/giving/index.php" class="btn btn-outline-primary">
                <i class="fas fa-hand-holding-heart me-2"></i> View Giving Dashboard
            </a>
            <?php if (is_parish_admin() || is_diocese_admin()): ?>
                <a href="modules/giving/admin/index.php" class="btn btn-outline-success">
                    <i class="fas fa-cog me-2"></i> Admin Giving
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions (Compact) -->
    <div class="mt-4">
        <h5 class="mb-3">Quick Actions</h5>
        <div class="row">
            <!-- Events Actions (Priority) -->
            <div class="col-md-2">
                <a href="modules/events/index.php" class="quick-action-card-compact <?php echo $role_class; ?>">
                    <i class="fas fa-calendar-alt text-primary"></i>
                    <p class="mb-0 mt-1">Events</p>
                </a>
            </div>
            
            <?php if (can_create($_SESSION, 'event')): ?>
            <div class="col-md-2">
                <a href="modules/events/create_event.php" class="quick-action-card-compact <?php echo $role_class; ?>">
                    <i class="fas fa-plus-circle text-success"></i>
                    <p class="mb-0 mt-1">Create Event</p>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <a href="modules/events/user_preferences.php" class="quick-action-card-compact <?php echo $role_class; ?>">
                    <i class="fas fa-bell text-info"></i>
                    <p class="mb-0 mt-1">Notifications</p>
                </a>
            </div>
            
            <!-- Original Quick Actions -->
            <?php if (can_create($_SESSION, 'user')): ?>
                <div class="col-md-2">
                    <a href="modules/users/create_user.php" class="quick-action-card-compact <?php echo $role_class; ?>">
                        <i class="fas fa-user-plus text-primary"></i>
                        <p class="mb-0 mt-1">Add User</p>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (can_view($_SESSION, 'reports')): ?>
                <div class="col-md-2">
                    <a href="reports.php" class="quick-action-card-compact <?php echo $role_class; ?>">
                        <i class="fas fa-file-alt text-success"></i>
                        <p class="mb-0 mt-1">Reports</p>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <a href="modules/members/profile.php" class="quick-action-card-compact <?php echo $role_class; ?>">
                    <i class="fas fa-id-card text-success"></i>
                    <p class="mb-0 mt-1">Profile</p>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="js/dashboard.js"></script>