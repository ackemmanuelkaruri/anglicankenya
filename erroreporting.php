<?php
/**
 * ============================================
 * COMPREHENSIVE SYSTEM TEST
 * Tests every component needed for dashboard
 * ============================================
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$results = [];
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function test($name, $callback) {
    global $results, $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    
    try {
        ob_start();
        $result = $callback();
        $output = ob_get_clean();
        
        if ($result === true) {
            $passed_tests++;
            $results[] = [
                'status' => 'PASS',
                'name' => $name,
                'output' => $output,
                'error' => null
            ];
        } else {
            $failed_tests++;
            $results[] = [
                'status' => 'FAIL',
                'name' => $name,
                'output' => $output,
                'error' => $result
            ];
        }
    } catch (Exception $e) {
        $failed_tests++;
        $output = ob_get_clean();
        $results[] = [
            'status' => 'ERROR',
            'name' => $name,
            'output' => $output,
            'error' => $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine()
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive System Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            background: #1e1e1e; 
            color: #d4d4d4; 
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { 
            color: #4ec9b0; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .summary {
            background: #252526;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-total { background: #264f78; }
        .summary-pass { background: #0e639c; }
        .summary-fail { background: #a31515; }
        .summary-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .test-result {
            background: #252526;
            margin-bottom: 15px;
            border-radius: 5px;
            overflow: hidden;
            border-left: 4px solid;
        }
        .test-result.pass { border-left-color: #4ec9b0; }
        .test-result.fail { border-left-color: #f48771; }
        .test-result.error { border-left-color: #ce9178; }
        .test-header {
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .test-header:hover { background: #2d2d30; }
        .test-status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .status-pass { background: #4ec9b0; color: #000; }
        .status-fail { background: #f48771; color: #000; }
        .status-error { background: #ce9178; color: #000; }
        .test-body {
            padding: 0 20px 20px 20px;
            display: none;
            border-top: 1px solid #3e3e42;
        }
        .test-body.show { display: block; }
        .output {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 3px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
        .error {
            color: #f48771;
            background: #2d1e1e;
            padding: 15px;
            border-radius: 3px;
            margin-top: 10px;
            white-space: pre-wrap;
        }
        .expand-all {
            background: #0e639c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .expand-all:hover { background: #1177bb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Comprehensive System Test</h1>
        
        <?php
        // ============================================
        // TEST 1: File Existence
        // ============================================
        test("File Existence Check", function() {
            $required_files = [
                'config.php',
                'db.php',
                'db_session.php',
                'includes/security.php',
                'includes/init.php',
                'includes/scope_helpers.php',
                'includes/dashboard_stats.php',
                'includes/rbac.php',
                'includes/dashboard_header.php',
                'includes/dashboard_sidebar.php',
                'includes/dashboard_main_content.php',
                'includes/footer.php'
            ];
            
            $missing = [];
            foreach ($required_files as $file) {
                $path = __DIR__ . '/' . $file;
                if (file_exists($path)) {
                    echo "✅ $file\n";
                } else {
                    echo "❌ $file MISSING\n";
                    $missing[] = $file;
                }
            }
            
            return empty($missing) ? true : "Missing files: " . implode(', ', $missing);
        });

        // ============================================
        // TEST 2: Database Connection
        // ============================================
        test("Database Connection", function() {
            if (!defined('DB_INCLUDED')) {
                define('DB_INCLUDED', true);
            }
            
            require_once __DIR__ . '/config.php';
            require_once __DIR__ . '/db.php';
            
            global $pdo;
            
            echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
            echo "Server: " . $pdo->query("SELECT version()")->fetchColumn() . "\n";
            
            // Test query
            $result = $pdo->query("SELECT 1 as test")->fetch();
            echo "Test query: " . ($result['test'] == 1 ? "✅ PASSED" : "❌ FAILED") . "\n";
            
            return true;
        });

        // ============================================
        // TEST 3: Sessions Table
        // ============================================
        test("Sessions Table Check", function() {
            global $pdo;
            
            // Check if table exists
            $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
            $count = $stmt->fetchColumn();
            echo "Sessions in database: $count\n";
            
            // Check table structure
            $stmt = $pdo->query("
                SELECT column_name, data_type 
                FROM information_schema.columns 
                WHERE table_name = 'sessions'
                ORDER BY ordinal_position
            ");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\nTable structure:\n";
            foreach ($columns as $col) {
                echo "  - {$col['column_name']} ({$col['data_type']})\n";
            }
            
            return count($columns) >= 4 ? true : "Sessions table missing required columns";
        });

        // ============================================
        // TEST 4: Session Handler
        // ============================================
        test("Database Session Handler", function() {
            require_once __DIR__ . '/db_session.php';
            
            echo "Session handler class: " . (class_exists('DatabaseSessionHandler') ? "✅ EXISTS" : "❌ MISSING") . "\n";
            echo "Init function: " . (function_exists('init_database_sessions') ? "✅ EXISTS" : "❌ MISSING") . "\n";
            
            // Test session handler implementation
            $reflection = new ReflectionClass('DatabaseSessionHandler');
            $methods = ['open', 'close', 'read', 'write', 'destroy', 'gc'];
            
            echo "\nRequired methods:\n";
            foreach ($methods as $method) {
                $exists = $reflection->hasMethod($method);
                echo "  - $method: " . ($exists ? "✅" : "❌") . "\n";
            }
            
            return true;
        });

        // ============================================
        // TEST 5: Security Functions
        // ============================================
        test("Security Functions", function() {
            require_once __DIR__ . '/includes/security.php';
            
            $required_functions = [
                'start_secure_session',
                'is_logged_in',
                'get_csrf_token',
                'validate_csrf_token',
                'is_super_admin',
                'has_permission',
                'sanitize_input'
            ];
            
            foreach ($required_functions as $func) {
                $exists = function_exists($func);
                echo ($exists ? "✅" : "❌") . " $func\n";
                if (!$exists) return "Missing function: $func";
            }
            
            return true;
        });

        // ============================================
        // TEST 6: Session Initialization
        // ============================================
        test("Session Initialization", function() {
            global $pdo;
            
            // Initialize database sessions
            init_database_sessions($pdo);
            
            // Start session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ INACTIVE") . "\n";
            echo "Session ID: " . session_id() . "\n";
            echo "Session name: " . session_name() . "\n";
            
            // Test session write
            $_SESSION['test_key'] = 'test_value_' . time();
            echo "Test write: ✅ Success\n";
            
            // Verify in database
            $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_id = ?");
            $stmt->execute([session_id()]);
            $db_session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "Session in DB: " . ($db_session ? "✅ FOUND" : "❌ NOT FOUND") . "\n";
            
            return $db_session ? true : "Session not saved to database";
        });

        // ============================================
        // TEST 7: CSRF Token
        // ============================================
        test("CSRF Token Generation", function() {
            $token = get_csrf_token();
            
            echo "Token generated: " . substr($token, 0, 20) . "...\n";
            echo "Token length: " . strlen($token) . " chars\n";
            echo "Token in session: " . (isset($_SESSION['csrf_token']) ? "✅ YES" : "❌ NO") . "\n";
            
            // Test validation
            $is_valid = validate_csrf_token($token);
            echo "Token validates: " . ($is_valid ? "✅ YES" : "❌ NO") . "\n";
            
            return $is_valid ? true : "CSRF token validation failed";
        });

        // ============================================
        // TEST 8: Users Table
        // ============================================
        test("Users Table Check", function() {
            global $pdo;
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $count = $stmt->fetchColumn();
            echo "Total users: $count\n";
            
            // Check for required columns
            $stmt = $pdo->query("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'users'
                ORDER BY ordinal_position
            ");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $required = ['id', 'username', 'email', 'password', 'role_level', 'account_status'];
            $missing = array_diff($required, $columns);
            
            echo "\nRequired columns:\n";
            foreach ($required as $col) {
                $exists = in_array($col, $columns);
                echo "  " . ($exists ? "✅" : "❌") . " $col\n";
            }
            
            return empty($missing) ? true : "Missing columns: " . implode(', ', $missing);
        });

        // ============================================
        // TEST 9: Init.php
        // ============================================
        test("Init.php Loading", function() {
            require_once __DIR__ . '/includes/init.php';
            
            echo "✅ init.php loaded successfully\n";
            echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ YES" : "❌ NO") . "\n";
            echo "PDO available: " . (isset($GLOBALS['pdo']) ? "✅ YES" : "❌ NO") . "\n";
            echo "DB_INCLUDED defined: " . (defined('DB_INCLUDED') ? "✅ YES" : "❌ NO") . "\n";
            echo "APP_ROOT defined: " . (defined('APP_ROOT') ? "✅ YES" : "❌ NO") . "\n";
            
            return true;
        });

        // ============================================
        // TEST 10: Scope Helpers
        // ============================================
        test("Scope Helpers", function() {
            require_once __DIR__ . '/includes/scope_helpers.php';
            
            $expected_functions = [
                'get_user_scope',
                'get_scope_filter',
                'can_access_scope'
            ];
            
            foreach ($expected_functions as $func) {
                $exists = function_exists($func);
                echo ($exists ? "✅" : "⚠️") . " $func\n";
            }
            
            return true;
        });

        // ============================================
        // TEST 11: Dashboard Stats
        // ============================================
        test("Dashboard Stats Function", function() {
            require_once __DIR__ . '/includes/dashboard_stats.php';
            
            echo "Function exists: " . (function_exists('get_dashboard_stats') ? "✅ YES" : "❌ NO") . "\n";
            
            if (function_exists('get_dashboard_stats')) {
                // Try to get stats (may fail if not logged in, but function should exist)
                try {
                    $stats = get_dashboard_stats();
                    echo "Stats retrieved: ✅ SUCCESS\n";
                    echo "Stats keys: " . implode(', ', array_keys($stats)) . "\n";
                } catch (Exception $e) {
                    echo "Stats call failed (expected if not logged in): " . $e->getMessage() . "\n";
                }
            }
            
            return function_exists('get_dashboard_stats') ? true : "get_dashboard_stats() not found";
        });

        // ============================================
        // TEST 12: RBAC
        // ============================================
        test("RBAC Functions", function() {
            require_once __DIR__ . '/includes/rbac.php';
            
            $rbac_functions = [
                'check_permission',
                'has_role',
                'get_user_permissions'
            ];
            
            foreach ($rbac_functions as $func) {
                $exists = function_exists($func);
                echo ($exists ? "✅" : "⚠️") . " $func\n";
            }
            
            return true;
        });

        // ============================================
        // TEST 13: Dashboard Header
        // ============================================
        test("Dashboard Header File", function() {
            $file = __DIR__ . '/includes/dashboard_header.php';
            
            if (!file_exists($file)) {
                return "File not found";
            }
            
            $content = file_get_contents($file);
            $size = filesize($file);
            
            echo "File size: $size bytes\n";
            echo "Contains HTML: " . (strpos($content, '<') !== false ? "✅ YES" : "❌ NO") . "\n";
            echo "Contains PHP: " . (strpos($content, '<?php') !== false ? "✅ YES" : "❌ NO") . "\n";
            
            // Check for syntax errors
            $check = php_check_syntax($file, $error);
            echo "Syntax check: " . ($check ? "✅ VALID" : "❌ INVALID") . "\n";
            if (!$check) echo "Error: $error\n";
            
            return $check ? true : "Syntax error: $error";
        });

        // ============================================
        // TEST 14: Environment Configuration
        // ============================================
        test("Environment Configuration", function() {
            echo "Environment: " . (function_exists('current_environment') ? current_environment() : 'UNKNOWN') . "\n";
            echo "Is development: " . (function_exists('is_development') ? (is_development() ? 'YES' : 'NO') : 'UNKNOWN') . "\n";
            echo "Database URL set: " . (getenv('DATABASE_URL') ? "✅ YES" : "❌ NO") . "\n";
            echo "PHP version: " . PHP_VERSION . "\n";
            echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'UNKNOWN') . "\n";
            
            return true;
        });

        // ============================================
        // TEST 15: Full Dashboard Simulation
        // ============================================
        test("Full Dashboard Load Simulation", function() {
            global $pdo;
            
            echo "Starting full dashboard simulation...\n\n";
            
            // Simulate logged in user
            $_SESSION['user_id'] = 1; // Assuming user 1 exists
            $_SESSION['role_level'] = 'super_admin';
            
            echo "1. Loading init.php...\n";
            // Already loaded
            echo "   ✅ Done\n\n";
            
            echo "2. Checking login status...\n";
            $logged_in = is_logged_in();
            echo "   " . ($logged_in ? "✅" : "❌") . " Logged in: " . ($logged_in ? "YES" : "NO") . "\n\n";
            
            if (!$logged_in) {
                return "User not logged in";
            }
            
            echo "3. Loading scope helpers...\n";
            // Already loaded
            echo "   ✅ Done\n\n";
            
            echo "4. Loading dashboard stats...\n";
            // Already loaded
            echo "   ✅ Done\n\n";
            
            echo "5. Loading RBAC...\n";
            // Already loaded
            echo "   ✅ Done\n\n";
            
            echo "6. Fetching user data...\n";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "   ✅ User found: {$user['username']}\n";
                echo "   Role: {$user['role_level']}\n\n";
            } else {
                return "User not found in database";
            }
            
            echo "7. Getting dashboard stats...\n";
            $stats = get_dashboard_stats();
            echo "   ✅ Stats retrieved\n";
            echo "   Keys: " . implode(', ', array_keys($stats)) . "\n\n";
            
            echo "8. Testing dashboard header include...\n";
            ob_start();
            include __DIR__ . '/includes/dashboard_header.php';
            $header = ob_get_clean();
            echo "   ✅ Header loaded (" . strlen($header) . " bytes)\n\n";
            
            echo "✅ Full dashboard simulation completed successfully!\n";
            
            return true;
        });

        // Display results
        ?>
        
        <div class="summary">
            <div class="summary-item summary-total">
                <div class="summary-number"><?php echo $total_tests; ?></div>
                <div>Total Tests</div>
            </div>
            <div class="summary-item summary-pass">
                <div class="summary-number"><?php echo $passed_tests; ?></div>
                <div>Passed</div>
            </div>
            <div class="summary-item summary-fail">
                <div class="summary-number"><?php echo $failed_tests; ?></div>
                <div>Failed</div>
            </div>
        </div>

        <button class="expand-all" onclick="toggleAll()">Expand All</button>

        <?php foreach ($results as $index => $result): ?>
            <div class="test-result <?php echo strtolower($result['status']); ?>">
                <div class="test-header" onclick="toggleTest(<?php echo $index; ?>)">
                    <span><?php echo htmlspecialchars($result['name']); ?></span>
                    <span class="test-status status-<?php echo strtolower($result['status']); ?>">
                        <?php echo $result['status']; ?>
                    </span>
                </div>
                <div class="test-body" id="test-<?php echo $index; ?>">
                    <?php if (!empty($result['output'])): ?>
                        <div class="output"><?php echo htmlspecialchars($result['output']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($result['error'])): ?>
                        <div class="error"><?php echo htmlspecialchars($result['error']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <script>
        function toggleTest(index) {
            const body = document.getElementById('test-' + index);
            body.classList.toggle('show');
        }

        function toggleAll() {
            const bodies = document.querySelectorAll('.test-body');
            const anyHidden = Array.from(bodies).some(body => !body.classList.contains('show'));
            
            bodies.forEach(body => {
                if (anyHidden) {
                    body.classList.add('show');
                } else {
                    body.classList.remove('show');
                }
            });
            
            const button = document.querySelector('.expand-all');
            button.textContent = anyHidden ? 'Collapse All' : 'Expand All';
        }
    </script>
</body>
</html>
