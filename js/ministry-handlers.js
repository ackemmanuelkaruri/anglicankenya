/**
 * Ministry Selection Handlers
 * Manages ministry and department selection functionality
 */
// Global variables
window.userMinistryData = window.userMinistryData || { departments: [], ministries: [] };
window.departmentOptions = window.departmentOptions || [];
window.ministryOptions = window.ministryOptions || [];

// Load server-provided data from dataset (CSP-safe)
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('ministry-data');
    if (el) {
        try {
            const userMinistry = el.dataset.userMinistry ? JSON.parse(el.dataset.userMinistry) : null;
            const deptOptions = el.dataset.departmentOptions ? JSON.parse(el.dataset.departmentOptions) : null;
            const minOptions = el.dataset.ministryOptions ? JSON.parse(el.dataset.ministryOptions) : null;
            if (userMinistry && (!window.userMinistryData || (!window.userMinistryData.departments && !window.userMinistryData.ministries))) {
                window.userMinistryData = userMinistry;
            }
            if (Array.isArray(Object.keys(deptOptions || {}))) {
                window.departmentOptions = deptOptions;
            }
            if (Array.isArray(Object.keys(minOptions || {}))) {
                window.ministryOptions = minOptions;
            }
        } catch (e) {
            console.warn('Failed to parse ministry dataset:', e);
        }
    }
});

/**
 * Set up ministry selection functionality
 */
function setupMinistryHandlers() {
    console.log('Setting up ministry handlers...');
    
    // Set up save button
    const saveButton = document.querySelector('.btn-save-section[data-section="ministry"]');
    if (saveButton) {
        saveButton.addEventListener('click', saveMinistryDetails);
        console.log('Save button listener attached');
    } else {
        console.error('Save button not found');
    }
    
    // Set up delete button
    const deleteButton = document.querySelector('.btn-delete-section[data-section="ministry"]');
    if (deleteButton) {
        deleteButton.addEventListener('click', deleteMinistryFromDatabase);
        console.log('Delete button listener attached');
    } else {
        console.error('Delete button not found');
    }
    
    // Set up ministry limits if needed
    setupMinistryLimits();
    
    // Populate existing selections
    populateExistingMinistrySelections();
}

/**
 * Set up ministry selection limit functionality
 */
function setupMinistryLimits() {
    const departmentCheckboxes = document.querySelectorAll('.department-checkbox');
    const ministryCheckboxes = document.querySelectorAll('.ministry-checkbox');
    const departmentError = document.getElementById('department-error');
    const ministryError = document.getElementById('ministry-error');
    
    // Setup department checkboxes
    departmentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // No limits for now, but you can add validation here if needed
            if (departmentError) departmentError.textContent = '';
            // Update preview in real-time
            updateCurrentAssignmentPreview();
        });
    });
    
    // Setup ministry checkboxes
    ministryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // No limits for now, but you can add validation here if needed
            if (ministryError) ministryError.textContent = '';
            // Update preview in real-time
            updateCurrentAssignmentPreview();
        });
    });
}

/**
 * Update current assignment preview based on selected checkboxes
 */
function updateCurrentAssignmentPreview() {
    const selectedDepartments = [];
    const selectedMinistries = [];
    
    // Get selected departments
    document.querySelectorAll('.department-checkbox:checked').forEach(checkbox => {
        // Get the label text instead of the value
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        if (label) {
            selectedDepartments.push(label.textContent.trim());
        }
    });
    
    // Get selected ministries
    document.querySelectorAll('.ministry-checkbox:checked').forEach(checkbox => {
        // Get the label text instead of the value
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        if (label) {
            selectedMinistries.push(label.textContent.trim());
        }
    });
    
    // Update the current assignments display
    updateCurrentAssignmentsDisplay(selectedDepartments, selectedMinistries);
}

/**
 * Update the current assignments display
 */
