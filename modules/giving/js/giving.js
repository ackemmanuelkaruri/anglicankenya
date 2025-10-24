/**
 * Giving Module - Main JavaScript
 * Anglican Kenya Church Management System
 */

// Global variables
let selectedPaybill = null;

/**
 * Initialize the giving module
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Giving module initialized');
    
    // Initialize paybill selection
    initializePaybillSelection();
    
    // Initialize payment form
    initializePaymentForm();
});

/**
 * Initialize paybill selection cards
 */
function initializePaybillSelection() {
    const paybillCards = document.querySelectorAll('.paybill-card');
    
    paybillCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            paybillCards.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Store selected paybill data
            selectedPaybill = {
                id: this.dataset.paybillId,
                number: this.dataset.paybillNumber,
                account: this.dataset.account,
                purpose: this.dataset.purpose
            };
            
            console.log('Selected paybill:', selectedPaybill);
            
            // Scroll to payment form
            document.querySelector('.payment-form').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
}

/**
 * Initialize payment form submission
 */
function initializePaymentForm() {
    const form = document.getElementById('givingForm');
    
    if (!form) {
        console.warn('Payment form not found');
        return;
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Check if paybill is selected
        if (!selectedPaybill) {
            showAlert('error', 'Please select a payment purpose first');
            return;
        }
        
        // Get form values
        const amount = parseFloat(document.getElementById('amount').value);
        const phone = document.getElementById('phone').value;
        const campaignSelect = document.getElementById('campaign');
        const campaignId = campaignSelect ? campaignSelect.value : '';
        
        // Validate amount
        if (amount < 10) {
            showAlert('error', 'Amount must be at least KES 10');
            return;
        }
        
        // Validate phone number
        const formattedPhone = formatPhoneNumber(phone);
        if (!validatePhoneNumber(formattedPhone)) {
            showAlert('error', 'Invalid phone number. Use format: 254XXXXXXXXX or 07XXXXXXXX');
            return;
        }
        
        // Prepare payment data
        const paymentData = {
            paybill_id: selectedPaybill.id,
            paybill_number: selectedPaybill.number,
            account: selectedPaybill.account,
            purpose: selectedPaybill.purpose,
            amount: amount,
            phone_number: formattedPhone, // ✅ FIX: Key changed from 'phone' to 'phone_number'
            campaign_id: campaignId
        };
        
        console.log('Submitting payment:', paymentData);
        
        // Disable submit button
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        try {
            // Initiate payment
            const result = await initiatePayment(paymentData);
            
            if (result.success) {
                showAlert('success', 
                    `Payment request sent successfully! Check your phone for the M-Pesa prompt.`,
                    result
                );
                
                // Reset form
                form.reset();
                selectedPaybill = null;
                document.querySelectorAll('.paybill-card').forEach(c => c.classList.remove('active'));
                
                // Show payment status
                showPaymentStatus(result);
                
                // Refresh history after 30 seconds
                setTimeout(() => {
                    location.reload();
                }, 30000);
            }
        } catch (error) {
            console.error('Payment error:', error);
            showAlert('error', error.message || 'Payment failed. Please try again.');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

/**
 * Initiate M-Pesa payment
 * @param {Object} paymentData - Payment details
 * @returns {Promise} Payment response
 */
async function initiatePayment(paymentData) {
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }
        
        console.log('🔄 Initiating payment...');
        
        // Make API request
        const response = await fetch('/anglicankenya/modules/giving/api/initiate_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            body: JSON.stringify(paymentData)
        });
        
        // Parse response
        const data = await response.json();
        
        console.log('Response:', data);
        
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        
        return data;
        
    } catch (error) {
        console.error('Payment error:', error);
        throw error;
    }
}

/**
 * Format phone number to 254XXXXXXXXX
 * @param {string} phone - Input phone
 * @returns {string} Formatted phone
 */
function formatPhoneNumber(phone) {
    // Remove all non-numeric characters
    let cleaned = phone.replace(/[^0-9]/g, '');
    
    // Handle different formats
    if (cleaned.startsWith('0')) {
        // 0712345678 -> 254712345678
        cleaned = '254' + cleaned.substring(1);
    } else if (cleaned.startsWith('+254')) {
        // +254712345678 -> 254712345678
        cleaned = cleaned.substring(1);
    } else if (cleaned.startsWith('254')) {
        // Already correct
        cleaned = cleaned;
    } else if (cleaned.length === 9) {
        // 712345678 -> 254712345678
        cleaned = '254' + cleaned;
    }
    
    return cleaned;
}

/**
 * Validate phone number format
 * @param {string} phone - Phone number
 * @returns {boolean} Valid or not
 */
function validatePhoneNumber(phone) {
    return /^254[0-9]{9}$/.test(phone);
}

/**
 * Show alert message
 * @param {string} type - success, error, warning, info
 * @param {string} message - Message to display
 * @param {Object} data - Additional data
 */
function showAlert(type, message, data = null) {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const iconClass = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    }[type] || 'fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${iconClass} me-2"></i>
            <strong>${message}</strong>
            ${data && data.checkout_request_id ? `<br><small>Reference: ${data.checkout_request_id}</small>` : ''}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.payment-form') || document.querySelector('.giving-container');
    const existingAlert = container.querySelector('.alert');
    
    if (existingAlert) {
        existingAlert.remove();
    }
    
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 10000);
}

/**
 * Show payment status section
 * @param {Object} result - Payment result
 */
function showPaymentStatus(result) {
    const statusContainer = document.getElementById('paymentStatus');
    
    if (!statusContainer) return;
    
    const statusHtml = `
        <div class="card border-success">
            <div class="card-body">
                <h5 class="card-title text-success">
                    <i class="fas fa-check-circle me-2"></i>Payment Request Sent
                </h5>
                <p class="card-text">
                    Please check your phone for the M-Pesa prompt and enter your PIN to complete the payment.
                </p>
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Transaction Reference:</strong> ${result.checkout_request_id || 'N/A'}<br>
                        <strong>Amount:</strong> KES ${result.amount || 'N/A'}<br>
                        <strong>Status:</strong> <span class="badge bg-warning">Pending Confirmation</span>
                    </small>
                </div>
            </div>
        </div>
    `;
    
    statusContainer.innerHTML = statusHtml;
    statusContainer.style.display = 'block';
    
    // Scroll to status
    statusContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Export functions for console testing
window.initiatePayment = initiatePayment;
window.formatPhoneNumber = formatPhoneNumber;
window.validatePhoneNumber = validatePhoneNumber;