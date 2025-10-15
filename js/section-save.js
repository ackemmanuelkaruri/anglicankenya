/**
 * Section Save Functionality - OPTIMIZED VERSION
 * Handles saving of individual form sections with enhanced error handling
 */

/**
 * Setup event listeners for section save buttons - FIXED VERSION
 * Supports both .section-save-btn and .btn-save-section class selectors
 * EXCLUDES employment delete buttons to prevent conflicts
 */
function setupSectionSaveButtons() {
    const saveButtons = document.querySelectorAll('.section-save-btn, .btn-save-section');
    
    console.log(`Found ${saveButtons.length} save buttons`);
    
    saveButtons.forEach(button => {
        // ✅ CRITICAL FIX: Skip employment delete/remove buttons
        if (button.classList.contains('btn-remove-employment') || 
            button.classList.contains('btn-remove-employment-role') ||
            button.classList.contains('btn-remove-from-form') ||
            button.getAttribute('onclick')?.includes('deleteEmploymentFromDatabase')) {
            console.log('Skipping employment delete button:', button);
            return; // Skip this button
        }
        
        button.addEventListener('click', function(e) {
            // ✅ ADDITIONAL CHECK: Make sure this isn't an employment action
            if (e.target.closest('.btn-remove-employment, .btn-remove-employment-role, .btn-remove-from-form')) {
                console.log('Click on employment button detected, not handling as section save');
                return; // Let the employment handler deal with it
            }
            
            e.preventDefault();
            
            const sectionId = this.getAttribute('data-section');
            if (!sectionId) {
                console.error('Section ID not found on save button');
                showMessage('error', 'Save button configuration error');
                return;
            }
            
            console.log(`Save button clicked for section: ${sectionId}`);
            
            // Special handling for ministry section
            if (sectionId === 'ministry') {
                if (typeof saveMinistryDetails === 'function') {
                    saveMinistryDetails();
                    return; // Let the ministry handler deal with it
                }
            }
            
            const formData = collectSectionData(sectionId);
            saveSectionData(sectionId, formData, this);
        });
    });
}

/**
 * Collect data from a specific form section
 * @param {string} sectionId - The ID of the section to collect data from
 * @returns {FormData} - The collected form data
 */
function collectSectionData(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) {
        console.error(`Section with ID '${sectionId}' not found`);
        showMessage('error', `Section '${sectionId}' not found on page`);
        return new FormData();
    }
    
    const formData = new FormData();
    const inputs = section.querySelectorAll('input, select, textarea');
    
    console.log(`Collecting data from ${inputs.length} inputs in section: ${sectionId}`);
    
    // Handle employment section
    if (sectionId === 'employment') {
        return collectEmploymentData(section);
    }

    // Handle clergy section - calls function from clergy-handlers.js
    if (sectionId === 'clergy') {
        return collectClergyData(section);
    }

    inputs.forEach(input => {
        // Skip inputs without names
        if (!input.name) {
            return;
        }
        
        if (input.type === 'checkbox' || input.type === 'radio') {
            if (input.checked) {
                formData.append(input.name, input.value);
                console.log(`Collected ${input.type}: ${input.name} = ${input.value}`);
            }
        } else if (input.type === 'file') {
            // Handle file inputs properly
            if (input.files && input.files.length > 0) {
                formData.append(input.name, input.files[0]);
                console.log(`Collected file: ${input.name} = ${input.files[0].name}`);
            }
        } else if (input.type !== 'button' && input.type !== 'submit') {
            // Always append the value, even if empty (server will handle validation)
            formData.append(input.name, input.value || '');
            if (input.value) {
                console.log(`Collected ${input.type}: ${input.name} = ${input.value}`);
            }
        }
    });
    
    return formData;
}

/**
 * Save section data to server
 * @param {string} sectionId - The section being saved
 * @param {FormData} formData - The data to save
 * @param {HTMLElement} saveButton - The save button element
 */
