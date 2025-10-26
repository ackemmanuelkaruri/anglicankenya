<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_INCLUDED', true);
require_once 'db.php';
require_once 'includes/security.php';

start_secure_session();

// Generate token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

// Handle login test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    // Check CSRF
    if ($csrf !== $_SESSION['csrf_token']) {
        $message = "❌ CSRF TOKEN MISMATCH! This is your issue.";
        $messageType = 'error';
    } else {
        // Try login
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE (username = ? OR email = ?) 
                AND account_status = 'active'
                AND email_verified = TRUE
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $message = "✅ LOGIN SUCCESSFUL! Credentials are correct.";
                $messageType = 'success';
            } else {
                $message = "❌ Invalid username/password or account not activated.";
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = "❌ Database error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Login Test</title>
    <style>
        body {
            font-family: Arial;
            padding: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #218838;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #bee5eb;
            font-size: 14px;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Quick Login Test</h1>
        
        <div class="info">
            <strong>Session Info:</strong><br>
            CSRF Token: <code><?php echo substr($_SESSION['csrf_token'], 0, 15); ?>...</code><br>
            <?php if (isset($_SESSION['user_id'])): ?>
                Already logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                <br><a href="logout.php">Logout first</a>
            <?php else: ?>
                Status: Not logged in
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $messageType; ?>">
                <?php echo $message; ?>
                <?php if ($messageType === 'success'): ?>
                    <br><br>
                    <strong>✅ Your login system is working!</strong><br>
                    Now try the regular login page: <a href="login.php">login.php</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label>Username or Email:</label>
            <input type="text" name="username" placeholder="Enter username or email" required>
            
            <label>Password:</label>
            <input type="password" name="password" placeholder="Enter password" required>
            
            <button type="submit">🔐 Test Login</button>
        </form>

        <div class="info" style="margin-top: 30px;">
            <strong>📝 Instructions:</strong><br>
            1. Enter the username/email you just registered with<br>
            2. Enter your password<br>
            3. Click "Test Login"<br>
            4. If successful, your actual login.php should work too!
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="login.php" style="color: #007bff;">← Back to Real Login Page</a>
        </div>
    </div>
</body>
</html>
