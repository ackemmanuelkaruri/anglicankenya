// ======================================================================
// FORM VALIDATION AND ENFORCEMENT FUNCTIONS
// ======================================================================

/**
 * Form validation initialization
 */
function initFormValidation() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            // Validate departments (optional)
            const departmentCheckboxes = document.querySelectorAll('.department-checkbox:checked');
            
            // Validate ministries (optional)
            const ministryCheckboxes = document.querySelectorAll('.ministry-checkbox:checked');
            
            // Add specific validation rules here
            if (!validateRequiredFields()) {
                event.preventDefault();
                showValidationErrors();
                return false;
            }
            
            // If all validations pass, form will submit
            return true;
        });
    }
}

/**
 * Enforce single selection for Service Attending
 */
function enforceServiceAttendingSelection() {
    const serviceSelect = document.getElementById('service_attending');
    const currentValue = serviceSelect.value;
    
    // Store the current selection
    if (currentValue) {
        // Clear any previous service selections from database
        // This would typically be handled in your backend processing
        serviceSelect.setAttribute('data-current-selection', currentValue);
    }
}

/**
 * Enforce single selection for Kikuyu Cell Group
 */
function enforceKikuyuCellGroupSelection() {
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    const currentValue = cellGroupSelect.value;
    
    // Store the current selection
    if (currentValue) {
        cellGroupSelect.setAttribute('data-current-selection', currentValue);
        
        // Clear family group when cell group changes
        const familyGroupSelect = document.getElementById('family_group');
        if (familyGroupSelect) {
            familyGroupSelect.value = '';
            familyGroupSelect.setAttribute('data-current-selection', '');
        }
    }
}

/**
 * Enforce single selection for Family Group
 */
function enforceFamilyGroupSelection() {
    const familyGroupSelect = document.getElementById('family_group');
    const currentValue = familyGroupSelect.value;
    
    // Store the current selection
    if (currentValue) {
        familyGroupSelect.setAttribute('data-current-selection', currentValue);
    }
}

/**
 * Handle baptism status change and remove "No" option
 */
function handleBaptismChange() {
    const baptizedYes = document.getElementById('baptized_yes');
    const baptizedNo = document.getElementById('baptized_no');
    
    // Only hide "No" option if user actively changes from "No" to "Yes"
    if (baptizedYes.checked) {
        // Check if this is a change from "No" to "Yes"
        const wasNo = baptizedNo.hasAttribute('data-was-checked');
        
        if (wasNo) {
            // User changed from "No" to "Yes", so hide "No" option
            baptizedNo.closest('.radio-option').style.display = 'none';
            baptizedNo.removeAttribute('data-was-checked');
        }
        
        // Show certificate section
        toggleBaptismSections();
    }
}

/**
 * Handle confirmation status change and remove "No" option
 */
function handleConfirmationChange() {
    const confirmedYes = document.getElementById('confirmed_yes');
    const confirmedNo = document.getElementById('confirmed_no');
    
    // Only hide "No" option if user actively changes from "No" to "Yes"
    if (confirmedYes.checked) {
        // Check if this is a change from "No" to "Yes"
        const wasNo = confirmedNo.hasAttribute('data-was-checked');
        
        if (wasNo) {
            // User changed from "No" to "Yes", so hide "No" option
            confirmedNo.closest('.radio-option').style.display = 'none';
            confirmedNo.removeAttribute('data-was-checked');
        }
        
        // Show certificate section
        toggleConfirmationSections();
    }
}

/**
 * Validate required fields
 */
function validateRequiredFields() {
    let isValid = true;
    const requiredFields = document.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            markFieldAsInvalid(field);
            isValid = false;
        } else {
            markFieldAsValid(field);
        }
    });
    
    return isValid;
}

/**
 * Form submission validation
 */
function validateFormBeforeSubmit() {
    // Additional validation logic can be added here
    // This function should be called before form submission
    return validateRequiredFields();
}

/**
 * Mark field as invalid
 */
function markFieldAsInvalid(field) {
    field.classList.add('invalid');
    field.classList.remove('valid');
}

/**
 * Mark field as valid
 */
function markFieldAsValid(field) {
    field.classList.add('valid');
    field.classList.remove('invalid');
}

/**
 * Show validation errors
 */
function showValidationErrors() {
    const errorContainer = document.getElementById('validation-errors');
    if (errorContainer) {
        errorContainer.style.display = 'block';
        errorContainer.innerHTML = '<p>Please fill in all required fields.</p>';
        
        // Scroll to error container
        errorContainer.scrollIntoView({ behavior: 'smooth' });
    }
}

/**
 * Hide validation errors
 */
function hideValidationErrors() {
    const errorContainer = document.getElementById('validation-errors');
    if (errorContainer) {
        errorContainer.style.display = 'none';
    }
}

/**
 * Validate email format
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number format
 */
function validatePhoneNumber(phone) {
    const phoneRegex = /^[+]?[\d\s-()]+$/;
    return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

/**
 * Validate date fields
 */
function validateDateField(dateField) {
    const date = new Date(dateField.value);
    const today = new Date();
    
    // Check if date is valid and not in the future (for birth dates, etc.)
    return date instanceof Date && !isNaN(date) && date <= today;
}