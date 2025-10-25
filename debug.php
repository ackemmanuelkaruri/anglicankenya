<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Information</h2>";

echo "<h3>1. Environment Variables (from Render)</h3>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "<br>";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "<br>";
echo "DB_PASS: " . (getenv('DB_PASS') ? '***SET***' : 'NOT SET') . "<br>";
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'NOT SET') . "<br>";

echo "<h3>2. Loading config.php</h3>";
try {
    require_once __DIR__ . '/config.php';
    echo "✅ Config loaded<br>";
    echo "Environment detected: " . current_environment() . "<br>";
    echo "Is Supabase: " . (is_supabase() ? 'YES' : 'NO') . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Constants Defined</h3>";
echo "DB_HOST constant: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
echo "DB_PORT constant: " . (defined('DB_PORT') ? DB_PORT : 'NOT DEFINED') . "<br>";
echo "DB_NAME constant: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
echo "DB_USER constant: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "<br>";
echo "DB_PASS constant: " . (defined('DB_PASS') ? '***SET***' : 'NOT DEFINED') . "<br>";

echo "<h3>4. Database Connection Test</h3>";
try {
    require_once __DIR__ . '/db.php';
    echo "✅ Database connected!<br>";
    
    $stmt = $pdo->query("SELECT current_database(), current_user");
    $result = $stmt->fetch();
    echo "Connected to database: " . $result['current_database'] . "<br>";
    echo "Connected as user: " . $result['current_user'] . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $count = $stmt->fetch();
    echo "Total users: " . $count['total'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "Error details: <pre>" . print_r($e, true) . "</pre>";
}

echo "<h3>5. PHP Info</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
?>
