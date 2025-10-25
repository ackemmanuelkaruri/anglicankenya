<?php
/**
 * Debug Script for Anglican Kenya Application
 * Tests environment variables, config loading, and database connection
 */

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Information - Anglican Kenya</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            background: #ecf0f1;
            padding: 10px;
            border-left: 4px solid #3498db;
        }
        .info-box {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table td:first-child {
            font-weight: bold;
            width: 200px;
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .test-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .test-failure {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h1>🔍 Debug Information</h1>
    
    <?php
    // ============================================
    // 1. ENVIRONMENT VARIABLES
    // ============================================
    echo "<h2>1. Environment Variables (from Render)</h2>";
    echo "<div class='info-box'>";
    echo "<table>";
    
    $env_vars = [
        'DB_HOST' => getenv('DB_HOST'),
        'DB_PORT' => getenv('DB_PORT'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASS' => getenv('DB_PASS') ? '***SET***' : 'NOT SET',
        'APP_ENV' => getenv('APP_ENV')
    ];
    
    foreach ($env_vars as $key => $value) {
        $status = $value ? '✅' : '❌';
        echo "<tr><td>$status $key</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // ============================================
    // 2. CONFIG FILE LOADING
    // ============================================
    echo "<h2>2. Loading config.php</h2>";
    echo "<div class='info-box'>";
    
    // Try multiple possible locations
    $config_paths = [
        __DIR__ . '/config.php',           // Root folder (same as debug.php)
        __DIR__ . '/config/config.php',    // Config subfolder
        __DIR__ . '/../config.php',        // One level up
    ];
    
    $config_loaded = false;
    $config_path_used = '';
    
    foreach ($config_paths as $config_path) {
        if (file_exists($config_path)) {
            echo "<p class='success'>✅ Config file found at: $config_path</p>";
            $config_path_used = $config_path;
            
            try {
                require_once $config_path;
                echo "<p class='success'>✅ Config loaded successfully</p>";
                $config_loaded = true;
                
                if (defined('APP_ENV')) {
                    echo "<p>Environment detected: <strong>" . APP_ENV . "</strong></p>";
                    echo "<p>Is Supabase: <strong>" . (defined('APP_ENV') && APP_ENV === 'supabase' ? 'YES' : 'NO') . "</strong></p>";
                } else {
                    echo "<p class='warning'>⚠️ APP_ENV constant not defined</p>";
                }
                
                // Show current environment function result if available
                if (function_exists('current_environment')) {
                    echo "<p>Current environment (function): <strong>" . current_environment() . "</strong></p>";
                }
                
                break;
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    if (!$config_loaded) {
        echo "<p class='error'>❌ Config file not found in any of these locations:</p>";
        echo "<ul>";
        foreach ($config_paths as $path) {
            echo "<li>" . htmlspecialchars($path) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
    
    // ============================================
    // 3. CONSTANTS CHECK
    // ============================================
    echo "<h2>3. Constants Defined</h2>";
    echo "<div class='info-box'>";
    echo "<table>";
    
    $constants = [
        'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'NOT DEFINED',
        'DB_PORT' => defined('DB_PORT') ? DB_PORT : 'NOT DEFINED',
        'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'NOT DEFINED',
        'DB_USER' => defined('DB_USER') ? DB_USER : 'NOT DEFINED',
        'DB_PASS' => defined('DB_PASS') ? '***SET***' : 'NOT DEFINED',
    ];
    
    foreach ($constants as $key => $value) {
        $status = ($value !== 'NOT DEFINED') ? '✅' : '❌';
        echo "<tr><td>$status $key constant</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // ============================================
    // 4. DATABASE CONNECTION TEST
    // ============================================
    echo "<h2>4. Database Connection Test</h2>";
    echo "<div class='info-box'>";
    
    // Show connection details
    echo "<h3>Connection Details:</h3>";
    echo "<table>";
    echo "<tr><td>Host</td><td>" . (defined('DB_HOST') ? DB_HOST : 'NOT SET') . "</td></tr>";
    echo "<tr><td>Port</td><td>" . (defined('DB_PORT') ? DB_PORT : 'NOT SET') . "</td></tr>";
    echo "<tr><td>Database</td><td>" . (defined('DB_NAME') ? DB_NAME : 'NOT SET') . "</td></tr>";
    echo "<tr><td>User</td><td>" . (defined('DB_USER') ? DB_USER : 'NOT SET') . "</td></tr>";
    echo "<tr><td>Password</td><td>" . (defined('DB_PASS') && DB_PASS ? "***SET*** (length: " . strlen(DB_PASS) . " chars)" : 'NOT SET') . "</td></tr>";
    echo "</table>";
    
    // Test connection
    echo "<h3>Connection Tests:</h3>";
    
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
        echo "<div class='test-result test-failure'>";
        echo "❌ Cannot test connection - missing required constants";
        echo "</div>";
    } else {
        // Test 1: With SSL
        echo "<div class='test-result'>";
        echo "<strong>Test 1: Connection with SSL (sslmode=require)</strong><br>";
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            echo "<span class='success'>✅ Connected successfully with SSL!</span><br><br>";
            
            // Test query
            $stmt = $pdo->query("SELECT version()");
            $version = $stmt->fetchColumn();
            echo "📊 PostgreSQL Version: " . htmlspecialchars(substr($version, 0, 100)) . "<br><br>";
            
            // Test table access
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'");
                $count = $stmt->fetchColumn();
                echo "📋 Number of tables in database: " . $count . "<br>";
            } catch (PDOException $e) {
                echo "⚠️ Could not count tables: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
            
        } catch (PDOException $e) {
            echo "<span class='error'>❌ Connection failed</span><br>";
            echo "<strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>Error Code:</strong> " . $e->getCode() . "<br><br>";
            
            // Test 2: Without SSL
            echo "<strong>Test 2: Connection without SSL requirement</strong><br>";
            try {
                $dsn2 = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                $pdo2 = new PDO($dsn2, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 10
                ]);
                echo "<span class='success'>✅ Connected successfully without SSL!</span><br>";
            } catch (PDOException $e2) {
                echo "<span class='error'>❌ Also failed without SSL</span><br>";
                echo "<strong>Error:</strong> " . htmlspecialchars($e2->getMessage()) . "<br>";
            }
            
            // Test 3: Try direct connection port
            if (DB_PORT == 6543) {
                echo "<br><strong>Test 3: Trying direct connection (port 5432)</strong><br>";
                try {
                    $dsn3 = "pgsql:host=" . DB_HOST . ";port=5432;dbname=" . DB_NAME . ";sslmode=require";
                    $pdo3 = new PDO($dsn3, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 10
                    ]);
                    echo "<span class='success'>✅ Connected on port 5432!</span><br>";
                    echo "💡 <strong>Suggestion:</strong> Change DB_PORT to 5432 in your environment variables<br>";
                } catch (PDOException $e3) {
                    echo "<span class='error'>❌ Port 5432 also failed</span><br>";
                    echo "<strong>Error:</strong> " . htmlspecialchars($e3->getMessage()) . "<br>";
                }
            }
        }
        echo "</div>";
    }
    
    echo "</div>";
    
    // ============================================
    // 5. PHP INFO
    // ============================================
    echo "<h2>5. PHP Environment</h2>";
    echo "<div class='info-box'>";
    echo "<table>";
    echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
    echo "<tr><td>PDO Available</td><td>" . (extension_loaded('pdo') ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><td>PDO PostgreSQL</td><td>" . (extension_loaded('pdo_pgsql') ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    ?>
    
    <div style="margin-top: 40px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">
        <h3>🔧 Troubleshooting Tips:</h3>
        <ul>
            <li><strong>If connection fails:</strong> Check that your Supabase password doesn't contain special characters like @, &, =, etc.</li>
            <li><strong>Port 6543:</strong> This is Supabase's connection pooler (transaction mode)</li>
            <li><strong>Port 5432:</strong> Direct connection (uses more connections but more compatible)</li>
            <li><strong>SSL Required:</strong> Supabase requires SSL connections</li>
            <li><strong>Timeout errors:</strong> Your Render server might be in a different region than your Supabase database</li>
        </ul>
    </div>
    
</body>
</html>