function saveSectionData(sectionId, formData, saveButton) {
    // Add section identifier
    formData.append('section_type', sectionId);
    formData.append('section', sectionId);
    
    // Get user ID from various possible sources
    let userId = getUserId();
    
    if (!userId) {
        console.error('User ID not found - cannot save data');
        showMessage('error', 'User authentication error. Please refresh the page and try again.');
        return;
    }
    
    formData.append('id', userId);
    
    // Debug: Log what we're sending
    console.log('Saving section:', sectionId);
    console.log('User ID:', userId);
    
    // Log form data for debugging
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(`  ${key}: File(${value.name})`);
        } else {
            console.log(`  ${key}: ${value}`);
        }
    }
    
    // Show loading state
    showSaveLoading(saveButton);
    
    // Use your existing endpoint
    const endpoint = '../user/usection_update.php';
    
    // Create abort controller for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout
    
    fetch(endpoint, {
        method: 'POST',
        body: formData,
        signal: controller.signal,
        // Don't set Content-Type header - let browser set it with boundary for FormData
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        clearTimeout(timeoutId);
        
        console.log(`Response status: ${response.status} ${response.statusText}`);
        
        if (!response.ok) {
            // Handle different HTTP error codes
            switch (response.status) {
                case 401:
                    throw new Error('Authentication required. Please log in again.');
                case 403:
                    throw new Error('You are not authorized to perform this action.');
                case 404:
                    throw new Error('The requested resource was not found.');
                case 500:
                    throw new Error('Server error occurred. Please try again later.');
                default:
                    throw new Error(`Request failed with status ${response.status}`);
            }
        }
        
        return response.text(); // Get as text first
    })
    .then(text => {
        console.log('Raw response:', text);
        
        // OPTIMIZED: Better response handling for all sections
        return handleServerResponse(text, sectionId);
    })
    .then(data => {
        console.log('Parsed response:', data);
        
        if (data.success) {
            showSaveSuccess(saveButton);
            showMessage('success', data.message || `${sectionId} details saved successfully!`);
            
            // Optional: Trigger any post-save actions
            triggerPostSaveActions(sectionId, data);
        } else {
            showSaveError(saveButton, data.message || 'Save failed');
            showMessage('error', data.message || 'Error saving data');
            
            // Log additional debug info if available
            if (data.debug) {
                console.error('Server debug info:', data.debug);
            }
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        
        if (error.name === 'AbortError') {
            console.error('Request timed out');
            showSaveError(saveButton, 'Request timed out');
            showMessage('error', 'Request timed out. Please try again.');
        } else {
            console.error('Save error:', error);
            showSaveError(saveButton, error.message);
            showMessage('error', error.message || 'An error occurred while saving');
        }
    });
}

/**
 * OPTIMIZED: Handle server response for all sections
 * @param {string} responseText - The raw response text
 * @param {string} sectionId - The section ID for context
 * @returns {Object} - Parsed JSON response
 */
function handleServerResponse(responseText, sectionId) {
    try {
        // First, try to parse as pure JSON
        return JSON.parse(responseText);
    } catch (initialParseError) {
        console.warn(`Initial JSON parse failed for ${sectionId} section:`, initialParseError.message);
        
        // Handle mixed HTML/JSON response (common issue)
        const cleanedResponse = cleanMixedResponse(responseText);
        if (cleanedResponse) {
            try {
                return JSON.parse(cleanedResponse);
            } catch (secondParseError) {
                console.warn('Second JSON parse attempt failed:', secondParseError.message);
            }
        }
        
        // Look for JSON patterns in the response
        const jsonMatch = responseText.match(/(\{[^{}]*"success"[^{}]*\})/);
        if (jsonMatch) {
            try {
                const extractedJson = jsonMatch[1];
                console.log(`Extracted JSON from ${sectionId} response:`, extractedJson);
                return JSON.parse(extractedJson);
            } catch (extractParseError) {
                console.warn('Extracted JSON parse failed:', extractParseError.message);
            }
        }
        
        // If response contains HTML, it's likely an error page or mixed response
        if (responseText.includes('<') || responseText.includes('<?php')) {
            return {
                success: false,
                message: `Server returned unexpected response format for ${sectionId} section. Please check server configuration.`,
                debug: {
                    issue: 'HTML or PHP content in response',
                    response_preview: responseText.substring(0, 300) + '...',
                    section: sectionId
                }
            };
        }
        
        // Last resort: create error response
        return {
            success: false,
            message: `Unable to process server response for ${sectionId} section. Please try again.`,
            debug: {
                error: initialParseError.message,
                response_preview: responseText.substring(0, 200),
                response_length: responseText.length,
                section: sectionId
            }
        };
    }
}

