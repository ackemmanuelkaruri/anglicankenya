// ==============
// EMPLOYMENT ROLE FUNCTIONS - SIMPLIFIED VERSION WITHOUT MODAL
// FIXED: Proper handling of employment ID for new records
// ==============

// CRITICAL FIX: Assign functions to global scope IMMEDIATELY
window.deleteEmploymentFromDatabase = function(button, employmentId, jobTitle, company) {
    console.log('Global deleteEmploymentFromDatabase called');
    return deleteEmploymentFromDatabaseImpl(button, employmentId, jobTitle, company);
};
window.addEmploymentRole = function() {
    console.log('Global addEmploymentRole called');
    return addEmploymentRoleImpl();
};

// Global counter for employment roles - Initialize safely
if (typeof window.employmentRoleCounter === 'undefined') {
    window.employmentRoleCounter = 1;
}

/**
 * Add employment role - Updated for accessibility and proper ID management
 */
function addEmploymentRoleImpl() {
    const container = document.getElementById('employment-roles-container');
    const firstRole = container?.querySelector('.employment-role');
    if (!container || !firstRole) {
        console.error('Employment container or first role not found');
        return;
    }
    window.employmentRoleCounter++;
    // Clone the first employment role div
    const newRole = firstRole.cloneNode(true);
    // Update dataset attributes
    newRole.dataset.roleId = window.employmentRoleCounter;
    newRole.dataset.employmentId = 'new'; // Mark as new role
    // Update role number display
    const roleNumber = newRole.querySelector('.role-number');
    if (roleNumber) roleNumber.textContent = window.employmentRoleCounter;
    // Reset and update all form fields with proper IDs and labels
    updateFormFields(newRole, window.employmentRoleCounter);
    // Setup remove button for frontend removal of new roles
    setupRemoveButton(newRole);
    // Add event listener for current employment checkbox inside the new role
    const currentCheckbox = newRole.querySelector('.is-current-employment');
    if (currentCheckbox) {
        currentCheckbox.addEventListener('change', function () {
            toggleCurrentEmployment(this);
        });
        // Set initial toggle state
        toggleCurrentEmployment(currentCheckbox);
    }
    // Append the new role to the container
    container.appendChild(newRole);
    // Update remove buttons visibility and styles
    updateRemoveButtons();
    // Scroll smoothly to the new role
    newRole.scrollIntoView({ behavior: 'smooth' });
    console.log('Added employment role:', window.employmentRoleCounter);
}

/**
 * Delete employment from the database - SIMPLIFIED VERSION WITHOUT MODAL
 */
function deleteEmploymentFromDatabaseImpl(button, employmentId, jobTitle, company) {
    // Prevent any form submission or navigation
    if (window.event) {
        window.event.preventDefault();
        window.event.stopPropagation();
        window.event.stopImmediatePropagation();
    }
    if (button) { 
        button.blur(); 
    }
    
    console.log('Delete button clicked - preventing form submission');
    console.log('Employment ID:', employmentId, 'Job Title:', jobTitle, 'Company:', company);
    
    // Only treat null, undefined, empty string, or 'new' as unsaved records
     if (employmentId === null || 
        employmentId === undefined || 
        employmentId === '' || 
        employmentId === 'new' ||
        employmentId === '0') { // Add this check for zero IDs
        
        console.log('Detected unsaved employment record, removing from form only');
        
        // For truly unsaved records, just remove from the DOM
        const roleDiv = button.closest('.employment-role');
        const container = document.getElementById('employment-roles-container');
        
        if (!roleDiv || !container) {
            alert('❌ Error: Could not find employment record to remove.');
            return false;
        }
        
        const roles = container.querySelectorAll('.employment-role');
        if (roles.length <= 1) {
            alert('⚠️ You must have at least one employment role.');
            return false;
        }
        
        // Show confirmation for unsaved record
        if (confirm(`Remove this unsaved employment record?\n\n"${jobTitle}" at "${company}"\n\nThis will only remove it from the form.`)) {
            roleDiv.remove();
            updateRoleNumbers();
            updateRemoveButtons();
            console.log('Unsaved employment role removed from form');
        }
        
        return false;
    }
    
    // For all database records (ID 1, 2, 3, etc.), proceed with database deletion
    const idString = String(employmentId);
    
    // Use browser confirm instead of modal
    if (confirm(`Are you sure you want to permanently delete this employment record from the database?\n\n"${jobTitle}" at "${company}"\n\nThis action cannot be undone.`)) {
        // User confirmed, proceed with deletion
        performEmploymentDeletion(button, idString, jobTitle, company);
    }
    
    return false;
}

