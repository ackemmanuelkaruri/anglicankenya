<?php
/**
 * ============================================
 * EMAIL VERIFICATION HANDLER (Supabase Ready)
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';

try {
    // Connect using Supabase-aware helper
    $pdo = get_pdo_connection();
    $pdo->exec("SET search_path TO public");

    if (!isset($_GET['token'])) {
        throw new Exception("Missing verification token.");
    }

    $token = trim($_GET['token']);

    // Look for the user with this token and not yet verified
    $stmt = $pdo->prepare("
        SELECT id, email, email_verified, email_token_expires_at
        FROM users 
        WHERE email_verification_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Invalid or expired verification token.");
    }

    // Check if already verified
    if ($user['email_verified']) {
        $message = "✅ Your email has already been verified.";
    } else {
        $expires = new DateTime($user['email_token_expires_at']);
        $now = new DateTime();

        if ($now > $expires) {
            throw new Exception("Verification link has expired. Please register again.");
        }

        // Mark as verified
        $update = $pdo->prepare("
            UPDATE users 
            SET email_verified = TRUE, 
                email_verification_token = NULL, 
                account_status = 'active',
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$user['id']]);

        $message = "🎉 Email verified successfully! Your account is now active.";
    }

} catch (Exception $e) {
    $message = "❌ Verification Failed: " . htmlspecialchars($e->getMessage());
}

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
        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
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
