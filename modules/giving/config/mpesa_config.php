<?php
/**
 * M-Pesa Configuration
 * Get your credentials from https://developer.safaricom.co.ke
 */

return [
    // YOUR Daraja API credentials
    'consumer_key' => 'HS0J317UfI3G2ZzZKjy5ER5sMU2uBmGBwOJNBGwI2ofs0dw0',
    'consumer_secret' => 'GQQbDhpmdoX10HMo9KqAcVpt0rcwdrrKyn95n6cKG1i9rkNq2aWITph0mPo5wMIN',
    
    // Sandbox test credentials
    'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
    'shortcode' => '174379',
    
    // Environment: 'sandbox' or 'production'
    'environment' => 'sandbox',
    
    // Callback URL - M-Pesa will send payment results here
    'callback_url' => 'https://noncapitalistic-francisco-lunisolar.ngrok-free.dev/anglicankenya/modules/giving/api/mpesa_callback.php'
];

// ✅ NO CLOSING TAG