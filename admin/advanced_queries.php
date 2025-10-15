<?php
session_start();
require_once '../db.php';
require_once 'functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// Initialize variables
$results = [];
$queryExecuted = false;
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $queryType = $_POST['query_type'] ?? '';
        
        switch ($queryType) {
            case 'age_range':
                $minAge = $_POST['min_age'] ?? 0;
                $maxAge = $_POST['max_age'] ?? 100;
                
                $stmt = $pdo->prepare("
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS age,
                        u.marital_status,
                        u.occupation,
                        u.service_attending,
                        u.kikuyu_cell_group,
                        u.family_group
                    FROM users u
                    WHERE TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) BETWEEN ? AND ?
                    ORDER BY age
                ");
                $stmt->execute([$minAge, $maxAge]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Members between ages $minAge and $maxAge";
                break;
                
            case 'marital_status':
                $maritalStatus = $_POST['marital_status'] ?? '';
                
                $stmt = $pdo->prepare("
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        u.marital_status,
                        u.occupation,
                        u.service_attending,
                        u.kikuyu_cell_group,
                        u.family_group
                    FROM users u
                    WHERE u.marital_status = ?
                    ORDER BY u.last_name, u.first_name
                ");
                $stmt->execute([$maritalStatus]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Members with marital status: $maritalStatus";
                break;
                
            case 'spiritual_status':
                $baptized = $_POST['baptized'] ?? '';
                $confirmed = $_POST['confirmed'] ?? '';
                $baptismInterest = $_POST['baptism_interest'] ?? '';
                $confirmationInterest = $_POST['confirmation_interest'] ?? '';
                
                $sql = "
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        u.baptized,
                        u.confirmed,
                        u.baptism_interest,
                        u.confirmation_interest,
                        u.service_attending,
                        u.kikuyu_cell_group,
                        u.family_group
                    FROM users u
                    WHERE 1=1
                ";
                
                $params = [];
                
                if (!empty($baptized)) {
                    $sql .= " AND u.baptized = ?";
                    $params[] = $baptized;
                }
                
                if (!empty($confirmed)) {
                    $sql .= " AND u.confirmed = ?";
                    $params[] = $confirmed;
                }
                
                if (!empty($baptismInterest)) {
                    $sql .= " AND u.baptism_interest = ?";
                    $params[] = $baptismInterest;
                }
                
                if (!empty($confirmationInterest)) {
                    $sql .= " AND u.confirmation_interest = ?";
                    $params[] = $confirmationInterest;
                }
                
                $sql .= " ORDER BY u.last_name, u.first_name";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Members with spiritual status criteria";
                break;
                
            case 'department_involvement':
                $departmentId = $_POST['department_id'] ?? '';
                
                $sql = "
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        d.name AS department_name,
                        ul.role,
                        ul.from_date AS join_date,
                        ul.is_current
                    FROM user_leadership ul
                    JOIN users u ON ul.user_id = u.id
                    JOIN departments d ON ul.department = d.name
                    WHERE 1=1
                ";
                
                $params = [];
                
                if (!empty($departmentId)) {
                    $sql .= " AND d.id = ?";
                    $params[] = $departmentId;
                }
                
                $sql .= " ORDER BY d.name, u.last_name, u.first_name";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Department involvement";
                break;
                
            case 'ministry_involvement':
                $ministryId = $_POST['ministry_id'] ?? '';
                
                $sql = "
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        m.name AS ministry_name,
                        umd.department_name,
                        u.service_attending
                    FROM user_ministry_department umd
                    JOIN users u ON umd.user_id = u.id
                    JOIN ministries m ON umd.ministry_name = m.name
                    WHERE 1=1
                ";
                
                $params = [];
                
                if (!empty($ministryId)) {
                    $sql .= " AND m.id = ?";
                    $params[] = $ministryId;
                }
                
                $sql .= " ORDER BY m.name, u.last_name, u.first_name";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Ministry involvement";
                break;
                
            case 'leadership_roles':
                $role = $_POST['role'] ?? '';
                $department = $_POST['department'] ?? '';
                $isCurrent = $_POST['is_current'] ?? '';
                
                $sql = "
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        ul.role,
                        ul.department,
                        ul.from_date,
                        ul.to_date,
                        ul.is_current
                    FROM user_leadership ul
                    JOIN users u ON ul.user_id = u.id
                    WHERE 1=1
                ";
                
                $params = [];
                
                if (!empty($role)) {
                    $sql .= " AND ul.role LIKE ?";
                    $params[] = '%' . $role . '%';
                }
                
                if (!empty($department)) {
                    $sql .= " AND ul.department LIKE ?";
                    $params[] = '%' . $department . '%';
                }
                
                if ($isCurrent !== '') {
                    $sql .= " AND ul.is_current = ?";
                    $params[] = $isCurrent;
                }
                
                $sql .= " ORDER BY ul.from_date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Leadership roles";
                break;
                
            case 'family_relationships':
                $relationshipType = $_POST['relationship_type'] ?? '';
                
                $sql = "
                    SELECT 
                        CASE 
                            WHEN fr.user1_id IS NOT NULL THEN CONCAT(u1.first_name, ' ', u1.last_name)
                            WHEN fr.parent_id IS NOT NULL THEN CONCAT(parent.first_name, ' ', parent.last_name)
                        END AS person1_name,
                        CASE 
                            WHEN fr.user2_id IS NOT NULL THEN CONCAT(u2.first_name, ' ', u2.last_name)
                            WHEN fr.child_id IS NOT NULL THEN CONCAT(child.first_name, ' ', child.last_name)
                        END AS person2_name,
                        fr.relationship_type,
                        fr.relationship_description,
                        fr.marriage_date,
                        fr.wedding_type
                    FROM family_relationships fr
                    LEFT JOIN users u1 ON fr.user1_id = u1.id
                    LEFT JOIN users u2 ON fr.user2_id = u2.id
                    LEFT JOIN users parent ON fr.parent_id = parent.id
                    LEFT JOIN users child ON fr.child_id = child.id
                    WHERE 1=1
                ";
                
                $params = [];
                
                if (!empty($relationshipType)) {
                    $sql .= " AND fr.relationship_type = ?";
                    $params[] = $relationshipType;
                }
                
                $sql .= " ORDER BY fr.relationship_type, person1_name";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Family relationships";
                break;
                
            case 'employment_status':
                $jobTitle = $_POST['job_title'] ?? '';
                $isCurrent = $_POST['is_current'] ?? '';
                
                $sql = "
                    SELECT 
                        CONCAT(u.first_name, ' ', u.last_name) AS name,
                        u.email,
                        u.phone_number,
                        eh.job_title,
                        eh.company,
                        eh.from_date,
                        eh.to_date,
                        eh.is_current
                    FROM employment_history eh
                    JOIN users u ON eh.user_id = u.id
                    WHERE 1=1
                ";
                
                $params = [];
                
                if (!empty($jobTitle)) {
                    $sql .= " AND eh.job_title LIKE ?";
                    $params[] = '%' . $jobTitle . '%';
                }
                
                if ($isCurrent !== '') {
                    $sql .= " AND eh.is_current = ?";
                    $params[] = $isCurrent;
                }
                
                $sql .= " ORDER BY eh.from_date DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $queryTitle = "Employment status";
                break;
                
            default:
                $error = "Please select a valid query type.";
                break;
        }
        
        $queryExecuted = true;
    } catch (Exception $e) {
        $error = "Error executing query: " . $e->getMessage();
    }
}

// Get all departments for dropdown
$departments = getAllDepartments();

// Get all ministries for dropdown
$ministries = getAllMinistries();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Queries - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background-image: url('../img/face.jpg'); 
            background-size: cover; 
            background-attachment: fixed;
        }
        .dashboard-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .results-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .query-option {
            display: none;
        }
        .query-option.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-info" style="min-height: 100vh; margin-left: -30px;">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action bg-info text-white text-center">Dashboard</a>
                    <a href="administrators.php" class="list-group-item list-group-item-action bg-info text-white text-center">Administrator</a>
                    <a href="users.php" class="list-group-item list-group-item-action bg-info text-white text-center">Users</a>
                    <a href="parishioners.php" class="list-group-item list-group-item-action bg-info text-white text-center">Parishioners</a>
                    <a href="advanced_queries.php" class="list-group-item list-group-item-action bg-info text-white text-center active">Advanced Queries</a>
                </div>
            </div>
            <div class="col-md-10">
                <div class="dashboard-container">
                    <h2 class="text-center mt-2">Advanced Query System</h2>
                    
                    <!-- Query Form -->
                    <div class="form-section">
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="query_type" class="form-label">Select Query Type</label>
                                        <select class="form-select" id="query_type" name="query_type" required>
                                            <option value="">-- Select Query Type --</option>
                                            <option value="age_range">Age Range</option>
                                            <option value="marital_status">Marital Status</option>
                                            <option value="spiritual_status">Spiritual Status</option>
                                            <option value="department_involvement">Department Involvement</option>
                                            <option value="ministry_involvement">Ministry Involvement</option>
                                            <option value="leadership_roles">Leadership Roles</option>
                                            <option value="family_relationships">Family Relationships</option>
                                            <option value="employment_status">Employment Status</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary w-100">Execute Query</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Age Range Options -->
                            <div id="age_range_options" class="query-option">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="min_age" class="form-label">Minimum Age</label>
                                            <input type="number" class="form-control" id="min_age" name="min_age" min="0" max="120" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_age" class="form-label">Maximum Age</label>
                                            <input type="number" class="form-control" id="max_age" name="max_age" min="0" max="120" value="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Marital Status Options -->
                            <div id="marital_status_options" class="query-option">
                                <div class="mb-3">
                                    <label for="marital_status" class="form-label">Marital Status</label>
                                    <select class="form-select" id="marital_status" name="marital_status">
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="divorced">Divorced</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="single parent">Single Parent</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Spiritual Status Options -->
                            <div id="spiritual_status_options" class="query-option">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="baptized" class="form-label">Baptized</label>
                                            <select class="form-select" id="baptized" name="baptized">
                                                <option value="">-- Any --</option>
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirmed" class="form-label">Confirmed</label>
                                            <select class="form-select" id="confirmed" name="confirmed">
                                                <option value="">-- Any --</option>
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="baptism_interest" class="form-label">Baptism Interest</label>
                                            <select class="form-select" id="baptism_interest" name="baptism_interest">
                                                <option value="">-- Any --</option>
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirmation_interest" class="form-label">Confirmation Interest</label>
                                            <select class="form-select" id="confirmation_interest" name="confirmation_interest">
                                                <option value="">-- Any --</option>
                                                <option value="yes">Yes</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Department Involvement Options -->
                            <div id="department_involvement_options" class="query-option">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">Department</label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">-- All Departments --</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?php echo $department['id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Ministry Involvement Options -->
                            <div id="ministry_involvement_options" class="query-option">
                                <div class="mb-3">
                                    <label for="ministry_id" class="form-label">Ministry</label>
                                    <select class="form-select" id="ministry_id" name="ministry_id">
                                        <option value="">-- All Ministries --</option>
                                        <?php foreach ($ministries as $ministry): ?>
                                            <option value="<?php echo $ministry['id']; ?>"><?php echo htmlspecialchars($ministry['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Leadership Roles Options -->
                            <div id="leadership_roles_options" class="query-option">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <input type="text" class="form-control" id="role" name="role" placeholder="Enter role (partial match)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="department" class="form-label">Department</label>
                                            <input type="text" class="form-control" id="department" name="department" placeholder="Enter department (partial match)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="is_current" class="form-label">Status</label>
                                            <select class="form-select" id="is_current" name="is_current">
                                                <option value="">-- Any --</option>
                                                <option value="1">Current</option>
                                                <option value="0">Previous</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Family Relationships Options -->
                            <div id="family_relationships_options" class="query-option">
                                <div class="mb-3">
                                    <label for="relationship_type" class="form-label">Relationship Type</label>
                                    <select class="form-select" id="relationship_type" name="relationship_type">
                                        <option value="">-- All Types --</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="parent-child">Parent-Child</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Employment Status Options -->
                            <div id="employment_status_options" class="query-option">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="job_title" class="form-label">Job Title</label>
                                            <input type="text" class="form-control" id="job_title" name="job_title" placeholder="Enter job title (partial match)">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="is_current" class="form-label">Status</label>
                                            <select class="form-select" id="is_current" name="is_current">
                                                <option value="">-- Any --</option>
                                                <option value="1">Current</option>
                                                <option value="0">Previous</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Section -->
                    <?php if ($queryExecuted): ?>
                        <div class="results-section">
                            <h3><?php echo $queryTitle; ?></h3>
                            <p class="text-muted"><?php echo count($results); ?> records found</p>
                            
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php else: ?>
                                <?php if (!empty($results)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <?php foreach (array_keys($results[0]) as $column): ?>
                                                        <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $column))); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $row): ?>
                                                    <tr>
                                                        <?php foreach ($row as $value): ?>
                                                            <td><?php echo htmlspecialchars($value); ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3">
<button id="export-csv-btn" class="btn btn-success">
                                            <i class="fas fa-file-csv"></i> Export to CSV
                                        </button>
                                        <button id="print-results-btn" class="btn btn-primary">
                                            <i class="fas fa-print"></i> Print Results
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No records found matching your criteria.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin-advanced-queries.js"></script>
</body>
</html>