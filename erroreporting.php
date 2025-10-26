<?php
// Ultra-simple Render-compatible database & dashboard test
echo "TEST STARTED<br>";
flush();

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "1. Testing file paths...<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "config.php exists: " . (file_exists(__DIR__ . '/config.php') ? 'YES' : 'NO') . "<br>";
    flush();

    echo "2. Loading config...<br>";
    require_once __DIR__ . '/config.php';
    echo "✅ Config loaded<br>";
    flush();

    echo "3. Loading database...<br>";
    require_once __DIR__ . '/db.php';
    echo "✅ Database loaded<br>";
    flush();

    echo "4. Testing database connection...<br>";
    $test = $pdo->query("SELECT 1")->fetch();
    echo "✅ Database connected<br>";
    flush();

    echo "5. Loading db_session...<br>";
    require_once __DIR__ . '/db_session.php';
    echo "✅ Session handler loaded<br>";
    flush();

    echo "6. Loading security...<br>";
    require_once __DIR__ . '/includes/security.php';
    echo "✅ Security loaded<br>";
    flush();

    // Ensure session works on Render
    ini_set('session.save_path', __DIR__ . '/sessions');
    if (!is_dir(__DIR__ . '/sessions')) mkdir(__DIR__ . '/sessions', 0777, true);

    echo "7. Starting session...<br>";
    start_secure_session(); // or session_start() if this fails
    echo "✅ Session started: " . session_id() . "<br>";
    flush();

    echo "<br><strong>ALL BASIC TESTS PASSED!</strong><br>";
    echo "<br>Now testing actual dashboard.php...<br>";
    flush();

    // Include dashboard and catch any errors
    try {
        require_once __DIR__ . '/dashboard.php';
    } catch (Throwable $e) {
        echo "<br><strong style='color:red'>DASHBOARD ERROR:</strong> " . $e->getMessage() . "<br>";
    }

} catch (Throwable $e) {
    echo "<br><strong style='color:red'>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
