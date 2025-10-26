<?php
/**
 * FINAL RENDER-READY FULL TEST SCRIPT
 * Save as test_render.php and open in browser.
 * This script:
 *  - enables full error display
 *  - defines the required constant so includes/security.php won't block
 *  - checks files, DB, sessions, CSRF, then includes dashboard.php and reports errors
 */

// Mark app context so includes/security.php allows being loaded
define('DB_INCLUDED', true);
define('ALLOW_TEST', true); // extra safety for test-only bypass if your security.php supports it

// Basic output
echo "TEST STARTED<br>";
flush();

// Show all errors (temporary for testing only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 1. Paths / files
    echo "1. Testing file paths...<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    echo "config.php exists: " . (file_exists(__DIR__ . '/config.php') ? 'YES' : 'NO') . "<br>";
    echo "db.php exists: " . (file_exists(__DIR__ . '/db.php') ? 'YES' : 'NO') . "<br>";
    echo "db_session.php exists: " . (file_exists(__DIR__ . '/db_session.php') ? 'YES' : 'NO') . "<br>";
    echo "includes/security.php exists: " . (file_exists(__DIR__ . '/includes/security.php') ? 'YES' : 'NO') . "<br>";
    flush();

    // 2. Load config
    echo "2. Loading config...<br>";
    require_once __DIR__ . '/config.php';
    echo "✅ Config loaded<br>";
    flush();

    // 3. Load DB
    echo "3. Loading database...<br>";
    require_once __DIR__ . '/db.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database object \$pdo not found or invalid.");
    }
    echo "✅ Database loaded<br>";
    flush();

    // 4. Test DB connection
    echo "4. Testing database connection...<br>";
    $stmt = $pdo->query("SELECT 1");
    if ($stmt === false) throw new Exception("Database test query failed.");
    $testRow = $stmt->fetch();
    echo "✅ Database connected<br>";
    flush();

    // 5. Load db_session (if present)
    echo "5. Loading db_session (if present)...<br>";
    if (file_exists(__DIR__ . '/db_session.php')) {
        require_once __DIR__ . '/db_session.php';
        echo "✅ db_session loaded<br>";
    } else {
        echo "ℹ️ db_session.php not present — continuing with file-based sessions<br>";
    }
    flush();

    // 6. Prepare a local sessions folder (Render-friendly)
    echo "6. Ensuring sessions folder is writable...<br>";
    $sessionsDir = __DIR__ . '/sessions';
    if (!is_dir($sessionsDir)) {
        if (!mkdir($sessionsDir, 0777, true)) {
            throw new Exception("Failed to create sessions directory at $sessionsDir");
        }
    }
    if (!is_writable($sessionsDir)) {
        if (!chmod($sessionsDir, 0777)) {
            throw new Exception("Sessions directory not writable: $sessionsDir");
        }
    }
    ini_set('session.save_path', $sessionsDir);
    echo "✅ session.save_path set to: " . session_save_path() . "<br>";
    flush();

    // 7. Load security (this file will now allow because DB_INCLUDED is defined)
    echo "7. Loading security...<br>";
    require_once __DIR__ . '/includes/security.php';
    echo "✅ Security loaded<br>";
    flush();

    // 8. Start secure session (use your function if available)
    echo "8. Starting session...<br>";
    if (function_exists('start_secure_session')) {
        start_secure_session();
    } else {
        // minimal fallback
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            if (!isset($_SESSION['_session_initialized'])) {
                session_regenerate_id(true);
                $_SESSION['_session_initialized'] = true;
                $_SESSION['_created'] = time();
            }
        }
    }
    echo "✅ Session started: " . session_id() . "<br>";
    echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT ACTIVE') . "<br>";
    echo "Session save path: " . session_save_path() . "<br>";
    flush();

    // 9. CSRF token check (show or generate)
    echo "9. CSRF token (session): ";
    if (function_exists('get_csrf_token')) {
        $token = get_csrf_token();
        echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . "<br>";
    } else {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . "<br>";
    }
    flush();

    echo "<br><strong>ALL BASIC TESTS PASSED!</strong><br>";
    echo "<br>Now testing actual dashboard.php (will include and capture errors)...<br>";
    flush();

    // 10. Include dashboard and capture any output/errors
    try {
        ob_start();
        require_once __DIR__ . '/dashboard.php';
        $dashOutput = ob_get_clean();
        if ($dashOutput === null) $dashOutput = '';
        echo "<hr><strong>--- DASHBOARD OUTPUT START ---</strong><br>";
        echo $dashOutput ?: "<em>(dashboard.php produced no visible output)</em>";
        echo "<br><strong>--- DASHBOARD OUTPUT END ---</strong><hr>";
    } catch (Throwable $e) {
        // If dashboard.php throws, print details
        if (ob_get_length()) ob_end_clean();
        echo "<br><strong style='color:red'>DASHBOARD ERROR:</strong><br>";
        echo "Message: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
        echo "File: " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
    }

} catch (Throwable $e) {
    // Main catch
    echo "<br><strong style='color:red'>ERROR:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
    echo "File: " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
}
?>
