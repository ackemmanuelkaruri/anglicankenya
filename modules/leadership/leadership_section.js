/**
 * leadership_section.js
 * Handles dynamic UI elements for the leadership section (Add/Remove fields, 'Other' role toggling).
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('leadership-roles-container');
    const addBtn = document.getElementById('add-leadership-btn');

    /**
     * Creates the HTML structure for a new leadership role.
     */
    function createNewRoleHtml(roleNumber) {
        const uniqueId = Date.now() + '_' + roleNumber;
        
        return `
            <div class="leadership-role new-role" data-role-id="${roleNumber}" data-leadership-id="">
                <h4>Leadership Assignment <span class="role-number">${roleNumber}</span></h4>
                
                <input type="hidden" name="leadership_id[]" value="">
                
                <label>Assigned Role</label>
                <select name="role_id[]" class="form-control role-id" required>
                    <option value="">Select Role</option>
                    <option value="1">Deacon</option>
                    <option value="2">Elder</option>
                    <option value="3">Department Head</option>
                    <option value="4">Ministry Lead</option>
                    <option value="99">Other</option>
                </select>
                
                <div class="form-group mt-2 other-role-group" style="display: none;">
                    <input type="text" name="other_role[]" class="form-control other-role" placeholder="Specify Role Name">
                </div>

                <label>Department/Group</label>
                <select name="department_id[]" class="form-control">
                    <option value="">N/A</option>
                    <option value="1">Finance</option>
                    <option value="2">Technical</option>
                    <option value="3">Logistics</option>
                </select>
                
                <div class="date-group row mt-3">
                    <div class="col-md-6">
                        <label>From Date</label>
                        <input type="date" name="leadership_from_date[]" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>To Date (or Expiry)</label>
                        <input type="date" name="leadership_to_date[]" class="form-control">
                    </div>
                </div>
                
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_active_checkbox_${uniqueId}" class="is-active-leadership-checkbox form-check-input" id="is_active_${uniqueId}" data-index="${roleNumber}" checked>
                    <label class="form-check-label" for="is_active_${uniqueId}">
                        Currently Active
                    </label>
                    <input type="hidden" name="is_active_leadership[]" value="1" class="is-active-leadership-hidden">
                </div>

                <button type="button" class="btn btn-danger btn-sm mt-3 btn-remove-leadership-role">
                    Remove Role
                </button>
                <hr>
            </div>
        `;
    }

    /**
     * Attaches event listeners to the new element.
     */
    function attachRoleListeners(element) {
        // Listener for Remove Button
        const removeBtn = element.querySelector('.btn-remove-leadership-role');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                element.remove();
                renumberRoles();
            });
        }
        
        // Listener for 'Is Active' checkbox
        const activeCheckbox = element.querySelector('.is-active-leadership-checkbox');
        const activeHidden = element.querySelector('.is-active-leadership-hidden');
        if (activeCheckbox && activeHidden) {
            activeCheckbox.addEventListener('change', function(e) {
                activeHidden.value = e.target.checked ? '1' : '0';
            });
        }

        // Listener for 'Role ID' selection (to show/hide 'Other' text field)
        const roleSelect = element.querySelector('.role-id');
        const otherGroup = element.querySelector('.other-role-group');
        const otherInput = element.querySelector('.other-role');
        if (roleSelect && otherGroup && otherInput) {
            roleSelect.addEventListener('change', function() {
                if (this.value === '99') {
                    otherGroup.style.display = 'block';
                    otherInput.required = true;
                } else {
                    otherGroup.style.display = 'none';
                    otherInput.required = false;
                }
            });
        }
    }
    
    function renumberRoles() {
        container.querySelectorAll('.leadership-role').forEach((role, index) => {
            const roleNumber = index + 1;
            role.querySelector('.role-number').textContent = roleNumber;
            role.dataset.roleId = roleNumber;
        });
    }

    // Event Listener for Add Button
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            const roleNumber = container.querySelectorAll('.leadership-role').length + 1;
            const newRoleHtml = createNewRoleHtml(roleNumber);
            container.insertAdjacentHTML('beforeend', newRoleHtml);
            
            const newRoleElement = container.lastElementChild;
            attachRoleListeners(newRoleElement);
        });
    }

    // Attach listeners to all existing roles on load
    container.querySelectorAll('.leadership-role').forEach(attachRoleListeners);
});