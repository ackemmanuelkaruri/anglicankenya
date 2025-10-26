<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Move use statements to the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Email Configuration Test</h2>";

// Check environment variables
echo "<h3>1. Environment Variables:</h3>";
$env_vars = [
    'SMTP_HOST' => getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST'] ?? $_SERVER['SMTP_HOST'] ?? 'NOT SET',
    'SMTP_PORT' => getenv('SMTP_PORT') ?: $_ENV['SMTP_PORT'] ?? $_SERVER['SMTP_PORT'] ?? 'NOT SET',
    'SMTP_USERNAME' => getenv('SMTP_USERNAME') ?: $_ENV['SMTP_USERNAME'] ?? $_SERVER['SMTP_USERNAME'] ?? 'NOT SET',
    'SMTP_PASSWORD' => (getenv('SMTP_PASSWORD') ?: $_ENV['SMTP_PASSWORD'] ?? $_SERVER['SMTP_PASSWORD'] ?? null) ? '***SET***' : 'NOT SET',
    'SMTP_FROM_EMAIL' => getenv('SMTP_FROM_EMAIL') ?: $_ENV['SMTP_FROM_EMAIL'] ?? $_SERVER['SMTP_FROM_EMAIL'] ?? 'NOT SET',
    'SMTP_FROM_NAME' => getenv('SMTP_FROM_NAME') ?: $_ENV['SMTP_FROM_NAME'] ?? $_SERVER['SMTP_FROM_NAME'] ?? 'NOT SET',
    'APP_URL' => getenv('APP_URL') ?: $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? 'NOT SET'
];

foreach ($env_vars as $key => $value) {
    $color = ($value && $value !== 'NOT SET') ? 'green' : 'red';
    echo "<p style='color:{$color};'>{$key}: " . htmlspecialchars($value) . "</p>";
}

// Check PHPMailer
echo "<h3>2. PHPMailer Check:</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:green;'>✅ Autoload found</p>";
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color:green;'>✅ PHPMailer class exists</p>";
        
        // Direct PHPMailer test with error output
        echo "<h3>3. Direct PHPMailer Test (with debug):</h3>";
        
        $mail = new PHPMailer(true);
        
        try {
            // Enable verbose debug output
            $mail->SMTPDebug = 2; // Show all debug info
            $mail->Debugoutput = function($str, $level) {
                echo htmlspecialchars($str) . "<br>";
            };
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $env_vars['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $env_vars['SMTP_USERNAME'];
            $mail->Password   = getenv('SMTP_PASSWORD') ?: $_ENV['SMTP_PASSWORD'] ?? $_SERVER['SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->Timeout    = 30;

            // Recipients
            $mail->setFrom($env_vars['SMTP_FROM_EMAIL'], $env_vars['SMTP_FROM_NAME']);
            $mail->addAddress('beniquecreations@gmail.com', 'Test User');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from Anglican Kenya System';
            $mail->Body    = '<b>This is a test email!</b><p>If you receive this, email is working correctly.</p>';
            $mail->AltBody = 'This is a test email. If you receive this, email is working correctly.';

            $mail->send();
            echo "<p style='color:green; font-weight:bold; margin-top:20px;'>✅ EMAIL SENT SUCCESSFULLY!</p>";
            echo "<p>Check your inbox at: beniquecreations@gmail.com</p>";
            
        } catch (Exception $e) {
            echo "<p style='color:red; font-weight:bold; margin-top:20px;'>❌ EMAIL FAILED</p>";
            echo "<p style='color:red;'><strong>Error Message:</strong> {$mail->ErrorInfo}</p>";
            echo "<p style='color:red;'><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p style='color:red;'>❌ PHPMailer class NOT found</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Autoload NOT found</p>";
}
?>
