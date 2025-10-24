/**
 * employment_section.js
 * Handles dynamic UI elements for the employment section (Add/Remove fields).
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('employment-roles-container');
    const addBtn = document.getElementById('add-employment-btn');

    /**
     * Finds the next available role number for the title.
     * @returns {number}
     */
    function getNextRoleNumber() {
        const roles = container.querySelectorAll('.employment-role');
        return roles.length + 1;
    }

    /**
     * Creates the HTML structure for a new employment role.
     * @param {number} roleNumber 
     * @returns {string} The HTML string
     */
    function createNewRoleHtml(roleNumber) {
        // Use a unique ID based on the current timestamp + index to prevent ID conflicts
        const uniqueId = Date.now() + '_' + roleNumber;
        
        return `
            <div class="employment-role new-role" data-role-id="${roleNumber}" data-employment-id="">
                <h4>Employment Role <span class="role-number">${roleNumber}</span></h4>
                
                <input type="hidden" name="employment_id[]" value="">
                
                <label>Job Title</label>
                <input type="text" name="job_title[]" class="job-title form-control" required>
                
                <label>Company/Employer</label>
                <input type="text" name="company[]" class="company form-control" required>
                       
                <div class="date-group">
                    <label>From Date</label>
                    <input type="date" name="employment_from_date[]" class="employment-from-date form-control" required>
                </div>
                
                <div class="date-group">
                    <label>To Date</label>
                    <input type="date" name="employment_to_date[]" class="employment-to-date form-control">
                    <small class="form-text text-muted">Leave blank if currently employed.</small>
                </div>
                
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_current_employment_checkbox_${uniqueId}" class="is-current-employment-checkbox form-check-input" id="is_current_${uniqueId}" data-index="${roleNumber}">
                    <label class="form-check-label" for="is_current_${uniqueId}">
                        Currently Employed Here
                    </label>
                    <input type="hidden" name="is_current_employment[]" value="0" class="is-current-employment-hidden">
                </div>

                <button type="button" class="btn btn-danger btn-sm mt-3 btn-remove-employment-role">
                    Remove Role
                </button>
                <hr>
            </div>
        `;
    }

    /**
     * Attaches event listeners to the new element.
     * @param {HTMLElement} element 
     */
    function attachRoleListeners(element) {
        // Listener for Remove Button
        const removeBtn = element.querySelector('.btn-remove-employment-role');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                // For simplicity in this bulk-save system, just remove it from the DOM
                element.remove();
                renumberRoles();
            });
        }
        
        // Listener for 'Currently Employed' checkbox
        const currentCheckbox = element.querySelector('.is-current-employment-checkbox');
        const currentHidden = element.querySelector('.is-current-employment-hidden');
        if (currentCheckbox && currentHidden) {
            currentCheckbox.addEventListener('change', function(e) {
                // Update the hidden field's value (1 or 0) which is used by section-save.js
                currentHidden.value = e.target.checked ? '1' : '0';
            });
        }
    }
    
    /**
     * Re-calculates and sets the role numbers after one is removed.
     */
    function renumberRoles() {
        container.querySelectorAll('.employment-role').forEach((role, index) => {
            const roleNumber = index + 1;
            role.querySelector('.role-number').textContent = roleNumber;
            role.dataset.roleId = roleNumber;
            // Update the checkbox data-index and ID to remain unique/correct
            const checkbox = role.querySelector('.is-current-employment-checkbox');
            if (checkbox) {
                checkbox.id = `is_current_${roleNumber}`;
                checkbox.dataset.index = roleNumber;
                role.querySelector(`label[for^="is_current_"]`).setAttribute('for', `is_current_${roleNumber}`);
            }
        });
    }

    // Event Listener for Add Button
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const roleNumber = getNextRoleNumber();
            const newRoleHtml = createNewRoleHtml(roleNumber);
            container.insertAdjacentHTML('beforeend', newRoleHtml);
            
            // Get the newly inserted element and attach listeners
            const newRoleElement = container.lastElementChild;
            attachRoleListeners(newRoleElement);
        });
    }

    // Attach listeners to all existing roles on load (for checkboxes and removal)
    container.querySelectorAll('.employment-role').forEach(attachRoleListeners);
});