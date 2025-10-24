<?php
/**
 * Giving Module Helper Functions
 */

/**
 * Get parish Paybills
 * 
 * @param int $parishId Parish ID
 * @return array List of active Paybills
 */
function getParishPaybills($parishId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM parish_paybills 
        WHERE parish_id = ? AND is_active = 1 
        ORDER BY purpose ASC
    ");
    $stmt->execute([$parishId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get giving transactions for a parish
 * 
 * @param int $parishId Parish ID
 * @param array $filters Optional filters (date range, status, etc.)
 * @return array List of transactions
 */
function getParishGivings($parishId, $filters = []) {
    global $pdo;
    
    $sql = "
        SELECT gt.*, pp.purpose, pp.paybill_number, 
               CONCAT(u.first_name, ' ', u.last_name) as member_name,
               mt.mpesa_receipt_number, mt.phone_number
        FROM givings gt
        JOIN parish_paybills pp ON gt.paybill_id = pp.id
        JOIN users u ON gt.member_id = u.id
        LEFT JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
        WHERE gt.parish_id = ?
    ";
    
    $params = [$parishId];


    
    
    // Add date filter if provided
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $sql .= " AND gt.created_at BETWEEN ? AND ?";
        $params[] = $filters['start_date'] . ' 00:00:00';
        $params[] = $filters['end_date'] . ' 23:59:59';
    }
    
    // Add status filter if provided
    if (!empty($filters['status'])) {
        $sql .= " AND gt.status = ?";
        $params[] = $filters['status'];
    }
    
    $sql .= " ORDER BY gt.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get donation campaigns for a parish
 * 
 * @param int $parishId Parish ID
 * @return array List of active campaigns
 */
function getParishCampaigns($parishId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT *, 
               (current_amount / target_amount * 100) as progress_percentage
        FROM donation_campaigns 
        WHERE parish_id = ? AND is_active = 1 
        AND end_date >= CURDATE()
        ORDER BY end_date ASC
    ");
    $stmt->execute([$parishId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create a new giving transaction
 * 
 * @param array $transactionData Transaction details
 * @return int|bool Giving ID on success, false on failure
 */
function createGivingTransaction($transactionData) {
    global $pdo;
    
    $pdo->beginTransaction();
    try {
        // Insert into givings table
        $stmt = $pdo->prepare("
            INSERT INTO givings 
            (member_id, parish_id, paybill_id, amount, method, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $transactionData['member_id'],
            $transactionData['parish_id'],
            $transactionData['paybill_id'],
            $transactionData['amount'],
            $transactionData['method'],
            'pending'
        ]);
        
        $givingId = $pdo->lastInsertId();
        
        // Insert into M-Pesa transactions table
        $stmt = $pdo->prepare("
            INSERT INTO mpesa_transactions 
            (giving_id, merchant_request_id, checkout_request_id, phone_number, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $givingId,
            $transactionData['merchant_request_id'],
            $transactionData['checkout_request_id'],
            $transactionData['phone'],
            'pending'
        ]);
        
        // If campaign ID is provided, link to campaign
        if (!empty($transactionData['campaign_id'])) {
            $stmt = $pdo->prepare("
                INSERT INTO campaign_donations (campaign_id, giving_id, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$transactionData['campaign_id'], $givingId]);
        }
        
        $pdo->commit();
        return $givingId;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating giving transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate giving receipt
 * 
 * @param int $givingId Giving ID
 * @return array Receipt data
 */
function generateGivingReceipt($givingId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT gt.*, pp.purpose, pp.paybill_number, pp.account,
               CONCAT(u.first_name, ' ', u.last_name) as member_name,
               u.email, u.phone,
               p.parish_name,
               mt.mpesa_receipt_number, mt.transaction_date
        FROM givings gt
        JOIN parish_paybills pp ON gt.paybill_id = pp.id
        JOIN users u ON gt.member_id = u.id
        JOIN parishes p ON gt.parish_id = p.parish_id
        LEFT JOIN mpesa_transactions mt ON gt.giving_id = mt.giving_id
        WHERE gt.giving_id = ?
    ");
    $stmt->execute([$givingId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Send giving receipt via email
 * 
 * @param int $givingId Giving ID
 * @return bool True if sent successfully
 */
function sendGivingReceipt($givingId) {
    $receipt = generateGivingReceipt($givingId);
    
    if (!$receipt) {
        return false;
    }
    
    // Generate PDF receipt (would need a PDF library like TCPDF or mPDF)
    $pdfContent = generatePdfReceipt($receipt);
    
    // Send email
    $subject = "Giving Receipt - " . $receipt['parish_name'];
    $message = "Dear " . $receipt['member_name'] . ",\n\n";
    $message .= "Thank you for your donation of KES " . number_format($receipt['amount'], 2) . " to " . $receipt['parish_name'] . ".\n\n";
    $message .= "Purpose: " . $receipt['purpose'] . "\n";
    $message .= "Transaction ID: " . $receipt['mpesa_receipt_number'] ?? 'Pending' . "\n";
    $message .= "Date: " . ($receipt['transaction_date'] ?? date('Y-m-d H:i:s', strtotime($receipt['created_at']))) . "\n\n";
    $message .= "Please find your receipt attached.\n\n";
    $message .= "God bless you!\n";
    $message .= $receipt['parish_name'];
    
    $headers = "From: " . $receipt['parish_name'] . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"boundary\"\r\n\r\n";
    
    // Email with attachment would be implemented here
    // This is a simplified version
    
    return mail($receipt['email'], $subject, $message, $headers);
}

/**
 * Generate PDF receipt (placeholder function)
 * 
 * @param array $receiptData Receipt data
 * @return string PDF content
 */
function generatePdfReceipt($receiptData) {
    // This would use a PDF library to generate the receipt
    // For now, return a placeholder
    return "PDF Receipt for Giving ID: " . $receiptData['giving_id'];
}
?>