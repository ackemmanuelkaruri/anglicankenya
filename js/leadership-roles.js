/**
 * Leadership Roles Management
 * Manages adding, removing, and handling leadership role forms
 */

// Global counter for leadership roles
if (typeof window.leadershipRoleCounter === 'undefined') {
    window.leadershipRoleCounter = 0;
}

/**
 * Add a new leadership role section
 */
function addLeadershipRole() {
    console.log('Add leadership role clicked');
    window.leadershipRoleCounter++;
    
    // Get the container and the first role template
    const container = document.getElementById('leadership-roles-container');
    const firstRole = container?.querySelector('.leadership-role');
    
    if (!container || !firstRole) {
        console.error('Container or first role not found');
        return;
    }
    
    // Clone the first role
    const newRole = firstRole.cloneNode(true);
    
    // Update the role ID and number
    newRole.dataset.roleId = window.leadershipRoleCounter;
    const roleNumber = newRole.querySelector('.role-number');
    if (roleNumber) roleNumber.textContent = window.leadershipRoleCounter;
    
    // Reset all form fields
    newRole.querySelectorAll('input, select').forEach(field => {
        if (field.type === 'checkbox') {
            field.checked = false;
        } else {
            field.value = '';
        }
    });
    
    // Update all IDs to be unique
    newRole.querySelectorAll('[id]').forEach(element => {
        const oldId = element.id;
        const newId = oldId.replace(/_\d+$/, `_${window.leadershipRoleCounter}`);
        element.id = newId;
        
        // Update label for attributes
        const label = newRole.querySelector(`label[for="${oldId}"]`);
        if (label) label.setAttribute('for', newId);
    });
    
    // Hide department and ministry options initially
    const departmentOptions = newRole.querySelector('.department-options');
    const ministryOptions = newRole.querySelector('.ministry-options');
    const otherRoleField = newRole.querySelector('.other-role-field');
    
    if (departmentOptions) departmentOptions.style.display = 'none';
    if (ministryOptions) ministryOptions.style.display = 'none';
    if (otherRoleField) otherRoleField.style.display = 'none';
    
    // Show remove button
    const removeButton = newRole.querySelector('.btn-remove-role');
    if (removeButton) removeButton.style.display = 'block';
    
    // Add event listeners
    const leadershipType = newRole.querySelector('.leadership-type');
    if (leadershipType) {
        leadershipType.addEventListener('change', function() {
            updateLeadershipOptions(this);
        });
    }
    
    const roleSelect = newRole.querySelector('.leadership-role-select');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            toggleOtherRoleField(this);
        });
    }
    
    const currentCheckbox = newRole.querySelector('.is-current-leadership');
    if (currentCheckbox) {
        currentCheckbox.addEventListener('change', function() {
            toggleCurrentLeadership(this);
        });
    }
    
    // Append to container
    container.appendChild(newRole);
    
    // Show all remove buttons if there's more than one role
    const allRoles = container.querySelectorAll('.leadership-role');
    if (allRoles.length > 1) {
        allRoles.forEach(role => {
            const btn = role.querySelector('.btn-remove-role');
            if (btn) btn.style.display = 'block';
        });
    }
    
    console.log('Added new leadership role, total roles:', allRoles.length);
}

/**
 * Remove a leadership role section
 */
function removeLeadershipRole(button) {
    console.log('Remove leadership role clicked');
    const roleDiv = button.closest('.leadership-role');
    const container = document.getElementById('leadership-roles-container');
    
    if (!roleDiv || !container) return;
    
    // Remove the role div
    container.removeChild(roleDiv);
    
    // Update role numbers
    const roles = container.querySelectorAll('.leadership-role');
    roles.forEach((role, index) => {
        const roleNumber = role.querySelector('.role-number');
        if (roleNumber) roleNumber.textContent = index + 1;
    });
    
    // Hide remove button if only one role remains
    if (roles.length === 1) {
        const removeButton = roles[0].querySelector('.btn-remove-role');
        if (removeButton) removeButton.style.display = 'none';
    }
    
    console.log('Removed leadership role, remaining roles:', roles.length);
}

/**
 * Update department/ministry options based on selection
 */
function updateLeadershipOptions(select) {
    console.log('Update leadership options:', select.value);
    const roleDiv = select.closest('.leadership-role');
    const departmentOptions = roleDiv?.querySelector('.department-options');
    const ministryOptions = roleDiv?.querySelector('.ministry-options');
    
    if (departmentOptions) departmentOptions.style.display = 'none';
    if (ministryOptions) ministryOptions.style.display = 'none';
    
    if (select.value === 'department') {
        if (departmentOptions) departmentOptions.style.display = 'block';
        const ministrySelect = roleDiv?.querySelector('.leadership-ministry');
        if (ministrySelect) ministrySelect.value = '';
    } else if (select.value === 'ministry') {
        if (ministryOptions) ministryOptions.style.display = 'block';
        const departmentSelect = roleDiv?.querySelector('.leadership-department');
        if (departmentSelect) departmentSelect.value = '';
    }
}

/**
 * Toggle other role field visibility
 */
function toggleOtherRoleField(select) {
    console.log('Toggle other role field:', select.value);
    const roleDiv = select.closest('.leadership-role');
    const otherRoleField = roleDiv?.querySelector('.other-role-field');
    const otherRoleInput = roleDiv?.querySelector('.other-leadership-role');
    
    if (otherRoleField) {
        otherRoleField.style.display = select.value === 'OTHER' ? 'block' : 'none';
        
        if (select.value !== 'OTHER' && otherRoleInput) {
            otherRoleInput.value = '';
        }
    }
}

