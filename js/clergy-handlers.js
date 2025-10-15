// ======================================================================
// SIMPLIFIED CLERGY HANDLER - clergy-handler.js
// ======================================================================
// Global variables
window.clergyRolesData = window.clergyRolesData || [];
window.roleOptions = window.roleOptions || {};

// Load server-provided data from dataset (CSP-safe)
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('clergy-data');
    if (el) {
        try {
            const roles = el.dataset.roles ? JSON.parse(el.dataset.roles) : null;
            const options = el.dataset.roleOptions ? JSON.parse(el.dataset.roleOptions) : null;
            if (Array.isArray(roles) && (!window.clergyRolesData || window.clergyRolesData.length === 0)) {
                window.clergyRolesData = roles;
            }
            if (options && Object.keys(options).length > 0 && (!window.roleOptions || Object.keys(window.roleOptions).length === 0)) {
                window.roleOptions = options;
            }
        } catch (e) {
            console.warn('Failed to parse clergy dataset:', e);
        }
    }
});
let editingRoleId = null;
/**
 * Main function to toggle clergy details visibility
 * Called by main.js - this fixes your error!
 */
function toggleClergyDetails() {
    const yesRadio = document.querySelector('input[name="has_clergy_role"][value="yes"]');
    const details = document.getElementById('clergy_service_details');
    const existingRoles = document.getElementById('existing_clergy_roles');
    
    if (!yesRadio) return; // Exit gracefully if elements don't exist
    
    if (yesRadio.checked) {
        if (details) details.style.display = 'block';
        if (existingRoles) {
            existingRoles.style.display = 'block';
            loadExistingRoles();
        }
    } else {
        if (details) details.style.display = 'none';
        if (existingRoles) existingRoles.style.display = 'none';
        clearForms();
    }
}
/**
 * Setup all clergy event listeners
 */
function setupClergyListeners() {
    console.log('Setting up clergy listeners...');
    
    // Radio button listeners
    setupRadioListeners();
    
    // Form button listeners
    setupButtonListeners();
    
    // Form field listeners
    setupFieldListeners();
    
    // Initialize page state
    initializePageState();
}
/**
 * Setup radio button event listeners
 */
function setupRadioListeners() {
    document.querySelectorAll('input[name="has_clergy_role"]').forEach(radio => {
        radio.addEventListener('change', toggleClergyDetails);
    });
}
/**
 * Setup button event listeners
 */
function setupButtonListeners() {
    // Add role button
    const addBtn = document.getElementById('add_clergy_role_btn');
    if (addBtn) {
        addBtn.onclick = () => {
            editingRoleId = null;
            showForm();
            clearForm();
            showStatus('Fill in the role details below', 'info');
        };
    }
    
    // Save button
    const saveBtn = document.getElementById('save_clergy_role_btn');
    if (saveBtn) {
        saveBtn.onclick = (e) => {
            e.preventDefault();
            saveRole();
        };
    }
    
    // Cancel button
    const cancelBtn = document.getElementById('cancel_clergy_role_btn');
    if (cancelBtn) {
        cancelBtn.onclick = () => {
            hideForm();
            clearForm();
            editingRoleId = null;
            showStatus('', 'clear');
        };
    }
}
/**
 * Setup form field event listeners
 */
function setupFieldListeners() {
    // Role selection
    const roleSelect = document.getElementById('role_id');
    if (roleSelect) {
        roleSelect.onchange = () => {
            updateRoleName();
            if (!editingRoleId && roleSelect.value) {
                checkDuplicates(roleSelect.value);
            }
        };
    }
    
    // Current role checkbox
    const currentCheckbox = document.getElementById('is_current');
    if (currentCheckbox) {
        currentCheckbox.onchange = () => {
            const toDate = document.getElementById('serving_to_date');
            if (toDate) {
                if (currentCheckbox.checked) {
                    toDate.value = '';
                    toDate.disabled = true;
                    toDate.style.backgroundColor = '#e9ecef';
                } else {
                    toDate.disabled = false;
                    toDate.style.backgroundColor = '';
                }
            }
        };
    }
    
    // Date validation
    const fromDate = document.getElementById('serving_from_date');
    const toDate = document.getElementById('serving_to_date');
    if (fromDate) fromDate.onchange = validateDates;
    if (toDate) toDate.onchange = validateDates;
}
/**
 * Load and display existing roles
 */
