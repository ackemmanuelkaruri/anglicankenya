<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Registration Debug</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.warning{color:orange;}";
echo "h2{background:#333;color:white;padding:10px;margin-top:20px;}";
echo "pre{background:#fff;padding:10px;border:1px solid #ddd;overflow:auto;}</style></head><body>";

echo "<h1>🔍 Registration Process Debug Test</h1>";
echo "<hr>";

// Initialize
define('DB_INCLUDED', true);
require_once 'db.php';
require_once 'includes/security.php';
require_once 'includes/email_helper.php';
require_once 'anglican_province.php';
require_once 'includes/scope_helpers.php';

echo "<h2>✅ Step 1: All Dependencies Loaded</h2>";

// Test database connection
echo "<h2>📊 Step 2: Database Connection Test</h2>";
try {
    if (isset($pdo)) {
        echo "<span class='success'>✅ PDO connection exists</span><br>";
        $pdo->query("SELECT 1");
        echo "<span class='success'>✅ Database query successful</span><br>";
    } else {
        echo "<span class='error'>❌ PDO connection not found</span><br>";
        die("Cannot proceed without database connection");
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Database error: " . $e->getMessage() . "</span><br>";
    die();
}

// Test database tables
echo "<h2>🗄️ Step 3: Database Tables Check</h2>";
$required_tables = [
    'registration_attempts',
    'dioceses',
    'archdeaconries',
    'deaneries',
    'parishes',
    'organizations',
    'users'
];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✅ Table '$table' exists</span><br>";
        } else {
            echo "<span class='error'>❌ Table '$table' MISSING</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</span><br>";
    }
}

// Check users table structure
echo "<h2>📋 Step 4: Users Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = [
        'id', 'organization_id', 'province_id', 'diocese_id', 'archdeaconry_id', 
        'deanery_id', 'parish_id', 'role_level', 'username', 'email', 'password',
        'first_name', 'last_name', 'phone_number', 'gender', 'date_of_birth',
        'account_status', 'email_verification_token', 'email_verified', 
        'email_token_expires_at', 'created_at'
    ];
    
    $existing_columns = array_column($columns, 'Field');
    
    echo "<strong>Checking required columns:</strong><br>";
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<span class='success'>✅ $col</span><br>";
        } else {
            echo "<span class='error'>❌ $col MISSING</span><br>";
        }
    }
    
    echo "<br><strong>Full table structure:</strong><br>";
    echo "<pre>" . print_r($columns, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}

