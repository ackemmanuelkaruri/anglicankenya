<?php
/**
 * API to initiate M-Pesa payment
 * FIXED VERSION - Uses correct database column names
 */

// ✅ CRITICAL: Clean any output buffer and set JSON header first
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Enable error logging (not display)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/payment_errors.log');

// Catch all errors and return JSON
try {
    // Load configuration first
    require_once __DIR__ . '/../../../config.php';
    
    // Manually define the constant needed by security.php
    if (!defined('DB_INCLUDED')) {
        define('DB_INCLUDED', true);
    }
    
    // Load DB and Security
    require_once __DIR__ . '/../../../db.php';
    require_once __DIR__ . '/../../../includes/security.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        start_secure_session();
    }
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in']);
        exit;
    }
    
    // Check CSRF Token
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
        exit;
    }

    // Get and sanitize input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate input
    if (empty($data['amount']) || empty($data['paybill_id']) || empty($data['phone_number'])) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid input data. Required: amount, paybill_id, phone_number']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $amount = (float)$data['amount'];
    $paybillId = (int)$data['paybill_id'];
    $phoneNumber = $data['phone_number'];
    $campaignId = !empty($data['campaign_id']) ? (int)$data['campaign_id'] : null;

    // Validate amount
    if ($amount < 10) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Amount must be at least KES 10']);
        exit;
    }

    // Format phone number
    $phoneNumber = formatPhoneNumberHelper($phoneNumber);
    
    if (!$phoneNumber) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use: 254712345678 or 0712345678']);
        exit;
    }

    // Load Module Files
    require_once __DIR__ . '/../includes/mpesa_adapter.php';
    
    // Get user details
    $stmt = $pdo->prepare("SELECT parish_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Get the Paybill details
    $stmt = $pdo->prepare("SELECT * FROM parish_paybills WHERE id = ? AND is_active = 1");
    $stmt->execute([$paybillId]);
    $paybill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paybill) {
        http_response_code(404);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Selected Paybill not found or inactive']);
        exit;
    }

    // 1. Initiate M-Pesa STK Push first
    $mpesaProvider = new MpesaPaymentProvider();
    $mpesaResult = $mpesaProvider->initiatePayment([
        'amount' => $amount,
        'phone' => $phoneNumber,
        'account' => $paybill['account'] ?? 'Donation',
        'purpose' => $paybill['purpose'] ?? 'Church Donation'
    ]);
    
    if (!$mpesaResult['success']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => $mpesaResult['message'] ?? 'Failed to initiate M-Pesa payment'
        ]);
        exit;
    }
    
    // 2. Create database record after successful STK push
    $pdo->beginTransaction();
    
    try {
        // ✅ FIX: Insert into givings table with CORRECT column names
        $stmt = $pdo->prepare("
            INSERT INTO givings 
            (member_id, parish_id, paybill_id, amount, method, status, merchant_request_id, checkout_request_id, created_at)
            VALUES (?, ?, ?, ?, 'mpesa', 'pending', ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $user['parish_id'],
            $paybillId,
            $amount,
            $mpesaResult['merchant_request_id'],
            $mpesaResult['checkout_request_id']
        ]);
        
        $givingId = $pdo->lastInsertId();
        
        // ✅ FIX: Insert into mpesa_transactions table
        $stmt = $pdo->prepare("
            INSERT INTO mpesa_transactions 
            (giving_id, merchant_request_id, checkout_request_id, phone_number, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $givingId,
            $mpesaResult['merchant_request_id'],
            $mpesaResult['checkout_request_id'],
            $phoneNumber
        ]);
        
        // If campaign ID is provided, link to campaign
        if ($campaignId) {
            $stmt = $pdo->prepare("
                INSERT INTO campaign_donations (campaign_id, giving_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$campaignId, $givingId]);
        }
        
        $pdo->commit();
        
        // Success response
        $response = [
            'success' => true,
            'message' => 'M-Pesa STK Push initiated successfully. Please check your phone.',
            'checkout_request_id' => $mpesaResult['checkout_request_id'],
            'merchant_request_id' => $mpesaResult['merchant_request_id'],
            'amount' => $amount,
            'giving_id' => $givingId
        ];
        
        // Clean buffer and send JSON
        $buffer = ob_get_clean();
        if (!empty($buffer)) {
            error_log("BUFFER CONTENT: " . bin2hex($buffer));
        }
        
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating giving transaction: " . $e->getMessage());
        
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create transaction record: ' . $e->getMessage()
        ]);
        exit;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Payment initiation error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean buffer and return JSON error
    $buffer = ob_get_clean();
    if (!empty($buffer)) {
        error_log("BUFFER CONTENT ON ERROR: " . bin2hex($buffer));
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An internal server error occurred',
        'error_detail' => $e->getMessage() // Remove this in production
    ]);
    exit;
}

/**
 * ✅ HELPER FUNCTION: Format phone number to 254XXXXXXXXX
 */
function formatPhoneNumberHelper($phone) {
    // Remove any spaces, dashes, or plus signs
    $phone = preg_replace('/[\s\-\+]/', '', $phone);
    
    // Remove leading zeros
    $phone = ltrim($phone, '0');
    
    // Add 254 if not present
    if (!str_starts_with($phone, '254')) {
        $phone = '254' . $phone;
    }
    
    // Validate: must be 254 followed by 9 digits
    if (preg_match('/^254[0-9]{9}$/', $phone)) {
        return $phone;
    }
    
    return false;
}

// Final exit to ensure no stray code runs
exit;