/**
 * Clean mixed HTML/JSON response
 * @param {string} responseText - The mixed response
 * @returns {string|null} - Cleaned JSON string or null
 */
function cleanMixedResponse(responseText) {
    // Remove HTML comments
    let cleaned = responseText.replace(/<!--[\s\S]*?-->/g, '');
    
    // Remove HTML tags before JSON
    cleaned = cleaned.replace(/^[\s\S]*?(?=\{)/, '');
    
    // Remove any trailing HTML after JSON
    const lastBrace = cleaned.lastIndexOf('}');
    if (lastBrace !== -1) {
        cleaned = cleaned.substring(0, lastBrace + 1);
    }
    
    // Check if we have valid JSON structure
    if (cleaned.includes('"success"') && (cleaned.includes('"message"') || cleaned.includes('"data"'))) {
        return cleaned.trim();
    }
    
    return null;
}

/**
 * Get user ID from various possible sources
 * @returns {string|null} - User ID or null if not found
 */
function getUserId() {
    // Try multiple sources in order of preference
    const sources = [
        () => document.querySelector('input[name="id"]')?.value,
        () => document.querySelector('input[name="user_id"]')?.value,
        () => document.querySelector('[data-user-id]')?.getAttribute('data-user-id'),
        () => document.body.getAttribute('data-user-id'),
        () => document.documentElement.getAttribute('data-user-id'),
        () => window.userId, // Global variable
        () => sessionStorage.getItem('user_id'),
        () => localStorage.getItem('user_id')
    ];
    
    for (const source of sources) {
        try {
            const userId = source();
            if (userId && userId.trim() !== '') {
                console.log('Found user ID:', userId);
                return userId.trim();
            }
        } catch (e) {
            // Continue to next source
        }
    }
    
    console.warn('User ID not found in any source');
    return null;
}

/**
 * Show loading state on save button
 * @param {HTMLElement} button - The save button
 */
function showSaveLoading(button) {
    const originalText = button.textContent;
    button.setAttribute('data-original-text', originalText);
    button.textContent = 'Saving...';
    button.disabled = true;
    button.classList.add('saving');
}

/**
 * Show success state on save button
 * @param {HTMLElement} button - The save button
 */
function showSaveSuccess(button) {
    button.textContent = 'Saved!';
    button.classList.remove('saving');
    button.classList.add('save-success');
    
    setTimeout(() => {
        resetButtonState(button);
    }, 2000);
}

/**
 * Show error state on save button
 * @param {HTMLElement} button - The save button
 * @param {string} message - Error message to display
 */
function showSaveError(button, message) {
    button.textContent = 'Error!';
    button.classList.remove('saving');
    button.classList.add('save-error');
    
    console.error('Save error:', message);
    
    setTimeout(() => {
        resetButtonState(button);
    }, 3000);
}

/**
 * Reset button to original state
 * @param {HTMLElement} button - The save button
 */
function resetButtonState(button) {
    const originalText = button.getAttribute('data-original-text') || 'Save';
    button.textContent = originalText;
    button.disabled = false;
    button.classList.remove('save-success', 'save-error', 'saving');
    button.removeAttribute('data-original-text');
}

/**
 * Show success/error messages in the UI
 * @param {string} type - 'success' or 'error'
 * @param {string} message - Message to display
 */
function showMessage(type, message) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.success-message, .error-message, .message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type === 'success' ? 'success-message' : 'error-message'}`;
    messageDiv.innerHTML = `
        <span class="message-text">${message}</span>
        <button class="message-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Style the message
    Object.assign(messageDiv.style, {
        padding: '12px 16px',
        margin: '10px 0',
        borderRadius: '4px',
        border: `1px solid ${type === 'success' ? '#d4edda' : '#f8d7da'}`,
        backgroundColor: type === 'success' ? '#d1ecf1' : '#f8d7da',
        color: type === 'success' ? '#155724' : '#721c24',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        fontSize: '14px',
        zIndex: '1000',
        position: 'relative'
    });
    
    // Style close button
    const closeBtn = messageDiv.querySelector('.message-close');
    Object.assign(closeBtn.style, {
        background: 'none',
        border: 'none',
        fontSize: '18px',
        cursor: 'pointer',
        color: 'inherit',
        marginLeft: '10px'
    });
    
    // Insert message at the top of the page or after the title
    const insertLocation = document.querySelector('h1, h2, .page-title') || document.body.firstElementChild;
    
    if (insertLocation) {
        insertLocation.parentNode.insertBefore(messageDiv, insertLocation.nextSibling);
    } else {
        document.body.insertBefore(messageDiv, document.body.firstChild);
    }
    
    // Auto-remove message after 8 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.3s ease';
            setTimeout(() => messageDiv.remove(), 300);
        }
    }, 8000);
    
    // Scroll to message
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Trigger any post-save actions
 * @param {string} sectionId - The section that was saved
 * @param {Object} responseData - Response data from server
 */
