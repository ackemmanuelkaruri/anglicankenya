<?php
// Bootstrap file to initialize the environment for tests

require_once __DIR__ . '/../config.php'; // your environment config
require_once __DIR__ . '/../db.php'; // your DB connection file

// Optionally, switch environment to test
if (!defined('APP_ENV')) {
    define('APP_ENV', 'test');
}


// Setup a test database connection if needed
$testDbConfig = get_db_config();
$testDbConfig['name'] = 'anglicankenya_test'; // separate test DB
$pdo = new PDO(
    "mysql:host={$testDbConfig['host']};dbname={$testDbConfig['name']};charset={$testDbConfig['charset']}",
    $testDbConfig['user'],
    $testDbConfig['pass']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
