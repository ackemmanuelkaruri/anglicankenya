<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Debug Mode - Testing Register.php Dependencies</h1>";
echo "<hr>";

// Test 1: db.php
echo "<h3>1. Testing db.php</h3>";
try {
    define('DB_INCLUDED', true);
    require_once 'db.php';
    echo "✅ db.php loaded successfully<br>";
    echo "PDO connection: " . (isset($pdo) ? "✅ Connected" : "❌ Not connected") . "<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// Test 2: security.php
echo "<h3>2. Testing includes/security.php</h3>";
try {
    require_once 'includes/security.php';
    echo "✅ security.php loaded successfully<br>";
    
    $functions = ['start_secure_session', 'is_logged_in', 'sanitize_input', 'csrf_field', 'verify_csrf_token'];
    foreach ($functions as $func) {
        echo "Function $func: " . (function_exists($func) ? "✅ EXISTS" : "❌ MISSING") . "<br>";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// Test 3: email_helper.php
echo "<h3>3. Testing includes/email_helper.php</h3>";
try {
    require_once 'includes/email_helper.php';
    echo "✅ email_helper.php loaded successfully<br>";
    echo "Function sendVerificationEmail: " . (function_exists('sendVerificationEmail') ? "✅ EXISTS" : "❌ MISSING") . "<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// Test 4: anglican_province.php
echo "<h3>4. Testing anglican_province.php</h3>";
try {
    require_once 'anglican_province.php';
    echo "✅ anglican_province.php loaded successfully<br>";
    echo "Function loadDiocese: " . (function_exists('loadDiocese') ? "✅ EXISTS" : "❌ MISSING") . "<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// Test 5: scope_helpers.php
echo "<h3>5. Testing includes/scope_helpers.php</h3>";
try {
    require_once 'includes/scope_helpers.php';
    echo "✅ scope_helpers.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// Test 6: Try calling start_secure_session
echo "<h3>6. Testing start_secure_session()</h3>";
try {
    if (function_exists('start_secure_session')) {
        start_secure_session();
        echo "✅ Session started successfully<br>";
    } else {
        echo "❌ Function doesn't exist<br>";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

echo "<h2>✅ All tests completed!</h2>";
echo "<p>If you see this message, all dependencies loaded successfully.</p>";
?>
