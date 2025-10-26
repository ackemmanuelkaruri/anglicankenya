<?php
// Ultra-simple test - if you see this, PHP is working
echo "TEST STARTED<br>";
flush();

try {
    echo "1. Testing error display...<br>";
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    echo "✅ Error display enabled<br>";
    flush();

    echo "2. Testing file paths...<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "config.php exists: " . (file_exists(__DIR__ . '/config.php') ? 'YES' : 'NO') . "<br>";
    flush();

    echo "3. Loading config...<br>";
    if (!defined('DB_INCLUDED')) {
        define('DB_INCLUDED', true);
    }
    require_once __DIR__ . '/config.php';
    echo "✅ Config loaded<br>";
    flush();

    echo "4. Loading database...<br>";
    require_once __DIR__ . '/db.php';
    echo "✅ Database loaded<br>";
    flush();

    echo "5. Testing database connection...<br>";
    $test = $pdo->query("SELECT 1")->fetch();
    echo "✅ Database connected<br>";
    flush();

    echo "6. Loading db_session...<br>";
    require_once __DIR__ . '/db_session.php';
    echo "✅ Session handler loaded<br>";
    flush();

    echo "7. Loading security...<br>";
    require_once __DIR__ . '/includes/security.php';
    echo "✅ Security loaded<br>";
    flush();

    echo "8. Starting session...<br>";
    start_secure_session();
    echo "✅ Session started: " . session_id() . "<br>";
    flush();

    echo "<br><strong>ALL BASIC TESTS PASSED!</strong><br>";
    echo "<br>Now testing actual dashboard.php...<br>";
    flush();

} catch (Exception $e) {
    echo "<br><strong style='color:red'>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
