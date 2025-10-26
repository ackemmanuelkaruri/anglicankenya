<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Import Brevo and Guzzle classes ---
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
// ----------------------------------------

echo "<h2>Email Configuration Test (Using Brevo API)</h2>";

// 1. Check Autoload and Dependencies
echo "<h3>1. Autoload and Dependency Check:</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:green;'>✅ Composer Autoload found.</p>";
} else {
    echo "<p style='color:red;'>❌ Composer Autoload NOT found. Run 'composer install'.</p>";
    exit;
}


// 2. Check Environment variables
echo "<h3>2. Environment Variables:</h3>";
$env_vars = [
    'BREVO_API_KEY' => getenv('BREVO_API_KEY') ? '***SET***' : 'NOT SET - CRITICAL!',
    'SMTP_FROM_EMAIL' => getenv('SMTP_FROM_EMAIL') ?: 'NOT SET',
    'SMTP_FROM_NAME' => getenv('SMTP_FROM_NAME') ?: 'NOT SET',
];

$apiKey = getenv('BREVO_API_KEY');

foreach ($env_vars as $key => $value) {
    $color = ($value === '***SET***' || $value !== 'NOT SET') ? 'green' : 'red';
    echo "<p style='color:{$color};'>{$key}: " . htmlspecialchars($value) . "</p>";
}

if ($env_vars['BREVO_API_KEY'] === 'NOT SET - CRITICAL!') {
    echo "<p style='color:red; font-weight:bold;'>❌ Cannot proceed. Set BREVO_API_KEY on your Render dashboard.</p>";
    exit;
}

// 3. Brevo API Test (using HTTP over port 443, which is allowed)
echo "<h3>3. Brevo API Send Test:</h3>";

try {
    // Configuration for the Brevo API client
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
    
    // Create the API client instance
    $apiInstance = new TransactionalEmailsApi(
        new Client(),
        $config
    );

    // Define the email content and recipients
    $senderEmail = getenv('SMTP_FROM_EMAIL');
    $senderName = getenv('SMTP_FROM_NAME') ?: 'Anglican Church of Kenya'; // Fallback name
    $recipientEmail = 'beniquecreations@gmail.com'; // Your test recipient
    
    $sendSmtpEmail = new SendSmtpEmail([
        'subject' => 'Test Email from Anglican Kenya System (API Success!)',
        'sender' => [
            'name' => $senderName,
            'email' => $senderEmail
        ],
        'to' => [
            [ 'email' => $recipientEmail, 'name' => 'Test User']
        ],
        // The HTML content is sent directly through the API payload
        'htmlContent' => '<html><body>
            <h1>Success! Render SMTP Block Bypassed.</h1>
            <p>This email was sent using the **Brevo HTTP API**, successfully connecting over standard port 443.</p>
            <p>The system is now ready for email integration.</p>
            <p><strong>Sender:</strong> ' . htmlspecialchars($senderEmail) . '</p>
        </body></html>',
    ]);

    // Send the email via API call
    $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
    
    echo "<p style='color:green; font-weight:bold; margin-top:20px;'>✅ EMAIL SENT SUCCESSFULLY VIA BREVO API!</p>";
    echo "<p>Check your inbox at: " . htmlspecialchars($recipientEmail) . "</p>";
    echo "<p>Brevo Message ID: " . htmlspecialchars($result->getMessageId() ?? 'N/A') . "</p>";

} catch (Exception $e) {
    // Brevo API errors are detailed here
    echo "<p style='color:red; font-weight:bold; margin-top:20px;'>❌ EMAIL FAILED</p>";
    echo "<p style='color:red;'><strong>Exception Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Double-check your <strong>BREVO_API_KEY</strong>, Sender email verification, and Brevo account status.</p>";
}
?>