function updateCurrentAssignmentsDisplay(departments, ministries) {
    const currentDepartments = document.getElementById('current-departments');
    const currentMinistries = document.getElementById('current-ministries');
    
    if (currentDepartments) {
        if (departments.length > 0) {
            currentDepartments.innerHTML = departments.map(dept => 
                `<span class="assignment-badge dept-badge">${escapeHtml(dept)}</span>`
            ).join('');
        } else {
            currentDepartments.innerHTML = '<span class="no-assignment">No departments assigned</span>';
        }
    }
    
    if (currentMinistries) {
        if (ministries.length > 0) {
            currentMinistries.innerHTML = ministries.map(ministry => 
                `<span class="assignment-badge ministry-badge">${escapeHtml(ministry)}</span>`
            ).join('');
        } else {
            currentMinistries.innerHTML = '<span class="no-assignment">No ministries assigned</span>';
        }
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Populate existing ministry selections from user data
 */
function populateExistingMinistrySelections() {
    console.log('Populating existing ministry selections:', window.userMinistryData);
    
    // Populate departments
    if (window.userMinistryData.departments) {
        window.userMinistryData.departments.forEach(dept => {
            const checkbox = document.querySelector(`.department-checkbox[value="${dept}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    // Populate ministries
    if (window.userMinistryData.ministries) {
        window.userMinistryData.ministries.forEach(ministry => {
            const checkbox = document.querySelector(`.ministry-checkbox[value="${ministry}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    // Initial update of the current assignments display
    updateCurrentAssignmentPreview();
}

/**
 * Save ministry details
 */
function saveMinistryDetails() {
    console.log('Save button clicked');
    const saveButton = document.querySelector('.btn-save-section[data-section="ministry"]');
    const originalText = saveButton ? saveButton.textContent : 'Save Ministry';
    
    if (saveButton) {
        saveButton.textContent = 'Saving...';
        saveButton.disabled = true;
    }
    
    // Collect selected departments
    const selectedDepartments = [];
    document.querySelectorAll('.department-checkbox:checked').forEach(checkbox => {
        selectedDepartments.push(checkbox.value);
    });
    
    // Collect selected ministries
    const selectedMinistries = [];
    document.querySelectorAll('.ministry-checkbox:checked').forEach(checkbox => {
        selectedMinistries.push(checkbox.value);
    });
    
    // Prepare form data
    const formData = new FormData();
    selectedDepartments.forEach(dept => {
        formData.append('departments[]', dept);
    });
    selectedMinistries.forEach(ministry => {
        formData.append('ministries[]', ministry);
    });
    
    // Add section identifier
    formData.append('section_type', 'ministry');
    formData.append('section', 'ministry');
    
    // Get user ID
    let userId = getUserId();
    if (!userId) {
        console.error('User ID not found - cannot save data');
        showMinistryStatus('error', 'User authentication error. Please refresh the page and try again.');
        if (saveButton) {
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }
        return;
    }
    formData.append('id', userId);
    
    // Debug: Log what we're sending
    console.log('Saving ministry section');
    console.log('User ID:', userId);
    console.log('Selected departments:', selectedDepartments);
    console.log('Selected ministries:', selectedMinistries);
    
    // Use the correct path
    const correctPath = '../sections/uministry_section.php';
    
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
                // Update local data
                window.userMinistryData.departments = selectedDepartments;
                window.userMinistryData.ministries = selectedMinistries;
                
                // Update the display with the actual saved data
                refreshMinistryDisplay();
                
                showMinistryStatus('success', data.message || 'Ministry details saved successfully!');
            } else {
                showMinistryStatus('error', data.message || 'Error saving ministry details');
            }
        } catch (parseError) {
            console.error('Error parsing response:', parseError);
            console.error('Raw response:', text);
            showMinistryStatus('error', 'Server returned invalid response');
        }
    })
    .catch(error => {
        console.error('Error saving ministry details:', error);
        showMinistryStatus('error', 'Network error: ' + error.message);
    })
    .finally(() => {
        if (saveButton) {
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }
    });
}

/**
 * Delete all ministry assignments from database and clear form
 */
function deleteMinistryFromDatabase() {
    console.log('Delete button clicked');
    
    // Show confirmation dialog
    if (!confirm('Are you sure you want to DELETE ALL your ministry and department assignments? This will permanently remove all your data from the database. This action cannot be undone!')) {
        return;
    }
    
    const deleteButton = document.querySelector('.btn-delete-section[data-section="ministry"]');
    const originalText = deleteButton ? deleteButton.textContent : 'Delete All';
    
    if (deleteButton) {
        deleteButton.textContent = 'Deleting...';
        deleteButton.disabled = true;
    }
    
    // Get user ID
    let userId = getUserId();
    if (!userId) {
        console.error('User ID not found - cannot delete data');
        showMinistryStatus('error', 'User authentication error. Please refresh the page and try again.');
        if (deleteButton) {
            deleteButton.textContent = originalText;
            deleteButton.disabled = false;
        }
        return;
    }
    
    // Prepare form data for deletion
    const formData = new FormData();
    formData.append('action', 'delete_all');
    formData.append('section_type', 'ministry');
    formData.append('id', userId);
    
    console.log('Deleting all ministry assignments for user:', userId);
    console.log('FormData contents:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ', pair[1]);
    }
    
    // Send delete request
    const correctPath = '../sections/uministry_section.php';
    
    fetch(correctPath, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log(`Delete response from ${correctPath}:`, response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log(`Raw delete response from ${correctPath}:`, text);
        
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                // Clear local data
                window.userMinistryData.departments = [];
                window.userMinistryData.ministries = [];
                
                // Clear all form selections
                document.querySelectorAll('.department-checkbox, .ministry-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Update the display
                updateCurrentAssignmentsDisplay([], []);
                
                // Clear any error messages
                const departmentError = document.getElementById('department-error');
                const ministryError = document.getElementById('ministry-error');
                if (departmentError) departmentError.textContent = '';
                if (ministryError) ministryError.textContent = '';
                
                showMinistryStatus('success', data.message || 'All ministry assignments deleted successfully!');
            } else {
                showMinistryStatus('error', data.message || 'Error deleting ministry assignments');
            }
        } catch (parseError) {
            console.error('Error parsing delete response:', parseError);
            console.error('Raw response:', text);
            showMinistryStatus('error', 'Server returned invalid response');
        }
    })
    .catch(error => {
        console.error('Error deleting ministry assignments:', error);
        showMinistryStatus('error', 'Network error: ' + error.message);
    })
    .finally(() => {
        if (deleteButton) {
            deleteButton.textContent = originalText;
            deleteButton.disabled = false;
        }
    });
}

/**
 * Refresh ministry display after successful save
 */
function refreshMinistryDisplay() {
    // Get the actual names from the checkboxes for display
    const selectedDepartmentNames = [];
    const selectedMinistryNames = [];
    
    document.querySelectorAll('.department-checkbox:checked').forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        if (label) {
            selectedDepartmentNames.push(label.textContent.trim());
        }
    });
    
    document.querySelectorAll('.ministry-checkbox:checked').forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        if (label) {
            selectedMinistryNames.push(label.textContent.trim());
        }
    });
    
    // Update the current assignments display
    updateCurrentAssignmentsDisplay(selectedDepartmentNames, selectedMinistryNames);
    
    console.log('Ministry display refreshed with:', {
        departments: selectedDepartmentNames,
        ministries: selectedMinistryNames
    });
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

/**
 * Show status message for ministry section
 */
function showMinistryStatus(type, message) {
    // Create or update status element
    let statusElement = document.getElementById('ministry-status');
    
    if (!statusElement) {
        statusElement = document.createElement('div');
        statusElement.id = 'ministry-status';
        const ministrySection = document.querySelector('.ministry-section');
        if (ministrySection) {
            ministrySection.appendChild(statusElement);
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

// Make functions globally available
window.setupMinistryHandlers = setupMinistryHandlers;

// Initialize handlers when DOM is ready (as a fallback)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof setupMinistryHandlers === 'function') {
        setupMinistryHandlers();
    }
});

console.log('Enhanced ministry handler loaded successfully');