function loadExistingRoles() {
    if (!window.clergyRolesData || window.clergyRolesData.length === 0) {
        showNoRoles();
        return;
    }
    
    const container = document.getElementById('existing_clergy_roles');
    if (!container) return;
    
    // Make sure the container is visible
    container.style.display = 'block';
    
    let html = '<div style="margin: 15px 0;"><h4>Your Current Clergy Roles</h4>';
    
    window.clergyRolesData.forEach((role, index) => {
        const displayName = role.display_role_name || getRoleName(role.role_id) || role.role_name || 'Unknown Role';
        const fromDate = role.serving_from_date || 'Not specified';
        const toDate = role.to_date || (role.is_current == 1 ? 'Current' : 'Not specified');
        const isCurrent = role.is_current == 1;
        
        html += createRoleHTML(role, displayName, fromDate, toDate, isCurrent, index);
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Attach event listeners to new buttons
    attachRoleButtons();
}
/**
 * Create HTML for a single role
 */
function createRoleHTML(role, roleName, fromDate, toDate, isCurrent, index) {
    return `
        <div class="role-item" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background-color: #f9f9f9;" data-role-id="${role.id}">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex-grow: 1;">
                    <strong style="font-size: 1.1em; color: #333; display: block;">${escapeHtml(roleName)}</strong>
                    <div style="margin: 5px 0; color: #666;"><strong>From:</strong> ${escapeHtml(fromDate)}</div>
                    <div style="margin: 5px 0; color: #666;"><strong>To:</strong> ${escapeHtml(toDate)}</div>
                    ${isCurrent ? '<div style="margin: 5px 0;"><span style="color: green; font-weight: bold;">✅ Currently Active</span></div>' : ''}
                </div>
                <div style="display: flex; gap: 5px;">
                    <button type="button" onclick="editRole('${role.id}', ${index})" 
                            style="padding: 5px 10px; background-color: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                        Edit
                    </button>
                    <button type="button" onclick="deleteRole('${role.id}', '${escapeHtml(roleName)}')"
                            style="padding: 5px 10px; background-color: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    `;
}
/**
 * Show no roles message
 */
function showNoRoles() {
    const container = document.getElementById('existing_clergy_roles');
    if (container) {
        container.innerHTML = `
            <div style="padding: 20px; text-align: center; background-color: #f8f9fa; border-radius: 5px; color: #6c757d; margin: 15px 0;">
                <p><strong>No clergy roles found.</strong></p>
                <p>Click "Add New Role" below to add your first clergy position.</p>
            </div>
        `;
        // Make sure container is visible
        container.style.display = 'block';
    }
}
/**
 * Save or update clergy role
 */
function saveRole() {
    if (!validateForm()) return;
    
    const saveBtn = document.getElementById('save_clergy_role_btn');
    const originalText = saveBtn ? saveBtn.textContent : 'Save Role';
    
    if (saveBtn) {
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
    }
    
    const formData = new FormData();
    formData.append('action', editingRoleId ? 'update' : 'save');
    formData.append('role_id', document.getElementById('role_id').value);
    formData.append('serving_from_date', document.getElementById('serving_from_date').value);
    
    if (editingRoleId) {
        formData.append('clergy_id', editingRoleId);
    }
    
    const toDate = document.getElementById('serving_to_date');
    const isCurrent = document.getElementById('is_current');
    
    if (toDate && toDate.value) {
        formData.append('serving_to_date', toDate.value);
    }
    
    if (isCurrent && isCurrent.checked) {
        formData.append('is_current', '1');
    }
    
    // Send to server
    fetch('../sections/uclergy_section.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const action = editingRoleId ? 'updated' : 'added';
            updateRolesData(data.role);
            clearForm();
            hideForm();
            editingRoleId = null;
            loadExistingRoles();
            showStatus(`Role ${action} successfully!`, 'success');
        } else {
            showStatus('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showStatus('Network error: ' + error.message, 'error');
    })
    .finally(() => {
        if (saveBtn) {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        }
    });
}
/**
 * Delete clergy role
 */
function deleteRole(roleId, roleName) {
    if (!confirm(`Are you sure you want to delete the "${roleName}" role?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('clergy_id', roleId);
    
    fetch('../sections/uclergy_section.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from data array
            window.clergyRolesData = window.clergyRolesData.filter(role => role.id != roleId);
            
            // Remove from DOM
            const roleItem = document.querySelector(`[data-role-id="${roleId}"]`);
            if (roleItem) {
                roleItem.remove();
            }
            
            // Check if any roles left
            if (window.clergyRolesData.length === 0) {
                showNoRoles();
                const noRadio = document.querySelector('input[name="has_clergy_role"][value="no"]');
                if (noRadio) {
                    noRadio.checked = true;
                    toggleClergyDetails();
                }
            }
            
            showStatus('Role deleted successfully', 'success');
        } else {
            showStatus('Error deleting role: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showStatus('Network error: ' + error.message, 'error');
    });
}
/**
 * Edit clergy role
 */
function editRole(roleId, index) {
    const roleData = window.clergyRolesData.find(role => role.id == roleId) || 
                     (window.clergyRolesData[index] || null);
    
    if (!roleData) {
        showStatus('Error: Could not load role data', 'error');
        return;
    }
    
    editingRoleId = roleId;
    showForm();
    populateForm(roleData);
    
    const saveBtn = document.getElementById('save_clergy_role_btn');
    if (saveBtn) saveBtn.textContent = 'Update Role';
    
    showStatus('Editing role. Make changes and click "Update Role".', 'info');
}
/**
 * Populate form with role data
 */
function populateForm(roleData) {
    const fields = {
        'role_id': roleData.role_id || '',
        'role_name': roleData.role_name || '',
        'serving_from_date': roleData.serving_from_date || '',
        'serving_to_date': roleData.to_date || ''
    };
    
    // Populate basic fields
    Object.keys(fields).forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) field.value = fields[fieldId];
    });
    
    // Handle current checkbox
    const currentCheckbox = document.getElementById('is_current');
    if (currentCheckbox) {
        currentCheckbox.checked = roleData.is_current == 1;
        currentCheckbox.dispatchEvent(new Event('change'));
    }
    
    updateRoleName();
}
/**
 * Validate form before saving
 */
function validateForm() {
    const roleSelect = document.getElementById('role_id');
    const fromDate = document.getElementById('serving_from_date');
    const toDate = document.getElementById('serving_to_date');
    const isCurrent = document.getElementById('is_current');
    
    if (!roleSelect || !roleSelect.value) {
        showStatus('Please select a role', 'error');
        if (roleSelect) roleSelect.focus();
        return false;
    }
    
    if (!fromDate || !fromDate.value) {
        showStatus('Please enter a start date', 'error');
        if (fromDate) fromDate.focus();
        return false;
    }
    
    if (!isCurrent?.checked && (!toDate || !toDate.value)) {
        showStatus('Please enter an end date or check "Currently serving"', 'error');
        if (toDate) toDate.focus();
        return false;
    }
    
    return validateDates();
}
/**
 * Validate date range
 */
function validateDates() {
    const fromDate = document.getElementById('serving_from_date');
    const toDate = document.getElementById('serving_to_date');
    const isCurrent = document.getElementById('is_current');
    
    if (!fromDate?.value || !toDate?.value || isCurrent?.checked) {
        return true;
    }
    
    if (new Date(toDate.value) <= new Date(fromDate.value)) {
        showStatus('End date must be after start date', 'error');
        return false;
    }
    
    return true;
}
/**
 * Update role name from selection
 */
function updateRoleName() {
    const roleSelect = document.getElementById('role_id');
    const roleNameField = document.getElementById('role_name');
    
    if (roleSelect && roleNameField) {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const roleName = selectedOption.getAttribute('data-role-name') || selectedOption.textContent;
        
        if (selectedOption.value && roleName !== 'Select a role...') {
            roleNameField.value = roleName;
        } else {
            roleNameField.value = '';
        }
    }
}
/**
 * Check for duplicate roles
 */
function checkDuplicates(roleId) {
    const existing = window.clergyRolesData.filter(role => 
        role.role_id == roleId && role.is_current == 1
    );
    
    const warningDiv = document.getElementById('duplicate-warning');
    if (warningDiv) warningDiv.remove();
    
    if (existing.length > 0) {
        const roleName = getRoleName(roleId);
        const warning = document.createElement('div');
        warning.id = 'duplicate-warning';
        warning.style.cssText = 'margin-top: 10px; padding: 12px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404; font-size: 14px;';
        warning.innerHTML = `<strong>⚠️ Warning:</strong> You already have an active "${roleName}" role.`;
        
        const roleSelect = document.getElementById('role_id');
        if (roleSelect?.parentNode) {
            roleSelect.parentNode.appendChild(warning);
        }
    }
}
/**
 * Get role name by ID
 */
function getRoleName(roleId) {
    if (window.roleOptions && window.roleOptions[roleId]) {
        return window.roleOptions[roleId];
    }
    
    const roleSelect = document.getElementById('role_id');
    if (roleSelect) {
        const option = roleSelect.querySelector(`option[value="${roleId}"]`);
        if (option) {
            return option.getAttribute('data-role-name') || option.textContent;
        }
    }
    
    return null;
}
/**
 * Update roles data after save/edit
 */
function updateRolesData(newRoleData) {
    if (!newRoleData) return;
    
    if (editingRoleId) {
        const index = window.clergyRolesData.findIndex(role => role.id == editingRoleId);
        if (index !== -1) {
            window.clergyRolesData[index] = newRoleData;
        }
    } else {
        if (!window.clergyRolesData) window.clergyRolesData = [];
        window.clergyRolesData.push(newRoleData);
    }
}
/**
 * Attach event listeners to role buttons
 */
function attachRoleButtons() {
    // This function is called after DOM is updated
    // The onclick handlers are inline in the HTML for simplicity
}
/**
 * Initialize page state
 */
function initializePageState() {
    const hasRoles = window.clergyRolesData && window.clergyRolesData.length > 0;
    const yesRadio = document.querySelector('input[name="has_clergy_role"][value="yes"]');
    const noRadio = document.querySelector('input[name="has_clergy_role"][value="no"]');
    
    if (hasRoles && yesRadio && !yesRadio.checked && !noRadio?.checked) {
        yesRadio.checked = true;
    } else if (!hasRoles && noRadio && !yesRadio?.checked && !noRadio.checked) {
        noRadio.checked = true;
    }
    
    // Trigger initial state
    const checked = document.querySelector('input[name="has_clergy_role"]:checked');
    if (checked) {
        toggleClergyDetails();
    }
}
// Utility Functions
function showForm() {
    const form = document.getElementById('clergy_add_role_form');
    const btn = document.getElementById('add_clergy_role_btn');
    if (form) form.style.display = 'block';
    if (btn) btn.style.display = 'none';
}
function hideForm() {
    const form = document.getElementById('clergy_add_role_form');
    const btn = document.getElementById('add_clergy_role_btn');
    if (form) form.style.display = 'none';
    if (btn) btn.style.display = 'block';
}
function clearForm() {
    const fields = ['role_id', 'role_name', 'serving_from_date', 'serving_to_date'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = field.tagName === 'SELECT' ? '' : '';
            if (field.tagName === 'SELECT') field.selectedIndex = 0;
        }
    });
    
    const currentCheckbox = document.getElementById('is_current');
    if (currentCheckbox) {
        currentCheckbox.checked = false;
        currentCheckbox.dispatchEvent(new Event('change'));
    }
    
    const warning = document.getElementById('duplicate-warning');
    if (warning) warning.remove();
}
function clearForms() {
    clearForm();
    hideForm();
    editingRoleId = null;
    showStatus('', 'clear');
}
function showStatus(message, type) {
    const statusDiv = document.getElementById('clergy-save-status');
    if (!statusDiv) return;
    
    if (type === 'clear' || !message) {
        statusDiv.innerHTML = '';
        return;
    }
    
    const colors = {
        success: { color: '#155724', bg: '#d4edda', border: '#c3e6cb', icon: '✅' },
        error: { color: '#721c24', bg: '#f8d7da', border: '#f5c6cb', icon: '❌' },
        info: { color: '#0c5460', bg: '#d1ecf1', border: '#bee5eb', icon: 'ℹ️' },
        warning: { color: '#856404', bg: '#fff3cd', border: '#ffeaa7', icon: '⚠️' }
    };
    
    const style = colors[type] || colors.info;
    
    statusDiv.innerHTML = `
        <div style="color: ${style.color}; background-color: ${style.bg}; border: 1px solid ${style.border}; border-radius: 4px; padding: 10px 12px; margin: 10px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            <span>${style.icon}</span>
            <span>${message}</span>
        </div>
    `;
    
    if (type !== 'error') {
        setTimeout(() => {
            if (statusDiv.innerHTML.includes(message)) {
                statusDiv.innerHTML = '';
            }
        }, 5000);
    }
}
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
// Make functions globally available
window.toggleClergyDetails = toggleClergyDetails;
window.setupClergyListeners = setupClergyListeners;
window.editRole = editRole;
window.deleteRole = deleteRole;
console.log('Clergy handler loaded successfully');