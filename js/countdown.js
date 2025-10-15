/**
 * countdown.js - Countdown and redirect functionality
 * Handles success messages, countdowns, and redirects
 */

/**
 * Consolidated countdown function
 */
function handleFinishUpdate() {
    // Create the countdown overlay
    const overlay = document.createElement('div');
    overlay.className = 'countdown-overlay';
    
    const countdownBox = document.createElement('div');
    countdownBox.className = 'countdown-box';
    countdownBox.innerHTML = `
        <h3>Update Complete</h3>
        <p>Your updates have been saved successfully!</p>
        <p>Redirecting to dashboard in <span id="countdown">5</span> seconds...</p>
        <div class="countdown-buttons">
            <button id="stay-button" class="btn-stay">Stay on This Page</button>
            <button id="go-now-button" class="btn-go-now">Go to Dashboard Now</button>
        </div>
    `;
    
    overlay.appendChild(countdownBox);
    document.body.appendChild(overlay);
    
    // Start countdown
    let seconds = 5;
    const countdownElement = document.getElementById('countdown');
    
    const interval = setInterval(() => {
        seconds--;
        countdownElement.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.href = 'dashboard.php';
        }
    }, 1000);
    
    // Add event listeners to buttons
    document.getElementById('stay-button').addEventListener('click', () => {
        clearInterval(interval);
        document.body.removeChild(overlay);
    });
    
    document.getElementById('go-now-button').addEventListener('click', () => {
        clearInterval(interval);
        window.location.href = 'dashboard.php';
    });
}

/**
 * Function to handle form submission success and show countdown
 */
function showSuccessAndRedirect() {
    // Check if success message exists (indicating form was submitted successfully)
    const successMessage = document.querySelector('.success-message');
    
    if (successMessage) {
        // Create countdown container
        const countdownContainer = document.createElement('div');
        countdownContainer.className = 'countdown-container';
        countdownContainer.innerHTML = `
            <p>Your details have been updated successfully!</p>
            <p>Redirecting to dashboard in <span id="countdown">5</span> seconds...</p>
            <button id="redirect-now" class="btn-redirect">Go to Dashboard Now</button>
        `;
        
        // Insert countdown after success message
        successMessage.parentNode.insertBefore(countdownContainer, successMessage.nextSibling);
        
        // Hide the original success message to avoid duplication
        successMessage.style.display = 'none';
        
        // Start countdown
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const interval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = 'dashboard.php';
            }
        }, 1000);
        
        // Add event listener to redirect-now button
        document.getElementById('redirect-now').addEventListener('click', () => {
            clearInterval(interval);
            window.location.href = 'dashboard.php';
        });
    }
}