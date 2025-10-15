<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/email_helper.php';

echo "<h2>Email Test</h2>";

// Replace with your actual email to receive the test
$test_email = "beniquecreations@gmail.com"; // ⚠️ CHANGE THIS to your email
$test_name = "Test User";
$test_token = "test_token_" . time();

echo "Attempting to send verification email to: $test_email<br><br>";

$result = sendVerificationEmail($test_email, $test_name, $test_token);

echo "<hr>";
if ($result === true) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Email failed to send";
}
?>