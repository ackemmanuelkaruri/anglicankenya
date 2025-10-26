<?php
/**
 * ============================================
 * EMAIL HELPER - RENDER + BREVO VERSION
 * Using PHPMailer with Environment Variables
 * ============================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("CRITICAL: Composer autoload not found!");
}

/**
 * Get environment variable - tries multiple sources
 */
function getEnvVar($key, $default = '') {
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    return $default;
}

/**
 * Get email configuration (Brevo)
 */
function getEmailConfig() {
    $config = [
        'host' => getEnvVar('SMTP_HOST', 'smtp-relay.brevo.com'),
        'username' => getEnvVar('SMTP_USERNAME', 'ackemmanuelchurchkaruri@gmail.com'),
        'password' => getEnvVar('SMTP_PASSWORD', 'YOUR_BREVO_API_KEY'),
        'port' => getEnvVar('SMTP_PORT', '587'),
        'from_email' => getEnvVar('SMTP_FROM_EMAIL', 'ackemmanuelchurchkaruri@gmail.com'),
        'from_name' => getEnvVar('SMTP_FROM_NAME', 'ACK Emmanuel Church Karuri'),
        'app_url' => getEnvVar('APP_URL', 
            (isset($_SERVER['HTTP_HOST']) ? 
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 
                'http://localhost'))
    ];

    error_log("📨 Email Config Loaded - Host: {$config['host']}, User: {$config['username']}, Port: {$config['port']}, URL: {$config['app_url']}");
    return $config;
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
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$config['port'];
        $mail->Timeout    = 30;

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $first_name);
        $mail->addReplyTo($config['from_email'], 'ACK Support');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Verify Your Email Address - ACK Emmanuel Church Karuri';

        $verification_link = rtrim($config['app_url'], '/') . '/verify_email.php?token=' . urlencode($verification_token);
        $mail->Body    = getVerificationEmailTemplate($first_name, $verification_link);
        $mail->AltBody = getVerificationEmailPlainText($first_name, $verification_link);

        $mail->send();
        error_log("✅ Verification email sent successfully to: {$email}");
        return true;

    } catch (Exception $e) {
        error_log("❌ Email send failed for {$email}: {$mail->ErrorInfo}");
        error_log("Exception: " . $e->getMessage());
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
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$config['port'];
        $mail->Timeout    = 30;

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $first_name);
        $mail->addReplyTo($config['from_email'], 'ACK Support');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset Request - ACK Emmanuel Church Karuri';

        $reset_link = rtrim($config['app_url'], '/') . '/reset_password_confirm.php?token=' . urlencode($reset_token);
        $mail->Body    = getPasswordResetEmailTemplate($first_name, $reset_link);
        $mail->AltBody = getPasswordResetEmailPlainText($first_name, $reset_link);

        $mail->send();
        error_log("✅ Password reset email sent successfully to: {$email}");
        return true;

    } catch (Exception $e) {
        error_log("❌ Password reset email failed for {$email}: {$mail->ErrorInfo}");
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
    <head><meta charset='UTF-8'><title>Verify Email</title></head>
    <body style='font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <table align='center' width='600' style='background:#fff; margin-top:30px; border-radius:8px; overflow:hidden;'>
            <tr>
                <td style='background:#4b6cb7; color:#fff; text-align:center; padding:40px 0;'>
                    <h1>ACK Emmanuel Church Karuri</h1>
                    <p>Email Verification</p>
                </td>
            </tr>
            <tr>
                <td style='padding:40px; color:#333;'>
                    <h2>Hello " . htmlspecialchars($first_name) . ",</h2>
                    <p>Thank you for registering! Please verify your email to activate your account.</p>
                    <p style='text-align:center; margin:30px 0;'>
                        <a href='" . htmlspecialchars($verification_link) . "' style='background:#4b6cb7; color:#fff; text-decoration:none; padding:14px 30px; border-radius:6px;'>Verify Email</a>
                    </p>
                    <p>If the button doesn’t work, copy this link:</p>
                    <p style='background:#f1f1f1; padding:10px; border-radius:4px;'>" . htmlspecialchars($verification_link) . "</p>
                    <p><strong>This link expires in 24 hours.</strong></p>
                </td>
            </tr>
            <tr>
                <td style='background:#f8f9fa; text-align:center; padding:20px; color:#777; font-size:13px;'>
                    &copy; " . date('Y') . " ACK Emmanuel Church Karuri
                </td>
            </tr>
        </table>
    </body></html>";
}

function getVerificationEmailPlainText($first_name, $verification_link) {
    return "Hello $first_name,\n\nThank you for registering.\nPlease verify your email:\n$verification_link\n\nThis link expires in 24 hours.\n\nACK Emmanuel Church Karuri";
}

function getPasswordResetEmailTemplate($first_name, $reset_link) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head><meta charset='UTF-8'><title>Password Reset</title></head>
    <body style='font-family: Arial, sans-serif; background-color:#f4f4f4;'>
        <table align='center' width='600' style='background:#fff; margin-top:30px; border-radius:8px; overflow:hidden;'>
            <tr>
                <td style='background:#4b6cb7; color:#fff; text-align:center; padding:40px 0;'>
                    <h1>Password Reset</h1>
                </td>
            </tr>
            <tr>
                <td style='padding:40px; color:#333;'>
                    <h2>Hello " . htmlspecialchars($first_name) . ",</h2>
                    <p>Click below to reset your password:</p>
                    <p style='text-align:center; margin:30px 0;'>
                        <a href='" . htmlspecialchars($reset_link) . "' style='background:#4b6cb7; color:#fff; text-decoration:none; padding:14px 30px; border-radius:6px;'>Reset Password</a>
                    </p>
                    <p>Or copy this link:</p>
                    <p style='background:#f1f1f1; padding:10px; border-radius:4px;'>" . htmlspecialchars($reset_link) . "</p>
                    <p><strong>This link expires in 1 hour.</strong></p>
                </td>
            </tr>
            <tr>
                <td style='background:#f8f9fa; text-align:center; padding:20px; color:#777; font-size:13px;'>
                    &copy; " . date('Y') . " ACK Emmanuel Church Karuri
                </td>
            </tr>
        </table>
    </body></html>";
}

function getPasswordResetEmailPlainText($first_name, $reset_link) {
    return "Hello $first_name,\n\nReset your password using this link:\n$reset_link\n\nThis link expires in 1 hour.\n\nACK Emmanuel Church Karuri";
}