/**
 * Perform employment deletion - SIMPLIFIED VERSION
 */
function performEmploymentDeletion(button, employmentId, jobTitle, company) {
    console.log('performEmploymentDeletion called');
    console.log('Employment ID:', employmentId);
    
    const roleDiv = button.closest('.employment-role');
    const userId = getUserId();
    
    if (!userId) {
        alert('❌ Error: User ID not found. Please refresh the page and try again.');
        return;
    }
    
    // Show loading state on button
    const originalText = button.innerHTML;
    button.innerHTML = '⏳ Deleting...';
    button.disabled = true;
    
    console.log('Attempting to delete employment ID:', employmentId, 'for user:', userId);
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('employment_id', employmentId);
    
    // FIXED: Updated paths to include the sections directory
    const possiblePaths = [
        '../sections/delete_employment.php',  // Most likely if current page is in user/ directory
        'sections/delete_employment.php',     // If sections is in current directory
        '/emmanuelkaruri/sections/delete_employment.php',  // Absolute path
        '/sections/delete_employment.php'     // Root sections directory
    ];
    
    // Try each path until one works
    tryPath(possiblePaths, 0, formData, button, originalText, roleDiv);
}

/**
 * Try different paths for the delete_employment.php file
 */
function tryPath(paths, index, formData, button, originalText, roleDiv) {
    if (index >= paths.length) {
        // All paths failed
        alert('❌ Error: Could not connect to the server to delete the employment record. Please contact the administrator.');
        button.innerHTML = originalText;
        button.disabled = false;
        return;
    }
    
    const currentPath = paths[index];
    console.log('Trying path:', currentPath);
    
    fetch(currentPath, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status for path', currentPath, ':', response.status);
        
        // If we get a 404, try the next path
        if (response.status === 404) {
            console.log('Path not found, trying next path...');
            tryPath(paths, index + 1, formData, button, originalText, roleDiv);
            return;
        }
        
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned invalid response format. Expected JSON.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        // If data is undefined, it means we're trying a different path
        if (!data) return;
        
        console.log('Delete response:', data);
        
        if (data.success) {
            // Remove the employment role from DOM
            if (roleDiv && roleDiv.parentNode) {
                roleDiv.remove();
                updateRoleNumbers();
                updateRemoveButtons();
                console.log('Employment role removed from DOM');
            }
            
            // Show success message
            alert('✅ ' + (data.message || 'Employment record deleted successfully from database.'));
        } else {
            console.error('Delete failed:', data);
            alert('❌ Error: ' + (data.message || 'Unknown error occurred during deletion.'));
        }
        
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    })
    .catch(error => {
        console.error('Delete error:', error);
        
        // If it's a 404 error, try the next path
        if (error.message.includes('404')) {
            console.log('Path not found, trying next path...');
            tryPath(paths, index + 1, formData, button, originalText, roleDiv);
            return;
        }
        
        alert('❌ An error occurred while deleting the employment record: ' + error.message);
        
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Fix in the updateFormFields function
function updateFormFields(roleElement, roleId) {
    roleElement.querySelectorAll('input').forEach(input => {
        if (input.type === 'text' || input.type === 'date' || input.type === 'email') {
            input.value = '';
            updateFieldId(input, roleId);
        } else if (input.type === 'hidden' && input.name === 'employment_id[]') {
            // FIXED: For new records, leave empty so PHP can handle auto-increment
            input.value = ''; // Empty string for new records
            // Alternative: input.removeAttribute('value'); // Remove value attribute entirely
        } else if (input.type === 'checkbox') {
            input.checked = false;
            updateCheckboxId(input, roleId);
        }
    });
    
    roleElement.querySelectorAll('select').forEach(select => {
        select.value = '';
        updateFieldId(select, roleId);
    });
    
    roleElement.querySelectorAll('textarea').forEach(textarea => {
        textarea.value = '';
        updateFieldId(textarea, roleId);
    });
    
    updateLabels(roleElement);
}

/**
 * Update a field's ID for accessibility and uniqueness
 */
function updateFieldId(field, roleId) {
    if (field.name && !field.name.includes('is_current_employment')) {
        const baseName = field.name.replace('[]', '').replace(/[^a-zA-Z0-9_]/g, '_');
        field.id = `${baseName}_${roleId}`;
    }
}

/**
 * Update checkbox ID and value as well as its related label
 */
function updateCheckboxId(checkbox, roleId) {
    const oldId = checkbox.id;
    if (oldId) {
        const newId = oldId.replace(/\d+$/, '') + roleId;
        checkbox.id = newId;
        checkbox.value = roleId - 1; // Update value as per array indexing
        const roleElement = checkbox.closest('.employment-role');
        const label = roleElement.querySelector(`label[for="${oldId}"]`);
        if (label) label.setAttribute('for', newId);
    }
}

/**
 * Update labels to match updated input/select/textarea IDs
 */
function updateLabels(roleElement) {
    roleElement.querySelectorAll('label[for]').forEach(label => {
        const forAttr = label.getAttribute('for');
        if (forAttr && !forAttr.includes('is_current_employment')) {
            const input = roleElement.querySelector(`input[id="${forAttr}"], select[id="${forAttr}"], textarea[id="${forAttr}"]`);
            if (input && input.id) {
                label.setAttribute('for', input.id);
            }
        }
    });
}

/**
 * Setup remove button for new roles (frontend removal)
 */
function setupRemoveButton(roleElement) {
    const removeButton = roleElement.querySelector('.btn-remove-employment, .btn-remove-employment-role');
    if (removeButton) {
        removeButton.style.display = 'inline-block';
        removeButton.className = 'btn-remove-employment btn-remove-from-form';
        removeButton.style.backgroundColor = '#ffeeee';
        removeButton.style.color = '#e60000';
        removeButton.innerHTML = '✖️ Remove This Role';
        removeButton.removeAttribute('data-employment-id');
        removeButton.removeAttribute('onclick');
        removeButton.onclick = function (e) {
            e.preventDefault();
            e.stopPropagation();
            removeEmploymentRole(this);
            return false;
        };
    }
}

/**
 * Remove employment role from DOM (frontend only for unsaved roles)
 */
function removeEmploymentRole(button) {
    const roleDiv = button.closest('.employment-role');
    const container = document.getElementById('employment-roles-container');
    if (!roleDiv || !container) {
        console.error('Role div or container not found');
        return;
    }
    
    const roles = container.querySelectorAll('.employment-role');
    if (roles.length <= 1) {
        alert('⚠️ You must have at least one employment role.');
        return;
    }
    
    roleDiv.remove();
    updateRoleNumbers();
    updateRemoveButtons();
    console.log('Removed employment role from form');
}

/**
 * Get user ID with multiple fallback methods
 */
function getUserId() {
    if (typeof window.currentUserId !== 'undefined' && window.currentUserId) {
        return window.currentUserId;
    }
    
    const userIdInput = document.querySelector('input[name="id"], input[name="user_id"]');
    if (userIdInput && userIdInput.value) {
        return userIdInput.value;
    }
    
    const userDataElement = document.querySelector('[data-user-id]');
    if (userDataElement && userDataElement.dataset.userId) {
        return userDataElement.dataset.userId;
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('user_id')) {
        return urlParams.get('user_id');
    }
    
    return null;
}

/**
 * Update role numbers and related IDs after add/remove
 */
function updateRoleNumbers() {
    const roles = document.querySelectorAll('.employment-role');
    roles.forEach((role, index) => {
        const roleNumber = role.querySelector('.role-number');
        if (roleNumber) {
            roleNumber.textContent = index + 1;
        }
        
        role.dataset.roleId = index + 1;
        updateRoleCheckbox(role, index);
        updateRoleInputIds(role, index);
    });
    
    window.employmentRoleCounter = roles.length;
}

/**
 * Update checkbox and label for a specific employment role
 */
function updateRoleCheckbox(role, index) {
    const checkbox = role.querySelector('.is-current-employment');
    const label = role.querySelector('label[for^="is_current_employment_"]');
    
    if (checkbox && label) {
        const newId = `is_current_employment_${index + 1}`;
        checkbox.id = newId;
        checkbox.value = index;
        label.setAttribute('for', newId);
    }
}

/**
 * Update input IDs and their labels for a specific role
 */
function updateRoleInputIds(role, index) {
    role.querySelectorAll('input[name]:not(.is-current-employment), select[name], textarea[name]').forEach(field => {
        if (field.name && field.type !== 'hidden') {
            const baseName = field.name.replace('[]', '').replace(/[^a-zA-Z0-9_]/g, '_');
            field.id = `${baseName}_${index + 1}`;
            const fieldLabel = role.querySelector(`label[for^="${baseName}_"]`);
            if (fieldLabel) {
                fieldLabel.setAttribute('for', field.id);
            }
        }
    });
}

/**
 * Update remove buttons visibility based on count of roles
 */
function updateRemoveButtons() {
    const roles = document.querySelectorAll('.employment-role');
    const removeButtons = document.querySelectorAll('.btn-remove-from-form, .btn-remove-employment-role');
    
    removeButtons.forEach(button => {
        button.style.display = roles.length > 1 ? 'inline-block' : 'none';
    });
}

/**
 * Enable/disable "to date" input based on "current employment" checkbox
 */
function toggleCurrentEmployment(checkbox) {
    const roleDiv = checkbox.closest('.employment-role');
    const toDateInput = roleDiv.querySelector('.employment-to-date');
    
    if (toDateInput) {
        toDateInput.disabled = checkbox.checked;
        toDateInput.required = !checkbox.checked;
        
        if (checkbox.checked) {
            toDateInput.value = '';
        }
    }
}

/**
 * Handle current employment checkbox change event
 */
function handleCurrentEmploymentChange(e) {
    toggleCurrentEmployment(e.target);
}

/**
 * Simplified initialization without modal setup
 */
function initEmploymentFunctionality() {
    // Prevent double initialization
    if (window.employmentFunctionalityInitialized) {
        console.log('Employment functionality already initialized, skipping...');
        return;
    }
    
    console.log('Initializing employment functionality...');
    
    const existingRoles = document.querySelectorAll('.employment-role');
    if (existingRoles.length > 0) {
        window.employmentRoleCounter = existingRoles.length;
        updateRemoveButtons();
        updateRoleNumbers();
    }
    
    existingRoles.forEach(role => {
        const currentCheckbox = role.querySelector('.is-current-employment');
        if (currentCheckbox) {
            toggleCurrentEmployment(currentCheckbox);
            currentCheckbox.removeEventListener('change', handleCurrentEmploymentChange);
            currentCheckbox.addEventListener('change', handleCurrentEmploymentChange);
        }
    });
    
    // Event delegation for delete and remove buttons
    const container = document.getElementById('employment-roles-container');
    if (container) {
        container.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete-from-db');
            if (deleteBtn) {
                e.preventDefault();
                const employmentId = deleteBtn.getAttribute('data-employment-id');
                const jobTitle = deleteBtn.getAttribute('data-job-title') || '';
                const company = deleteBtn.getAttribute('data-company') || '';
                deleteEmploymentFromDatabaseImpl(deleteBtn, employmentId, jobTitle, company);
                return;
            }
            const removeBtn = e.target.closest('.btn-remove-from-form, .btn-remove-employment-role');
            if (removeBtn) {
                e.preventDefault();
                removeEmploymentRole(removeBtn);
                return;
            }
        });
    }
    
    // Wire up add role button without inline handler
    const addBtn = document.querySelector('.btn-add-employment');
    if (addBtn) {
        addBtn.addEventListener('click', addEmploymentRoleImpl);
    }
    
    // Mark as initialized
    window.employmentFunctionalityInitialized = true;
    console.log('Employment functionality initialized with', existingRoles.length, 'existing roles');
}

