<?php
/**
 * Access Control Test Suite
 */

require_once '../includes/init.php';
require_once '../includes/rbac.php';

// Set content type for better output
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Access Control Test Results</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body>
    <div class='container mt-5'>
        <h1 class='mb-4'><i class='fas fa-shield-alt me-2'></i>Access Control Test Results</h1>";

// Test users configuration
 $test_users = [
    [
        'username' => 'superadmin',
        'password' => 'password123',
        'role' => 'super_admin',
        'expected_access' => ['list.php', 'create.php', 'edit.php', 'delete.php', 'impersonate.php']
    ],
    [
        'username' => 'dioceseadmin',
        'password' => 'password123',
        'role' => 'diocese_admin',
        'expected_access' => ['list.php', 'create.php', 'edit.php']
    ],
    [
        'username' => 'parishadmin',
        'password' => 'password123',
        'role' => 'parish_admin',
        'expected_access' => ['list.php', 'create.php']
    ],
    [
        'username' => 'member',
        'password' => 'password123',
        'role' => 'member',
        'expected_access' => ['list.php']
    ]
];

// Test endpoints
 $test_endpoints = [
    'list.php' => 'User List',
    'create.php' => 'Create User',
    'edit.php?id=1' => 'Edit User',
    'delete.php?id=1' => 'Delete User',
    'impersonate.php?id=2' => 'Impersonate User'
];

 $results = [];
 $total_tests = 0;
 $passed_tests = 0;

foreach ($test_users as $user) {
    echo "<div class='card mb-4'>
            <div class='card-header'>
                <h4 class='mb-0'>Testing User: <strong>" . htmlspecialchars($user['username']) . "</strong> (" . htmlspecialchars($user['role']) . ")</h4>
            </div>
            <div class='card-body'>
                <div class='table-responsive'>
                    <table class='table table-striped'>
                        <thead>
                            <tr>
                                <th>Endpoint</th>
                                <th>Expected</th>
                                <th>Actual</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>";
    
    // Get user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user['username']]);
    $db_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$db_user) {
        echo "<tr><td colspan='4' class='text-danger'>User not found in database</td></tr>";
        continue;
    }
    
    // Test each endpoint
    foreach ($test_endpoints as $endpoint => $name) {
        $total_tests++;
        
        // Check expected access
        $expected_access = in_array($endpoint, $user['expected_access']);
        
        // Check actual access based on role
        $actual_access = should_have_access($user['role'], $endpoint);
        
        // Determine if test passed
        $test_passed = ($expected_access === $actual_access);
        if ($test_passed) $passed_tests++;
        
        // Display result
        $result_class = $test_passed ? 'success' : 'danger';
        $result_icon = $test_passed ? 'check-circle' : 'times-circle';
        $result_text = $test_passed ? 'PASS' : 'FAIL';
        
        echo "<tr>
                <td>" . htmlspecialchars($name) . "</td>
                <td><span class='badge bg-" . ($expected_access ? 'success' : 'secondary') . "'>" . ($expected_access ? 'Allowed' : 'Denied') . "</span></td>
                <td><span class='badge bg-" . ($actual_access ? 'success' : 'secondary') . "'>" . ($actual_access ? 'Allowed' : 'Denied') . "</span></td>
                <td><span class='badge bg-$result_class'><i class='fas fa-$result-icon me-1'></i>$result_text</span></td>
              </tr>";
        
        // Log failed tests
        if (!$test_passed) {
            $results[] = [
                'user' => $user['username'],
                'endpoint' => $name,
                'expected' => $expected_access ? 'access' : 'denied',
                'actual' => $actual_access ? 'access' : 'denied'
            ];
        }
    }
    
    echo "</tbody></table></div></div></div>";
}

// Summary
 $success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 2) : 0;
 $summary_class = $success_rate >= 80 ? 'success' : ($success_rate >= 50 ? 'warning' : 'danger');

echo "<div class='card mb-4'>
        <div class='card-header'>
            <h4 class='mb-0'>Test Summary</h4>
        </div>
        <div class='card-body'>
            <div class='row'>
                <div class='col-md-3'>
                    <div class='text-center'>
                        <h2 class='text-$summary_class'>$passed_tests/$total_tests</h2>
                        <p class='text-muted mb-0'>Tests Passed</p>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='text-center'>
                        <h2 class='text-$summary_class'>$success_rate%</h2>
                        <p class='text-muted mb-0'>Success Rate</p>
                    </div>
                </div>
                <div class='col-md-6'>
                    <h5>Failed Tests:</h5>";
                    
if (empty($results)) {
    echo "<p class='text-success mb-0'><i class='fas fa-check-circle me-2'></i>All tests passed!</p>";
} else {
    echo "<ul class='mb-0'>";
    foreach ($results as $result) {
        echo "<li class='text-danger'>
                <strong>{$result['user']}</strong> accessing <strong>{$result['endpoint']}</strong> - 
                Expected {$result['expected']}, got {$result['actual']}
              </li>";
    }
    echo "</ul>";
}

echo "      </div>
            </div>
        </div>
    </div>";

// Additional security checks
echo "<div class='card mb-4'>
        <div class='card-header'>
            <h4 class='mb-0'>Security Checks</h4>
        </div>
        <div class='card-body'>
            <div class='table-responsive'>
                <table class='table table-striped'>
                    <thead>
                        <tr>
                            <th>Check</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>";

// Check CSRF protection
 $csrf_check = function_exists('generate_csrf_token');
echo "<tr>
        <td>CSRF Protection</td>
        <td><span class='badge bg-" . ($csrf_check ? 'success' : 'danger') . "'>" . ($csrf_check ? 'Enabled' : 'Disabled') . "</span></td>
        <td>" . ($csrf_check ? 'CSRF tokens are properly implemented' : 'CSRF protection not found') . "</td>
      </tr>";

// Check password hashing
 $hash_check = defined('PASSWORD_DEFAULT');
echo "<tr>
        <td>Password Hashing</td>
        <td><span class='badge bg-" . ($hash_check ? 'success' : 'danger') . "'>" . ($hash_check ? 'Secure' : 'Insecure') . "</span></td>
        <td>" . ($hash_check ? 'Using modern password hashing' : 'Password hashing not properly implemented') . "</td>
      </tr>";

// Check session security
 $session_check = isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE;
echo "<tr>
        <td>Session Security</td>
        <td><span class='badge bg-" . ($session_check ? 'success' : 'danger') . "'>" . ($session_check ? 'Active' : 'Inactive') . "</span></td>
        <td>" . ($session_check ? 'Secure sessions are active' : 'Session security not properly configured') . "</td>
      </tr>";

echo "</tbody></table></div></div></div>";

echo "<div class='text-center mt-4'>
        <a href='../modules/users/list.php' class='btn btn-primary'>
            <i class='fas fa-arrow-left me-2'></i>Back to User List
        </a>
      </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

/**
 * Determine if role should have access to endpoint
 */
function should_have_access($role, $endpoint) {
    switch ($endpoint) {
        case 'list.php':
            return true; // All authenticated users can view list
        case 'create.php':
            return in_array($role, ['super_admin', 'diocese_admin', 'parish_admin']);
        case 'edit.php':
        case 'delete.php':
            return in_array($role, ['super_admin', 'diocese_admin']);
        case 'impersonate.php':
            return $role === 'super_admin';
        default:
            return false;
    }
}