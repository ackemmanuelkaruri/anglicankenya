<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Error Check</h1><pre>";

// Check if files exist
$files = [
    'config.php',
    'db.php',
    'db_session.php',
    'includes/security.php',
    'includes/init.php',
    'includes/scope_helpers.php',
    'includes/dashboard_stats.php',
    'includes/rbac.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo $file . ": " . (file_exists($path) ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

echo "\n--- Testing Database Connection ---\n";
try {
    define('DB_INCLUDED', true);
    require_once 'config.php';
    require_once 'db.php';
    echo "✅ Database connected!\n";
    echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n--- Testing Session Handler ---\n";
try {
    require_once 'db_session.php';
    echo "✅ Session handler loaded!\n";
} catch (Exception $e) {
    echo "❌ Session handler error: " . $e->getMessage() . "\n";
}

echo "\n--- Testing Security Functions ---\n";
try {
    require_once 'includes/security.php';
    echo "✅ Security functions loaded!\n";
} catch (Exception $e) {
    echo "❌ Security error: " . $e->getMessage() . "\n";
}

echo "\n--- Testing Sessions Table ---\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
    echo "✅ Sessions table exists! Count: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "❌ Sessions table error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
