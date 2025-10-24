<div class="row">
    <?php if (in_array($role_level, ['super_admin', 'national_admin'])): ?>
        <!-- Super Admin & National Admin Stats -->
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-globe"></i>
                </div>
                <h4><?php echo number_format($stats['total_provinces'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Provinces</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-green">
                    <i class="fas fa-building"></i>
                </div>
                <h4><?php echo number_format($stats['total_dioceses'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Dioceses</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h4><?php echo number_format($stats['total_archdeaconries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Archdeaconries</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-orange">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h4><?php echo number_format($stats['total_deaneries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Deaneries</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-teal">
                    <i class="fas fa-church"></i>
                </div>
                <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Parishes</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-red">
                    <i class="fas fa-users"></i>
                </div>
                <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>
    
    <?php elseif ($role_level == 'diocese_admin'): ?>
        <!-- Diocese Admin Stats -->
        <div class="col-md-3">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h4><?php echo number_format($stats['total_archdeaconries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Archdeaconries</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-orange">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h4><?php echo number_format($stats['total_deaneries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Deaneries</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-teal">
                    <i class="fas fa-church"></i>
                </div>
                <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Parishes</p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>
    
    <?php elseif ($role_level == 'archdeaconry_admin'): ?>
        <!-- Archdeaconry Admin Stats -->
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-orange">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h4><?php echo number_format($stats['total_deaneries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Deaneries</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-teal">
                    <i class="fas fa-church"></i>
                </div>
                <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Parishes</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>
    
    <?php elseif ($role_level == 'deanery_admin'): ?>
        <!-- Deanery Admin Stats -->
        <div class="col-md-6">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-teal">
                    <i class="fas fa-church"></i>
                </div>
                <h4><?php echo number_format($stats['total_parishes'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Parishes</p>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <h4><?php echo number_format($stats['total_users'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>
    
    <?php elseif ($role_level == 'parish_admin'): ?>
        <!-- Parish Admin Stats -->
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <h4><?php echo number_format($stats['total_members'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Members</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-green">
                    <i class="fas fa-home"></i>
                </div>
                <h4><?php echo number_format($stats['total_families'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Families</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h4><?php echo number_format($stats['total_ministries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Ministries</p>
            </div>
        </div>
    
    <?php else: // member ?>
        <!-- Member Stats -->
        <div class="col-md-6">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-green">
                    <i class="fas fa-home"></i>
                </div>
                <h4><?php echo number_format($stats['family_members'] ?? 0); ?></h4>
                <p class="text-muted mb-0">Family Members</p>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stat-card <?php echo $role_class; ?>">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h4><?php echo number_format($stats['my_ministries'] ?? 0); ?></h4>
                <p class="text-muted mb-0">My Ministries</p>
            </div>
        </div>
    <?php endif; ?>
</div>