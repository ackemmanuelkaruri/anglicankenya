<?php
/**
 * API to check M-Pesa payment status
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../includes/mpesa_adapter.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check CSRF token
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get checkout request ID
 $checkoutRequestId = $_GET['checkout_request_id'] ?? '';

if (empty($checkoutRequestId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing checkout request ID']);
    exit;
}

// Check transaction status
 $mpesaProvider = new MpesaPaymentProvider();
 $result = $mpesaProvider->getTransactionStatus($checkoutRequestId);

if ($result['success']) {
    // Get additional transaction details
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT gt.*, pp.purpose, pp.paybill_number, 
               CONCAT(u.first_name, ' ', u.last_name) as member_name,
               mt.mpesa_receipt_number
        FROM givings gt
        JOIN parish_paybills pp ON gt.paybill_id = pp.id
        JOIN users u ON gt.member_id = u.id
        JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
        WHERE mt.checkout_request_id = ?
    ");
    $stmt->execute([$checkoutRequestId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo json_encode([
            'success' => true,
            'status' => $result['status'],
            'result_desc' => $result['result_desc'] ?? '',
            'amount' => $transaction['amount'],
            'purpose' => $transaction['purpose'],
            'mpesa_receipt_number' => $transaction['mpesa_receipt_number'],
            'giving_id' => $transaction['giving_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Transaction not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
?>