// File: modules/ministries/ministry_section.js

/**
 * Ministry Section JavaScript - AJAX and Event Handlers
 * Follows the pattern of church_section.js for form submission and notification.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Ministry Section JS: DOM Loaded');

    const saveButton = document.querySelector('.btn-save-section[data-section="ministry"]');
    const deleteButton = document.querySelector('.btn-delete-section[data-section="ministry"]');
    const form = document.getElementById('ministry-form');
    
    if (saveButton) {
        // 'save' action sends the form data
        saveButton.addEventListener('click', () => handleMinistryFormSubmit(form, 'save'));
    }
    
    if (deleteButton) {
        // 'delete_all' action prompts and sends an action flag
        deleteButton.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete ALL your ministry and department assignments? This action cannot be undone.')) {
                handleMinistryFormSubmit(form, 'delete_all');
            }
        });
    }
});

// Clear selections button and save
const clearButton = document.getElementById('clear-ministry-selections');
if (clearButton) {
    clearButton.addEventListener('click', () => {
        if (confirm('Clear all ministry selections and save changes?')) {
            // Uncheck all checkboxes
            document.querySelectorAll('input[name="departments[]"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('input[name="ministries[]"]').forEach(cb => cb.checked = false);
            
            // Automatically save (which will delete all since nothing is checked)
            const form = document.getElementById('ministry-form');
            handleMinistryFormSubmit(form, 'save');
        }
    });
}

/**
 * Handles the AJAX submission for the ministry form.
 * @param {HTMLFormElement} form - The ministry form element.
 * @param {string} action - 'save' or 'delete_all'.
 */
function handleMinistryFormSubmit(form, action) {
    const section = 'ministry';
    const saveButton = document.querySelector(`.btn-save-section[data-section="${section}"]`);
    
    // Disable button and show loading state
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
    
    // 1. Collect Form Data as JSON object (NOT FormData)
    const formData = {
        user_id: document.querySelector('input[name="user_id"]').value,
        action: action
    };

    if (action === 'save') {
        // Collect selected departments (array of values)
        formData.departments = Array.from(document.querySelectorAll('input[name="departments[]"]:checked'))
            .map(input => input.value);

        // Collect selected ministries (array of values)
        formData.ministries = Array.from(document.querySelectorAll('input[name="ministries[]"]:checked'))
            .map(input => input.value);
    }

    console.log('Sending data:', formData);
    console.log('JSON string:', JSON.stringify(formData));

    // 2. Perform AJAX Request with JSON
    fetch('../ministries/ministry_update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'  // CRITICAL: Tell server we're sending JSON
        },
        body: JSON.stringify(formData)  // Convert object to JSON string
    })
    .then(response => {
        // Check for non-200 status codes
        if (!response.ok) {
            return response.json().then(error => Promise.reject(error));
        }
        return response.json();
    })
    .then(data => {
        console.log('Response:', data);
        if (data.success) {
            showNotification(data.message, 'success');
            // If deleted/cleared, uncheck all checkboxes for visual consistency
            if (action === 'delete_all' || (action === 'save' && data.data && data.data.departments.length === 0 && data.data.ministries.length === 0)) {
                 document.querySelectorAll('#ministry-form input[type="checkbox"]').forEach(cb => cb.checked = false);
            }
        } else {
            showNotification(data.message || 'An unknown error occurred during the update.', 'danger');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        const errorMessage = error.message || 'Could not connect to the server or process the request.';
        showNotification(errorMessage, 'danger');
    })
    .finally(() => {
        // Re-enable the button and reset text
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save Ministry';
        }
    });
}

/**
 * Show notification message to user
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of main content
    const mainContent = document.querySelector('.main-content .container');
    if (mainContent) {
        mainContent.insertBefore(notification, mainContent.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}