function triggerPostSaveActions(sectionId, responseData) {
    // Dispatch custom event for other scripts to listen to
    const event = new CustomEvent('sectionSaved', {
        detail: {
            section: sectionId,
            response: responseData
        }
    });
    document.dispatchEvent(event);
    
    // Update any related UI elements
    updateRelatedUI(sectionId, responseData);
}

/**
 * Update related UI elements after successful save
 * @param {string} sectionId - The section that was saved
 * @param {Object} responseData - Response data from server
 */
function updateRelatedUI(sectionId, responseData) {
    // Add any section-specific UI updates here
    switch (sectionId) {
        case 'church':
            // Update church-related UI elements
            updateChurchRelatedFields(responseData);
            break;
        case 'personal':
            // Update personal info displays
            updatePersonalInfoDisplays(responseData);
            break;
        case 'clergy':
            // Update clergy-specific displays if needed
            console.log('Clergy section saved successfully');
            break;
        // Add more cases as needed
    }
}

/**
 * Update church-related fields after save
 * @param {Object} responseData - Response data from server
 */
function updateChurchRelatedFields(responseData) {
    // Example: Update dependent dropdowns or displays
    console.log('Updating church-related UI elements');
}

/**
 * Update personal info displays after save
 * @param {Object} responseData - Response data from server
 */
function updatePersonalInfoDisplays(responseData) {
    // Example: Update name displays throughout the page
    console.log('Updating personal info displays');
}

/**
 * Employment data collection function
 */
function collectEmploymentData(section) {
    const formData = new FormData();
    const employmentRoles = section.querySelectorAll('.employment-role');
    
    console.log(`Found ${employmentRoles.length} employment roles to collect`);
    
    employmentRoles.forEach((role, index) => {
        const jobTitle = role.querySelector('input[name="job_title[]"]')?.value || '';
        const company = role.querySelector('input[name="company[]"]')?.value || '';
        const fromDate = role.querySelector('input[name="employment_from_date[]"]')?.value || '';
        const toDate = role.querySelector('input[name="employment_to_date[]"]')?.value || '';
        const isCurrent = role.querySelector('input[name="is_current_employment[]"]')?.checked || false;
        
        formData.append('job_title[]', jobTitle);
        formData.append('company[]', company);
        formData.append('employment_from_date[]', fromDate);
        formData.append('employment_to_date[]', toDate);
        
        if (isCurrent) {
            formData.append('is_current_employment[]', index.toString());
        }
    });
    
    return formData;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupSectionSaveButtons();
    console.log('Section save functionality initialized');
});