// Test loadDiocese function
echo "<h2>⛪ Step 5: Test loadDiocese() Function</h2>";
try {
    $test_diocese = "Nairobi"; // Change this to your actual diocese name
    echo "Testing with diocese: <strong>$test_diocese</strong><br>";
    
    $dioceseData = loadDiocese($test_diocese);
    
    if ($dioceseData) {
        echo "<span class='success'>✅ loadDiocese() returned data</span><br>";
        echo "<strong>Diocese structure:</strong><br>";
        echo "<pre>" . print_r($dioceseData, true) . "</pre>";
        
        if (isset($dioceseData['archdeaconries']) && is_array($dioceseData['archdeaconries'])) {
            echo "<span class='success'>✅ Archdeaconries array exists (" . count($dioceseData['archdeaconries']) . " found)</span><br>";
        } else {
            echo "<span class='error'>❌ Archdeaconries array missing or invalid</span><br>";
        }
    } else {
        echo "<span class='error'>❌ loadDiocese() returned null or false</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span><br>";
}

// Test a complete registration flow with dummy data
echo "<h2>🧪 Step 6: Simulate Registration Process</h2>";

// Dummy test data
$test_data = [
    'diocese' => 'Diocese of Mount Kenya South', // CHANGE THIS to match your data
    'archdeaconry' => 'Thimbigua Archdeaconry', // CHANGE THIS
    'deanery' => 'Karuri Deanery', // CHANGE THIS
    'parish' => 'Ack Emmanuel Karuri Parish', // CHANGE THIS
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test_' . time() . '@example.com', // Unique email
    'username' => 'testuser_' . time(), // Unique username
    'phone' => '0712345678',
    'gender' => 'Male',
    'date_of_birth' => '1990-01-01',
    'password' => 'TestPass123!@#'
];

echo "<strong>Test Data:</strong><br>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

try {
    echo "<br><strong>Starting transaction...</strong><br>";
    $pdo->beginTransaction();
    
    // Load and validate diocese data
    echo "Loading diocese data...<br>";
    $dioceseData = loadDiocese($test_data['diocese']);
    
    if (!$dioceseData) {
        throw new Exception("Diocese '{$test_data['diocese']}' not found in anglican_province.php");
    }
    echo "<span class='success'>✅ Diocese data loaded</span><br>";
    
    // Validate hierarchy
    echo "Validating church hierarchy...<br>";
    $archFound = false;
    $deanFound = false;
    $parishFound = false;
    
    foreach ($dioceseData['archdeaconries'] as $arch) {
        if ($arch['name'] === $test_data['archdeaconry']) {
            $archFound = true;
            echo "<span class='success'>✅ Archdeaconry found: {$arch['name']}</span><br>";
            
            if (isset($arch['deaneries'])) {
                foreach ($arch['deaneries'] as $dean) {
                    if ($dean['name'] === $test_data['deanery']) {
                        $deanFound = true;
                        echo "<span class='success'>✅ Deanery found: {$dean['name']}</span><br>";
                        
                        if (isset($dean['parishes'])) {
                            foreach ($dean['parishes'] as $par) {
                                if ($par === $test_data['parish']) {
                                    $parishFound = true;
                                    echo "<span class='success'>✅ Parish found: {$par}</span><br>";
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            break;
        }
    }
    
    if (!$archFound) throw new Exception("Archdeaconry '{$test_data['archdeaconry']}' not found");
    if (!$deanFound) throw new Exception("Deanery '{$test_data['deanery']}' not found");
    if (!$parishFound) throw new Exception("Parish '{$test_data['parish']}' not found");
    
    // Get or create diocese
    echo "<br>Processing diocese...<br>";
    $stmt = $pdo->prepare("SELECT diocese_id FROM dioceses WHERE diocese_name = ? LIMIT 1");
    $stmt->execute([$test_data['diocese']]);
    $diocese_id = $stmt->fetchColumn();
    
    if (!$diocese_id) {
        $stmt = $pdo->prepare("INSERT INTO dioceses (diocese_name, created_at) VALUES (?, NOW())");
        $stmt->execute([$test_data['diocese']]);
        $diocese_id = $pdo->lastInsertId();
        echo "<span class='success'>✅ Created diocese_id: $diocese_id</span><br>";
    } else {
        echo "<span class='success'>✅ Found diocese_id: $diocese_id</span><br>";
    }
    
    // Get or create archdeaconry
    echo "Processing archdeaconry...<br>";
    $stmt = $pdo->prepare("SELECT archdeaconry_id FROM archdeaconries WHERE archdeaconry_name = ? AND diocese_id = ? LIMIT 1");
    $stmt->execute([$test_data['archdeaconry'], $diocese_id]);
    $archdeaconry_id = $stmt->fetchColumn();
    
    if (!$archdeaconry_id) {
        $stmt = $pdo->prepare("INSERT INTO archdeaconries (diocese_id, archdeaconry_name, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$diocese_id, $test_data['archdeaconry']]);
        $archdeaconry_id = $pdo->lastInsertId();
        echo "<span class='success'>✅ Created archdeaconry_id: $archdeaconry_id</span><br>";
    } else {
        echo "<span class='success'>✅ Found archdeaconry_id: $archdeaconry_id</span><br>";
    }
    
    // Get or create deanery
    echo "Processing deanery...<br>";
    $stmt = $pdo->prepare("SELECT deanery_id FROM deaneries WHERE deanery_name = ? AND archdeaconry_id = ? LIMIT 1");
    $stmt->execute([$test_data['deanery'], $archdeaconry_id]);
    $deanery_id = $stmt->fetchColumn();
    
    if (!$deanery_id) {
        $stmt = $pdo->prepare("INSERT INTO deaneries (archdeaconry_id, deanery_name, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$archdeaconry_id, $test_data['deanery']]);
        $deanery_id = $pdo->lastInsertId();
        echo "<span class='success'>✅ Created deanery_id: $deanery_id</span><br>";
    } else {
        echo "<span class='success'>✅ Found deanery_id: $deanery_id</span><br>";
    }
    
    // Get or create parish
    echo "Processing parish...<br>";
    $stmt = $pdo->prepare("SELECT parish_id FROM parishes WHERE parish_name = ? AND deanery_id = ? LIMIT 1");
    $stmt->execute([$test_data['parish'], $deanery_id]);
    $parish_id = $stmt->fetchColumn();
    
    if (!$parish_id) {
        $stmt = $pdo->prepare("INSERT INTO parishes (deanery_id, parish_name, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$deanery_id, $test_data['parish']]);
        $parish_id = $pdo->lastInsertId();
        echo "<span class='success'>✅ Created parish_id: $parish_id</span><br>";
    } else {
        echo "<span class='success'>✅ Found parish_id: $parish_id</span><br>";
    }
    
    // Get or create organization
    echo "Processing organization...<br>";
    $stmt = $pdo->prepare("
        SELECT id FROM organizations 
        WHERE diocese_id = ? AND archdeaconry_id = ? AND deanery_id = ? AND parish_id = ?
        LIMIT 1
    ");
    $stmt->execute([$diocese_id, $archdeaconry_id, $deanery_id, $parish_id]);
    $org = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$org) {
        $org_code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $test_data['diocese']), 0, 3)) . 
                   strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $test_data['archdeaconry']), 0, 3)) . 
                   strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $test_data['deanery']), 0, 3)) . 
                   strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $test_data['parish']), 0, 3));
        
        $stmt = $pdo->prepare("
            INSERT INTO organizations (
                org_name, org_code, diocese_id, archdeaconry_id, deanery_id, parish_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $test_data['parish'] . " Parish", 
            $org_code, 
            $diocese_id, 
            $archdeaconry_id, 
            $deanery_id, 
            $parish_id
        ]);
        $org_id = $pdo->lastInsertId();
        echo "<span class='success'>✅ Created organization_id: $org_id (code: $org_code)</span><br>";
    } else {
        $org_id = $org['id'];
        echo "<span class='success'>✅ Found organization_id: $org_id</span><br>";
    }
    
    // Prepare user data
    echo "<br>Preparing user data...<br>";
    $email_token = bin2hex(random_bytes(32));
    $hashed_password = password_hash($test_data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $phone = '+254' . substr($test_data['phone'], 1);
    $token_expiry = (new DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');
    $province_id = 1;
    
    echo "Province ID: $province_id<br>";
    echo "Org ID: $org_id<br>";
    echo "Diocese ID: $diocese_id<br>";
    echo "Archdeaconry ID: $archdeaconry_id<br>";
    echo "Deanery ID: $deanery_id<br>";
    echo "Parish ID: $parish_id<br>";
    echo "Phone (normalized): $phone<br>";
    echo "Token expiry: $token_expiry<br>";
    
    // Try to insert user
    echo "<br><strong>Attempting user insert...</strong><br>";
    $stmt = $pdo->prepare("
        INSERT INTO users (
            organization_id, 
            province_id, 
            diocese_id, 
            archdeaconry_id, 
            deanery_id, 
            parish_id,
            role_level, 
            username, 
            email, 
            password, 
            first_name, 
            last_name, 
            phone_number, 
            gender, 
            date_of_birth,
            account_status, 
            email_verification_token, 
            email_verified, 
            email_token_expires_at, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'member', ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 0, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $org_id, 
        $province_id, 
        $diocese_id, 
        $archdeaconry_id, 
        $deanery_id, 
        $parish_id,
        $test_data['username'], 
        $test_data['email'], 
        $hashed_password, 
        $test_data['first_name'], 
        $test_data['last_name'], 
        $phone, 
        $test_data['gender'], 
        $test_data['date_of_birth'],
        $email_token, 
        $token_expiry
    ]);
    
    if ($result) {
        $user_id = $pdo->lastInsertId();
        echo "<span class='success'>✅✅✅ USER INSERTED SUCCESSFULLY!</span><br>";
        echo "<span class='success'>User ID: $user_id</span><br>";
        
        echo "<br><strong>Rolling back transaction (test mode)...</strong><br>";
        $pdo->rollBack();
        echo "<span class='warning'>⚠️ Transaction rolled back - no data was actually saved</span><br>";
        
        echo "<br><h2 style='background:green;'>🎉 SUCCESS! Registration process is working correctly!</h2>";
        echo "<p>The error must be related to:</p>";
        echo "<ul>";
        echo "<li>Specific data being submitted from the form</li>";
        echo "<li>CSRF token validation</li>";
        echo "<li>Rate limiting</li>";
        echo "<li>Email sending issues</li>";
        echo "</ul>";
        
    } else {
        echo "<span class='error'>❌ Insert failed but no exception thrown</span><br>";
        echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<br><h2 style='background:red;'>❌ PDO EXCEPTION CAUGHT!</h2>";
    echo "<strong>Error Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    echo "<strong>SQL State:</strong> " . $e->errorInfo[0] . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<br><h2 style='background:red;'>❌ EXCEPTION CAUGHT!</h2>";
    echo "<strong>Error Message:</strong> " . $e->getMessage() . "<br>";
    echo "<br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><hr>";
echo "<h2>📝 Instructions</h2>";
echo "<ol>";
echo "<li>Update the test data in this file (lines around 'Dummy test data') to match your actual diocese/archdeaconry/deanery/parish names</li>";
echo "<li>Run this script and check the output</li>";
echo "<li>If you see a PDO exception, the error message will tell you exactly what's wrong</li>";
echo "<li>Check the php_errors.log and registration debug logs for more details</li>";
echo "</ol>";

echo "</body></html>";
?>