// Initialize when DOM is loaded - with duplicate prevention
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmploymentFunctionality);
} else {
    // Small delay to prevent race conditions with other scripts
    setTimeout(initEmploymentFunctionality, 100);
}

/**
 * Enhanced debug function to check employment IDs
 */
function debugEmploymentIds() {
    console.log('=== EMPLOYMENT DEBUG INFO ===');
    const roles = document.querySelectorAll('.employment-role');
    
    roles.forEach((role, index) => {
        console.log(`Role ${index + 1}:`);
        
        // Check for hidden employment ID input
        const hiddenId = role.querySelector('input[name="employment_id[]"]');
        const idValue = hiddenId ? hiddenId.value : 'Not found';
        console.log('  Hidden ID input value:', idValue);
        console.log('  Hidden ID input type:', typeof idValue);
        console.log('  Is ID empty string?', idValue === '');
        console.log('  Is ID null?', idValue === null);
        console.log('  Is ID undefined?', idValue === undefined);
        console.log('  Is ID "new"?', idValue === 'new');
        console.log('  Will be treated as database record?', !(idValue === null || idValue === undefined || idValue === '' || idValue === 'new'));
        
        // Check for data attributes
        console.log('  Role data-employment-id:', role.dataset.employmentId);
        
        // Check delete button parameters
        const deleteBtn = role.querySelector('[onclick*="deleteEmploymentFromDatabase"]');
        if (deleteBtn) {
            const onclickAttr = deleteBtn.getAttribute('onclick');
            console.log('  Delete button onclick:', onclickAttr);
            
            // Extract parameters from onclick
            const match = onclickAttr.match(/deleteEmploymentFromDatabase\(this,\s*([^,]+),\s*'([^']*)',\s*'([^']*)'\)/);
            if (match) {
                console.log('  Extracted ID:', match[1]);
                console.log('  Extracted Job Title:', match[2]);
                console.log('  Extracted Company:', match[3]);
            }
        }
        
        // Check form field values
        const jobTitle = role.querySelector('input[name="job_title[]"]');
        const company = role.querySelector('input[name="company[]"]');
        console.log('  Job Title:', jobTitle ? jobTitle.value : 'Not found');
        console.log('  Company:', company ? company.value : 'Not found');
        console.log('  ---');
    });
    
    console.log('Global variables:');
    console.log('  employmentIdToDelete:', employmentIdToDelete);
    console.log('  employmentRoleToRemove:', employmentRoleToRemove);
    console.log('=== END DEBUG INFO ===');
}

// Make debug function available globally
window.debugEmploymentIds = debugEmploymentIds;

// CRITICAL: Test that functions are available immediately
console.log('Functions available check:');
console.log('deleteEmploymentFromDatabase:', typeof window.deleteEmploymentFromDatabase);
console.log('addEmploymentRole:', typeof window.addEmploymentRole);