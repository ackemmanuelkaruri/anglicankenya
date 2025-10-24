<?php
/**
 * M-Pesa Callback Handler
 * Receives payment confirmation from Safaricom
 */

require_once __DIR__ . '/../../../db.php';

// Get callback data
$callbackData = file_get_contents('php://input');
$data = json_decode($callbackData, true);

// Log the callback
error_log("M-Pesa Callback Received: " . $callbackData);

// Respond to Safaricom immediately
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);

// Process the callback
if (!$data || !isset($data['Body']['stkCallback'])) {
    error_log("Invalid callback data structure");
    exit;
}

$callback = $data['Body']['stkCallback'];
$merchantRequestId = $callback['MerchantRequestID'] ?? null;
$checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
$resultCode = $callback['ResultCode'] ?? null;
$resultDesc = $callback['ResultDesc'] ?? null;

if (!$merchantRequestId || !$checkoutRequestId) {
    error_log("Missing merchant or checkout request ID");
    exit;
}

try {
    // Find the transaction
    $stmt = $pdo->prepare("
        SELECT * FROM givings 
        WHERE merchant_request_id = ? OR checkout_request_id = ?
    ");
    $stmt->execute([$merchantRequestId, $checkoutRequestId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        error_log("Transaction not found for: " . $merchantRequestId);
        exit;
    }
    
    // Determine status
    $status = ($resultCode == '0') ? 'completed' : 'failed';
    
    // Extract callback metadata if successful
    $mpesaReceiptNumber = null;
    $transactionDate = null;
    $phoneNumber = null;
    
    if ($resultCode == '0' && isset($callback['CallbackMetadata']['Item'])) {
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $item['Value'];
                    break;
            }
        }
    }
    
    // Update transaction
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        UPDATE givings 
        SET status = ?,
            mpesa_receipt_number = ?,
            transaction_date = ?,
            result_desc = ?,
            updated_at = NOW()
        WHERE giving_id = ?
    ");
    $stmt->execute([
        $status,
        $mpesaReceiptNumber,
        $transactionDate,
        $resultDesc,
        $transaction['giving_id']
    ]);
    
    // If successful and linked to campaign, update campaign amount
    if ($status == 'completed') {
        $stmt = $pdo->prepare("
            SELECT campaign_id FROM campaign_donations 
            WHERE giving_id = ?
        ");
        $stmt->execute([$transaction['giving_id']]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($campaign) {
            $stmt = $pdo->prepare("
                UPDATE donation_campaigns 
                SET current_amount = current_amount + ?
                WHERE campaign_id = ?
            ");
            $stmt->execute([$transaction['amount'], $campaign['campaign_id']]);
        }
    }
    
    $pdo->commit();
    
    error_log("Transaction updated successfully: " . $transaction['giving_id'] . " - Status: " . $status);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Callback processing error: " . $e->getMessage());
}
?>