<div class="dashboard-container">
    <!-- Statistics Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-users text-primary"></i>
                <h3 id="total-members-count">-</h3>
                <p>Total Members</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-user-shield text-success"></i>
                <h3 id="leadership-count">-</h3>
                <p>Leadership Roles</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-church text-warning"></i>
                <h3 id="clergy-count">-</h3>
                <p>Clergy Members</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-sitemap text-info"></i>
                <h3 id="family-count">-</h3>
                <p>Family Units</p>
            </div>
        </div>
    </div>
    
    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="status-filter">Status Filter</label>
                    <select class="form-control" id="status-filter">
                        <option value="all">All</option>
                        <option value="current">Current</option>
                        <option value="previous">Previous</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="role-filter">Role Filter</label>
                    <select class="form-control" id="role-filter">
                        <option value="all">All Roles</option>
                        <option value="pcc">PCC</option>
                        <option value="department">Department Head</option>
                        <option value="clergy">Clergy</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button class="btn-refresh" id="refresh-data">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leadership Section -->
    <h2 class="section-title">Leadership Information</h2>
    <div class="row">
        <!-- PCC Members Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-users"></i> PCC Members</h3>
                <div id="pcc-members-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Department Heads Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-building"></i> Department Heads</h3>
                <div id="department-heads-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Clergy Members Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-cross"></i> Clergy Members</h3>
                <div id="clergy-members-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Leadership History Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-history"></i> Leadership History</h3>
                <div id="leadership-history-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Family Section -->
    <h2 class="section-title">Family Relationships</h2>
    <div class="row">
        <!-- Parent-Child Relationships Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-child"></i> Parent-Child Relationships</h3>
                <div id="parent-child-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Spouse Relationships Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-heart"></i> Spouse Relationships</h3>
                <div id="spouse-relationships-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Family Units Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-home"></i> Family Units</h3>
                <div id="family-units-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Extended Family Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-sitemap"></i> Extended Family</h3>
                <div id="extended-family-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Special Categories Section -->
    <h2 class="section-title">Special Categories</h2>
    <div class="row">
        <!-- Single Parents Box -->
        <div class="col-md-4">
            <div class="dashboard-box">
                <h3><i class="fas fa-user-friends"></i> Single Parents</h3>
                <div id="single-parents-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Youth Members Box -->
        <div class="col-md-4">
            <div class="dashboard-box">
                <h3><i class="fas fa-user-graduate"></i> Youth Members</h3>
                <div id="youth-members-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Senior Members Box -->
        <div class="col-md-4">
            <div class="dashboard-box">
                <h3><i class="fas fa-user"></i> Senior Members</h3>
                <div id="senior-members-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Sections -->
    <h2 class="section-title">Additional Information</h2>
    <div class="row">
        <!-- Ministry Members Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-pray"></i> Ministry Members</h3>
                <div id="ministry-members-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
        
        <!-- Employment History Box -->
        <div class="col-md-6">
            <div class="dashboard-box">
                <h3><i class="fas fa-briefcase"></i> Employment History</h3>
                <div id="employment-history-container" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-container {
        padding: 20px;
    }
    
    .dashboard-box {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .dashboard-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-box h3 {
        color: #2c3e50;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    
    .dashboard-box h3 i {
        margin-right: 10px;
        color: #3498db;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th, .data-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .data-table th {
        background-color: #f2f2f2;
        font-weight: 600;
    }
    
    .data-table tr:hover {
        background-color: #f5f5f5;
    }
    
    .status-current {
        color: #27ae60;
        font-weight: bold;
    }
    
    .status-previous {
        color: #e74c3c;
    }
    
    .loading {
        text-align: center;
        padding: 20px;
    }
    
    .loading i {
        font-size: 24px;
        color: #3498db;
    }
    
    .error-message {
        color: #e74c3c;
        text-align: center;
        padding: 15px;
        background-color: #fadbd8;
        border-radius: 5px;
        margin: 10px 0;
    }
    
    .stats-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-card i {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
    
    .stats-card h3 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .stats-card p {
        margin: 5px 0 0;
        color: #7f8c8d;
    }
    
    .section-title {
        margin: 30px 0 20px;
        color: #2c3e50;
        font-weight: 600;
        position: relative;
        padding-left: 15px;
    }
    
    .section-title:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 5px;
        background-color: #3498db;
        border-radius: 3px;
    }
    
    .filter-controls {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .filter-controls .form-group {
        margin-bottom: 15px;
    }
    
    .btn-refresh {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn-refresh:hover {
        background-color: #2980b9;
    }
    
    .no-data {
        text-align: center;
        padding: 20px;
        color: #7f8c8d;
    }
    
    .no-data i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #bdc3c7;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../js/admin-dashboard.js"></script>
    
    function createPCCTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Years Served</th></tr></thead><tbody>';
        
        data.forEach(function(member) {
            html += '<tr>';
            html += '<td>' + member.member_name + '</td>';
            html += '<td>' + member.pcc_role + '</td>';
            html += '<td class="' + (member.status === 'Current' ? 'status-current' : 'status-previous') + '">' + member.status + '</td>';
            html += '<td>' + member.years_served + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createDepartmentHeadsTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Department</th><th>Head</th><th>Contact</th></tr></thead><tbody>';
        
        data.forEach(function(dept) {
            html += '<tr>';
            html += '<td>' + dept.department_name + '</td>';
            html += '<td>' + dept.head_name + '</td>';
            html += '<td>' + dept.email + '<br>' + dept.phone_number + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createClergyTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Role</th><th>Parish</th><th>Status</th></tr></thead><tbody>';
        
        data.forEach(function(clergy) {
            html += '<tr>';
            html += '<td>' + clergy.member_name + '</td>';
            html += '<td>' + clergy.clergy_role + '</td>';
            html += '<td>' + clergy.parish + '</td>';
            html += '<td class="' + (clergy.status === 'Current' ? 'status-current' : 'status-previous') + '">' + clergy.status + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createLeadershipHistoryTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Role</th><th>Department</th><th>From</th><th>To</th></tr></thead><tbody>';
        
        data.forEach(function(leader) {
            html += '<tr>';
            html += '<td>' + leader.leader_name + '</td>';
            html += '<td>' + leader.role + '</td>';
            html += '<td>' + leader.department + '</td>';
            html += '<td>' + leader.from_date + '</td>';
            html += '<td>' + (leader.to_date || 'Present') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createParentChildTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Parent</th><th>Child</th><th>Age</th><th>Contact</th></tr></thead><tbody>';
        
        data.forEach(function(rel) {
            html += '<tr>';
            html += '<td>' + rel.parent_name + '</td>';
            html += '<td>' + rel.child_name + '</td>';
            html += '<td>' + rel.child_age + '</td>';
            html += '<td>' + rel.parent_email + '<br>' + rel.parent_phone + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createSpouseTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Spouse 1</th><th>Spouse 2</th><th>Marriage Date</th><th>Wedding Type</th></tr></thead><tbody>';
        
        data.forEach(function(rel) {
            html += '<tr>';
            html += '<td>' + rel.spouse1_name + '</td>';
            html += '<td>' + rel.spouse2_name + '</td>';
            html += '<td>' + rel.marriage_date + '</td>';
            html += '<td>' + rel.wedding_type + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createFamilyUnitsTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Parent</th><th>Children</th><th>Contact</th></tr></thead><tbody>';
        
        data.forEach(function(family) {
            html += '<tr>';
            html += '<td>' + family.parent_name + '</td>';
            html += '<td>' + family.children_names + '</td>';
            html += '<td>' + family.parent_email + '<br>' + family.parent_phone + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createExtendedFamilyTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Person 1</th><th>Person 2</th><th>Relationship</th></tr></thead><tbody>';
        
        data.forEach(function(rel) {
            html += '<tr>';
            html += '<td>' + rel.person1_name + '</td>';
            html += '<td>' + rel.person2_name + '</td>';
            html += '<td>' + rel.relationship_type + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createSingleParentsTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Children</th><th>Contact</th><th>Leadership Role</th></tr></thead><tbody>';
        
        data.forEach(function(parent) {
            html += '<tr>';
            html += '<td>' + parent.parent_name + '</td>';
            html += '<td>' + parent.children_names + '</td>';
            html += '<td>' + parent.parent_email + '<br>' + parent.parent_phone + '</td>';
            html += '<td>' + (parent.leadership_role || 'None') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createYouthTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Age</th><th>Contact</th><th>Service</th></tr></thead><tbody>';
        
        data.forEach(function(youth) {
            html += '<tr>';
            html += '<td>' + youth.name + '</td>';
            html += '<td>' + youth.age + '</td>';
            html += '<td>' + youth.email + '<br>' + youth.phone_number + '</td>';
            html += '<td>' + (youth.service_attending || 'N/A') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createSeniorsTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Age</th><th>Contact</th><th>Service</th></tr></thead><tbody>';
        
        data.forEach(function(senior) {
            html += '<tr>';
            html += '<td>' + senior.name + '</td>';
            html += '<td>' + senior.age + '</td>';
            html += '<td>' + senior.email + '<br>' + senior.phone_number + '</td>';
            html += '<td>' + (senior.service_attending || 'N/A') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createMinistryTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Ministry</th><th>Department</th><th>Service</th></tr></thead><tbody>';
        
        data.forEach(function(member) {
            html += '<tr>';
            html += '<td>' + member.member_name + '</td>';
            html += '<td>' + (member.ministry_name || 'N/A') + '</td>';
            html += '<td>' + (member.department_name || 'N/A') + '</td>';
            html += '<td>' + (member.service_attending || 'N/A') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function createEmploymentTable(data) {
        let html = '<table class="data-table"><thead><tr><th>Name</th><th>Job Title</th><th>Company</th><th>Status</th></tr></thead><tbody>';
        
        data.forEach(function(employee) {
            html += '<tr>';
            html += '<td>' + employee.employee_name + '</td>';
            html += '<td>' + employee.job_title + '</td>';
            html += '<td>' + employee.company + '</td>';
            html += '<td class="' + (employee.status === 'Current' ? 'status-current' : 'status-previous') + '">' + employee.status + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function applyFilters() {
        const statusFilter = $('#status-filter').val();
        const roleFilter = $('#role-filter').val();
        
        // Apply filters to visible tables
        $('.data-table tbody tr').each(function() {
            const status = $(this).find('td:nth-child(3)').text();
            const role = $(this).find('td:nth-child(2)').text();
            
            let show = true;
            
            if (statusFilter !== 'all' && status !== statusFilter) {
                show = false;
            }
            
            if (roleFilter !== 'all' && !role.toLowerCase().includes(roleFilter)) {
                show = false;
            }
            
            if (show) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
</script>