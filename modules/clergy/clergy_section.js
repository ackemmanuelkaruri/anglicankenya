/**
 * clergy_section.js
 * Handles the full CRUD UI/Logic for individual clergy roles.
 */

const CLERGY_ENDPOINT = '../sections/clergy_update.php';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clergy-role-form');
    const roleListContainer = document.getElementById('existing_clergy_roles');
    const saveBtn = document.getElementById('save_clergy_role_btn');
    const cancelBtn = document.getElementById('cancel_clergy_role_btn');
    const roleIdSelect = document.getElementById('role_id');
    const otherRoleGroup = document.getElementById('other_role_name_group');
    const statusDiv = document.getElementById('clergy-save-status-role');

    // --- UI Helpers ---

    function resetForm() {
        form.reset();
        document.getElementById('clergy_id').value = '';
        document.getElementById('role-form-title').textContent = 'Add New Role';
        otherRoleGroup.style.display = 'none';
        roleIdSelect.setCustomValidity('');
    }

    function showStatus(message, isSuccess) {
        statusDiv.innerHTML = `<div class="alert alert-${isSuccess ? 'success' : 'danger'}">${message}</div>`;
        setTimeout(() => statusDiv.innerHTML = '', 5000);
    }
    
    // --- Data Rendering ---

    /**
     * Renders the list of roles based on the data returned from the server.
     * @param {Array} roles 
     */
    function renderRoleList(roles) {
        roleListContainer.innerHTML = '';
        const noRolesMessage = document.getElementById('no-roles-message') || document.createElement('p');
        noRolesMessage.id = 'no-roles-message';
        noRolesMessage.textContent = 'No clergy roles recorded.';
        
        if (roles.length === 0) {
            roleListContainer.appendChild(noRolesMessage);
            return;
        }
        
        roles.forEach(role => {
            const roleItem = document.createElement('div');
            roleItem.className = 'role-item p-3 mb-2 border rounded';
            roleItem.dataset.id = role.id;
            
            const endDate = role.serving_to_date ? role.serving_to_date : 'Current';
            
            roleItem.innerHTML = `
                <strong>${role.display_role_name || role.role_name}</strong> 
                (${role.serving_from_date} - ${endDate})
                <div class="float-end">
                    <button type="button" class="btn btn-sm btn-info edit-clergy-role" data-id="${role.id}">Edit</button>
                    <button type="button" class="btn btn-sm btn-danger delete-clergy-role" data-id="${role.id}">Delete</button>
                </div>
            `;
            roleListContainer.appendChild(roleItem);
        });
    }

    // --- Event Handlers ---
    
    roleIdSelect.addEventListener('change', function() {
        otherRoleGroup.style.display = this.value === '99' ? 'block' : 'none';
        document.getElementById('role_name').required = this.value === '99';
    });

    cancelBtn.addEventListener('click', resetForm);

    saveBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        // Simple form validation check
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        // Special handling for unchecked checkbox: need to ensure is_current is '0' if unchecked
        if (!document.getElementById('is_current').checked) {
            formData.append('is_current', '0');
        }
        
        try {
            const response = await fetch(CLERGY_ENDPOINT, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showStatus(result.message, true);
                resetForm();
                // Re-render the list from the data returned by the handler
                if (result.roles) {
                    renderRoleList(result.roles);
                }
            } else {
                showStatus(result.message, false);
            }

        } catch (error) {
            showStatus('An error occurred during save.', false);
            console.error('Clergy Save Error:', error);
        }
    });

    roleListContainer.addEventListener('click', function(e) {
        const target = e.target;
        const id = target.dataset.id;
        if (!id) return;
        
        if (target.classList.contains('edit-clergy-role')) {
            // Find the data for this role (Requires a new GET endpoint to fetch single role data if not in DOM)
            // For simplicity, we assume the data needed for the form is present in the DOM or fetched separately.
            // A dedicated GET call to the endpoint is the proper architectural approach:
            // fetchRoleData(id).then(populateForm);
            
            // Temporary, simplified logic:
            const roleItem = target.closest('.role-item');
            showStatus('Editing not yet fully implemented. Populate form fields manually.', false);
            // In a real application, you would make an AJAX call to get the specific role data by ID
            // and then fill the form fields (including hidden clergy_id).
            document.getElementById('role-form-title').textContent = 'Edit Role (ID: ' + id + ')';
            document.getElementById('clergy_id').value = id;

        } else if (target.classList.contains('delete-clergy-role')) {
            if (confirm('Are you sure you want to delete this clergy role?')) {
                deleteRole(id);
            }
        }
    });
    
    async function deleteRole(id) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('clergy_id', id);
        
        try {
            const response = await fetch(CLERGY_ENDPOINT, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showStatus(result.message, true);
                // Remove the item from the DOM
                document.querySelector(`.role-item[data-id="${id}"]`).remove();
                
                // If the delete action also returns the new list, use renderRoleList(result.roles);
            } else {
                showStatus(result.message, false);
            }

        } catch (error) {
            showStatus('An error occurred during deletion.', false);
            console.error('Clergy Delete Error:', error);
        }
    }
});