<?php
/**
 * Payment Provider Interface
 * Defines the contract for all payment providers
 */

interface PaymentProvider {
    /**
     * Initiate a payment transaction
     * 
     * @param array $paymentData Payment details (amount, phone, etc.)
     * @return array Transaction details
     */
    public function initiatePayment(array $paymentData): array;
    
    /**
     * Process payment callback/webhook
     * 
     * @param array $callbackData Callback data from payment provider
     * @return bool Whether the callback was processed successfully
     */
    public function processCallback(array $callbackData): bool;
    
    /**
     * Get transaction status
     * 
     * @param string $transactionId Transaction reference
     * @return array Transaction status details
     */
    public function getTransactionStatus(string $transactionId): array;
    
    /**
     * Refund a transaction
     * 
     * @param string $transactionId Transaction reference
     * @param float $amount Refund amount
     * @return array Refund details
     */
    public function refundTransaction(string $transactionId, float $amount): array;
}
?>