/**
 * Toggle current leadership date field
 */
function toggleCurrentLeadership(checkbox) {
    console.log('Toggle current leadership:', checkbox.checked);
    const roleDiv = checkbox.closest('.leadership-role');
    const toDateInput = roleDiv?.querySelector('.leadership-to-date');
    
    if (toDateInput) {
        toDateInput.disabled = checkbox.checked;
        if (checkbox.checked) {
            toDateInput.value = '';
        }
    }
}

/**
 * Save leadership roles
 */
function saveLeadershipRoles() {
    console.log('Save leadership roles clicked');
    const saveButton = document.querySelector('.btn-save-section[data-section="leadership"]');
    const originalText = saveButton ? saveButton.textContent : 'Save Leadership Roles';
    
    if (saveButton) {
        saveButton.textContent = 'Saving...';
        saveButton.disabled = true;
    }
    
    // Collect all leadership role data
    const leadershipRoles = document.querySelectorAll('.leadership-role');
    const formData = new FormData();
    
    leadershipRoles.forEach((role, index) => {
        const leadershipType = role.querySelector('.leadership-type')?.value || '';
        const department = role.querySelector('.leadership-department')?.value || '';
        const ministry = role.querySelector('.leadership-ministry')?.value || '';
        const roleValue = role.querySelector('.leadership-role-select')?.value || '';
        const otherRole = role.querySelector('.other-leadership-role')?.value || '';
        const fromDate = role.querySelector('.leadership-from-date')?.value || '';
        const toDate = role.querySelector('.leadership-to-date')?.value || '';
        const isCurrent = role.querySelector('.is-current-leadership')?.checked || false;
        
        formData.append('leadership_type[]', leadershipType);
        formData.append('leadership_department[]', department);
        formData.append('leadership_ministry[]', ministry);
        formData.append('leadership_role[]', roleValue);
        formData.append('other_leadership_role[]', otherRole);
        formData.append('leadership_from_date[]', fromDate);
        formData.append('leadership_to_date[]', toDate);
        
        if (isCurrent) {
            formData.append('is_current_leadership[]', index.toString());
        }
    });
    
    // Add section identifier
    formData.append('section_type', 'leadership');
    formData.append('section', 'leadership');
    
    // Get user ID
    let userId = getUserId();
    if (!userId) {
        console.error('User ID not found - cannot save data');
        showLeadershipStatus('error', 'User authentication error. Please refresh the page and try again.');
        if (saveButton) {
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }
        return;
    }
    formData.append('id', userId);
    
    // Debug: Log what we're sending
    console.log('Saving leadership section');
    console.log('User ID:', userId);
    
    // Send save request
    const correctPath = '../sections/uleadership_section.php';
    
    fetch(correctPath, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log(`Response from ${correctPath}:`, response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log(`Raw response from ${correctPath}:`, text);
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                showLeadershipStatus('success', data.message || 'Leadership roles saved successfully!');
                
                // Refresh the page to show the updated leadership summary
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showLeadershipStatus('error', data.message || 'Error saving leadership roles');
            }
        } catch (parseError) {
            console.error('Error parsing response:', parseError);
            console.error('Raw response:', text);
            showLeadershipStatus('error', 'Server returned invalid response');
        }
    })
    .catch(error => {
        console.error('Error saving leadership roles:', error);
        showLeadershipStatus('error', 'Network error: ' + error.message);
    })
    .finally(() => {
        if (saveButton) {
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }
    });
}

/**
 * Show status message for leadership section
 */
function showLeadershipStatus(type, message) {
    // Create or update status element
    let statusElement = document.getElementById('leadership-status');
    
    if (!statusElement) {
        statusElement = document.createElement('div');
        statusElement.id = 'leadership-status';
        const leadershipSection = document.querySelector('.leadership-section');
        if (leadershipSection) {
            leadershipSection.appendChild(statusElement);
        }
    }
    
    if (type === 'clear' || !message) {
        statusElement.innerHTML = '';
        return;
    }
    
    const colors = {
        success: { color: '#155724', bg: '#d4edda', border: '#c3e6cb', icon: '✅' },
        error: { color: '#721c24', bg: '#f8d7da', border: '#f5c6cb', icon: '❌' },
        info: { color: '#0c5460', bg: '#d1ecf1', border: '#bee5eb', icon: 'ℹ️' },
        warning: { color: '#856404', bg: '#fff3cd', border: '#ffeaa7', icon: '⚠️' }
    };
    
    const style = colors[type] || colors.info;
    
    statusElement.innerHTML = `
        <div style="color: ${style.color}; background-color: ${style.bg}; border: 1px solid ${style.border}; border-radius: 4px; padding: 10px 12px; margin: 10px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
            <span>${style.icon}</span>
            <span>${message}</span>
        </div>
    `;
    
    if (type !== 'error') {
        setTimeout(() => {
            if (statusElement.innerHTML.includes(message)) {
                statusElement.innerHTML = '';
            }
        }, 5000);
    }
}

/**
 * Get user ID from various possible sources
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

// Make functions globally available
window.addLeadershipRole = addLeadershipRole;
window.removeLeadershipRole = removeLeadershipRole;
window.updateLeadershipOptions = updateLeadershipOptions;
window.toggleOtherRoleField = toggleOtherRoleField;
window.toggleCurrentLeadership = toggleCurrentLeadership;
window.saveLeadershipRoles = saveLeadershipRoles;

console.log('Leadership roles handler loaded successfully');