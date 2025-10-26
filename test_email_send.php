<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Configuration Test</h2>";

// Check environment variables
echo "<h3>1. Environment Variables:</h3>";
$env_vars = [
    'SMTP_HOST' => getenv('SMTP_HOST'),
    'SMTP_PORT' => getenv('SMTP_PORT'),
    'SMTP_USERNAME' => getenv('SMTP_USERNAME'),
    'SMTP_PASSWORD' => getenv('SMTP_PASSWORD') ? '***SET***' : 'NOT SET',
    'SMTP_FROM_EMAIL' => getenv('SMTP_FROM_EMAIL'),
    'SMTP_FROM_NAME' => getenv('SMTP_FROM_NAME'),
    'APP_URL' => getenv('APP_URL')
];

foreach ($env_vars as $key => $value) {
    $color = ($value && $value !== 'NOT SET') ? 'green' : 'red';
    echo "<p style='color:{$color};'>{$key}: " . htmlspecialchars($value ?: 'NOT SET') . "</p>";
}

// Check if PHPMailer exists
echo "<h3>2. PHPMailer Check:</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:green;'>✅ Autoload found</p>";
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color:green;'>✅ PHPMailer class exists</p>";
        
        // Try sending test email
        echo "<h3>3. Attempting Test Email:</h3>";
        
        require_once 'includes/email_helper.php';
        
        try {
            $result = sendVerificationEmail(
                'beniquecreations@gmail.com', // Your email
                'Test',
                'test_token_' . time()
            );
            
            if ($result) {
                echo "<p style='color:green; font-weight:bold;'>✅ EMAIL SENT SUCCESSFULLY!</p>";
                echo "<p>Check your inbox (and spam folder)</p>";
            } else {
                echo "<p style='color:red; font-weight:bold;'>❌ Email sending returned FALSE</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p style='color:red;'>❌ PHPMailer class NOT found</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Autoload NOT found</p>";
    echo "<p>Run: composer require phpmailer/phpmailer</p>";
}
?>
