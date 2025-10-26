<?php
/**
 * ============================================
 * EMAIL VERIFICATION HANDLER (Supabase + Logging)
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';

// Create logs directory if not exists
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/verify_debug_' . date('Y-m-d') . '.log';

/**
 * Helper to log debug info with timestamps
 */
function vdebug($msg) {
    global $log_file;
    $time = date('Y-m-d H:i:s');
    $entry = "[{$time}] {$msg}\n";
    file_put_contents($log_file, $entry, FILE_APPEND);
    error_log($entry); // also goes to PHP error log
}

vdebug("=== Email verification attempt ===");
vdebug("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

$message = '';
$status = 'error';

try {
    $pdo = get_pdo_connection();
    $pdo->exec("SET search_path TO public");

    if (!isset($_GET['token']) || empty($_GET['token'])) {
        throw new Exception("No verification token provided.");
    }

    $token = trim($_GET['token']);
    vdebug("Received token: {$token}");

    // Check token format
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        throw new Exception("Invalid token format.");
    }

    $stmt = $pdo->prepare("
        SELECT id, email, email_verified, email_token_expires_at
        FROM users 
        WHERE email_verification_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        vdebug("Token not found in database.");
        throw new Exception("Invalid or already used verification link.");
    }

    vdebug("User found: {$user['email']} | Verified: {$user['email_verified']} | Expires: {$user['email_token_expires_at']}");

    if ($user['email_verified']) {
        $message = "✅ This email is already verified. You can log in.";
        $status = 'info';
        vdebug("Already verified user: {$user['email']}");
    } else {
        $expires = new DateTime($user['email_token_expires_at']);
        $now = new DateTime();

        if ($now > $expires) {
            vdebug("Token expired for user {$user['email']} (expired at {$user['email_token_expires_at']})");
            throw new Exception("Verification link expired. Please register again.");
        }

        // Mark verified
        $update = $pdo->prepare("
            UPDATE users
            SET email_verified = TRUE,
                email_verification_token = NULL,
                account_status = 'active',
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$user['id']]);

        vdebug("Verification successful for {$user['email']} (ID: {$user['id']})");

        $message = "🎉 Your email has been verified successfully! You can now log in.";
        $status = 'success';
    }

} catch (Exception $e) {
    $message = "❌ Verification failed: " . htmlspecialchars($e->getMessage());
    $status = 'error';
    vdebug("ERROR: " . $e->getMessage());
}

vdebug("=== Verification process ended ===\n");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light text-center py-5">
    <div class="container">
        <div class="card shadow-sm mx-auto" style="max-width:600px;">
            <div class="card-body">
                <h3 class="card-title mb-3">Email Verification</h3>
                <p class="lead"><?php echo $message; ?></p>
                <hr>
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
