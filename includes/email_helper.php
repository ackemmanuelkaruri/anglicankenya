<?php
/**
 * ============================================
 * EMAIL HELPER - RENDER COMPATIBLE VERSION
 * Using PHPMailer with Environment Variables
 * ============================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try to load autoload from different possible locations
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("CRITICAL: Composer autoload not found!");
}

// Load environment variables from multiple possible locations
try {
    if (class_exists('Dotenv\Dotenv')) {
        if (file_exists(__DIR__ . '/../.env.supabase')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.supabase');
            $dotenv->safeLoad();
        } elseif (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->safeLoad();
        }
    }
} catch (Exception $e) {
    error_log("Dotenv load error: " . $e->getMessage());
}

/**
 * Get email configuration from environment or fallback
 */
function getEmailConfig() {
    return [
        'host' => getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? 'smtp.gmail.com'),
        'username' => getenv('SMTP_USERNAME') ?: ($_ENV['SMTP_USERNAME'] ?? 'beniquecreations@gmail.com'),
        'password' => getenv('SMTP_PASSWORD') ?: ($_ENV['SMTP_PASSWORD'] ?? 'ikspurkhihjaabvs'),
        'port' => getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587),
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: ($_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@anglicankenya.local'),
        'from_name' => getenv('SMTP_FROM_NAME') ?: ($_ENV['SMTP_FROM_NAME'] ?? 'Church Management System'),
        'app_url' => getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? (isset($_SERVER['HTTP_HOST']) ? 
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 
            'http://localhost'))
    ];
}

/**
 * ============================================
 * SEND VERIFICATION EMAIL
 * ============================================
 */
function sendVerificationEmail($email, $first_name, $verification_token) {
    $config = getEmailConfig();
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];
        
        // Timeout settings for slow connections
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        // Sender/Recipient
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $first_name);
        $mail->addReplyTo($config['from_email'], 'Support Team');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Verify Your Email Address - Church Management System';

        // Build verification link
        $verification_link = rtrim($config['app_url'], '/') . '/verify_email.php?token=' . urlencode($verification_token);

        $mail->Body    = getVerificationEmailTemplate($first_name, $verification_link);
        $mail->AltBody = getVerificationEmailPlainText($first_name, $verification_link);

        $result = $mail->send();
        
        // Log success
        error_log("Verification email sent successfully to: {$email}");
        
        return true;

    } catch (Exception $e) {
        // Detailed error logging
        error_log("=== EMAIL SEND FAILURE ===");
        error_log("To: {$email}");
        error_log("Error: {$mail->ErrorInfo}");
        error_log("Exception: " . $e->getMessage());
        error_log("Config Host: {$config['host']}");
        error_log("Config Username: {$config['username']}");
        error_log("=========================");
        
        return false;
    }
}

/**
 * ============================================
 * SEND PASSWORD RESET EMAIL
 * ============================================
 */
function sendPasswordResetEmail($email, $first_name, $reset_token) {
    $config = getEmailConfig();
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];
        
        $mail->Timeout = 30;

        // Sender/Recipient
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $first_name);
        $mail->addReplyTo($config['from_email'], 'Support Team');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset Request - Church Management System';

        // Build reset link
        $reset_link = rtrim($config['app_url'], '/') . '/reset_password_confirm.php?token=' . urlencode($reset_token);

        $mail->Body    = getPasswordResetEmailTemplate($first_name, $reset_link);
        $mail->AltBody = getPasswordResetEmailPlainText($first_name, $reset_link);

        $mail->send();
        
        error_log("Password reset email sent successfully to: {$email}");
        
        return true;

    } catch (Exception $e) {
        error_log("Password reset email failed: {$mail->ErrorInfo}");
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * ============================================
 * EMAIL TEMPLATES
 * ============================================
 */
function getVerificationEmailTemplate($first_name, $verification_link) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verify Your Email</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;'>
        <table align='center' width='600' cellpadding='0' cellspacing='0' style='background:#fff; margin-top:30px; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);'>
            <tr>
                <td style='background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; text-align:center; padding:40px 0;'>
                    <h1 style='margin:0;'>Church Management System</h1>
                    <p style='margin:5px 0 0 0;'>Email Verification</p>
                </td>
            </tr>
            <tr>
                <td style='padding:40px; color:#333;'>
                    <h2>Hello " . htmlspecialchars($first_name) . ",</h2>
                    <p>Thank you for registering with our Church Management System. Please verify your email to activate your account.</p>
                    <p style='text-align:center; margin:30px 0;'>
                        <a href='" . htmlspecialchars($verification_link) . "' style='background:#667eea; color:#fff; text-decoration:none; padding:14px 30px; border-radius:6px; font-weight:bold; display:inline-block;'>Verify Email</a>
                    </p>
                    <p>If the button doesn't work, copy and paste this link:</p>
                    <p style='background:#f1f1f1; padding:10px; border-radius:4px; word-break:break-all; font-size:12px;'>" . htmlspecialchars($verification_link) . "</p>
                    <p><strong>This link expires in 24 hours.</strong></p>
                </td>
            </tr>
            <tr>
                <td style='background:#f8f9fa; text-align:center; padding:20px; color:#777; font-size:13px;'>
                    &copy; " . date('Y') . " Anglican Church of Kenya
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

function getVerificationEmailPlainText($first_name, $verification_link) {
    return "Hello $first_name,

Thank you for registering with the Church Management System.

Please verify your email using this link:
$verification_link

This link expires in 24 hours.

Thank you,
Anglican Church of Kenya";
}

function getPasswordResetEmailTemplate($first_name, $reset_link) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Password Reset</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;'>
        <table align='center' width='600' cellpadding='0' cellspacing='0' style='background:#fff; margin-top:30px; border-radius:8px; overflow:hidden;'>
            <tr>
                <td style='background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; text-align:center; padding:40px 0;'>
                    <h1 style='margin:0;'>Password Reset</h1>
                </td>
            </tr>
            <tr>
                <td style='padding:40px; color:#333;'>
                    <h2>Hello " . htmlspecialchars($first_name) . ",</h2>
                    <p>Click the button below to reset your password:</p>
                    <p style='text-align:center; margin:30px 0;'>
                        <a href='" . htmlspecialchars($reset_link) . "' style='background:#667eea; color:#fff; text-decoration:none; padding:14px 30px; border-radius:6px; font-weight:bold; display:inline-block;'>Reset Password</a>
                    </p>
                    <p>Or copy this link:</p>
                    <p style='background:#f1f1f1; padding:10px; border-radius:4px; word-break:break-all; font-size:12px;'>" . htmlspecialchars($reset_link) . "</p>
                    <p><strong>This link expires in 1 hour.</strong></p>
                </td>
            </tr>
            <tr>
                <td style='background:#f8f9fa; text-align:center; padding:20px; color:#777; font-size:13px;'>
                    &copy; " . date('Y') . " Anglican Church of Kenya
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

function getPasswordResetEmailPlainText($first_name, $reset_link) {
    return "Hello $first_name,

Reset your password using this link:
$reset_link

This link expires in 1 hour.

Thank you,
Anglican Church of Kenya";
}
