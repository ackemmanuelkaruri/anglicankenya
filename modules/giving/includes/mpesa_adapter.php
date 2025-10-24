<?php
/**
 * M-Pesa Payment Provider - Complete STK Push Implementation
 * Supports multiple church paybills with one Daraja account
 */

class MpesaPaymentProvider {
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $baseUrl;
    private $callbackUrl;
    
    public function __construct() {
        // Load configuration
        $config = $this->loadConfig();
        
        $this->consumerKey = $config['consumer_key'];
        $this->consumerSecret = $config['consumer_secret'];
        $this->passkey = $config['passkey'];
        $this->shortcode = $config['shortcode'];
        $this->baseUrl = $config['environment'] === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
        $this->callbackUrl = $config['callback_url'];
        
        // Log configuration load (for debugging)
        error_log("M-Pesa Config Loaded - Environment: " . $config['environment']);
    }
    
    /**
     * Load configuration
     */
    private function loadConfig(): array {
        $configFile = __DIR__ . '/../config/mpesa_config.php';
        
        if (file_exists($configFile)) {
            return require $configFile;
        }
        
        // Fallback to environment variables
        return [
            'consumer_key' => getenv('MPESA_CONSUMER_KEY'),
            'consumer_secret' => getenv('MPESA_CONSUMER_SECRET'),
            'passkey' => getenv('MPESA_PASSKEY'),
            'shortcode' => getenv('MPESA_SHORTCODE'),
            'environment' => getenv('MPESA_ENVIRONMENT') ?: 'sandbox',
            'callback_url' => getenv('APP_URL') . '/modules/giving/api/mpesa_callback.php'
        ];
    }
    
    /**
     * Validate credentials are set
     */
    private function validateCredentials(): bool {
        if (empty($this->consumerKey)) {
            error_log("M-Pesa Error: Consumer Key not set");
            return false;
        }
        if (empty($this->consumerSecret)) {
            error_log("M-Pesa Error: Consumer Secret not set");
            return false;
        }
        if (empty($this->passkey)) {
            error_log("M-Pesa Error: Passkey not set");
            return false;
        }
        if (empty($this->shortcode)) {
            error_log("M-Pesa Error: Shortcode not set");
            return false;
        }
        return true;
    }
    
    /**
     * Get access token from M-Pesa
     */
    private function getAccessToken(): array {
        if (!$this->validateCredentials()) {
            return [
                'success' => false,
                'message' => 'M-Pesa credentials not configured properly'
            ];
        }
        
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            error_log("M-Pesa Token Error: " . $error);
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode == 200 && isset($result['access_token'])) {
            return ['success' => true, 'token' => $result['access_token']];
        }
        
        error_log("M-Pesa Token Failed: " . $response);
        return [
            'success' => false,
            'message' => $result['error_description'] ?? 'Failed to get access token'
        ];
    }
    
    /**
     * Initiate STK Push Payment
     */
    public function initiatePayment(array $paymentData): array {
        // Get access token
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }
        
        $accessToken = $tokenResult['token'];
        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        $timestamp = date('YmdHis');
        
        // Generate password: Base64(Shortcode + Passkey + Timestamp)
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        // Format phone number (must be 254XXXXXXXXX)
        $phone = $this->formatPhoneNumber($paymentData['phone']);
        
        // Prepare STK Push request
        $requestData = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)$paymentData['amount'],
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $paymentData['account'] ?? 'Donation',
            'TransactionDesc' => $paymentData['purpose'] ?? 'Church Donation'
        ];
        
        // Log request for debugging
        error_log("M-Pesa STK Push Request: " . json_encode($requestData));
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            error_log("M-Pesa STK Push Error: " . $error);
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }
        
        $result = json_decode($response, true);
        error_log("M-Pesa STK Push Response: " . $response);
        
        // Check if successful
        if ($httpCode == 200 && isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
            return [
                'success' => true,
                'merchant_request_id' => $result['MerchantRequestID'],
                'checkout_request_id' => $result['CheckoutRequestID'],
                'customer_message' => $result['CustomerMessage'] ?? 'Request sent to your phone. Please enter your M-Pesa PIN.'
            ];
        }
        
        // Handle errors
        $errorMessage = $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'Failed to initiate payment';
        error_log("M-Pesa STK Push Failed: " . $errorMessage);
        
        return [
            'success' => false,
            'message' => $errorMessage,
            'error_code' => $result['ResponseCode'] ?? $result['errorCode'] ?? 'UNKNOWN'
        ];
    }
    
    /**
     * Format phone number to 254XXXXXXXXX
     */
    private function formatPhoneNumber(string $phone): string {
        // Remove any spaces, dashes, or plus signs
        $phone = preg_replace('/[\s\-\+]/', '', $phone);
        
        // Remove leading zeros
        $phone = ltrim($phone, '0');
        
        // Add 254 if not present
        if (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Query transaction status
     */
    public function queryTransactionStatus(string $checkoutRequestId): array {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }
        
        $accessToken = $tokenResult['token'];
        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $requestData = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $result = json_decode($response, true);
        
        if ($httpCode == 200 && isset($result['ResponseCode'])) {
            return [
                'success' => true,
                'result_code' => $result['ResultCode'] ?? null,
                'result_desc' => $result['ResultDesc'] ?? null,
                'status' => $result['ResultCode'] == '0' ? 'completed' : 'pending'
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'Failed to query status'
        ];
    }
}

// ✅ NO CLOSING TAG - Prevents whitespace/BOM issues in JSON responses