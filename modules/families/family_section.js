/**
 * family_section.js
 * Handles CRUD and relationship requests for family and dependents.
 */

const FAMILY_ENDPOINT = '../sections/family_update.php';

document.addEventListener('DOMContentLoaded', function() {
    
    const requestLinkBtn = document.getElementById('request_link_btn');
    const targetUsernameInput = document.getElementById('target_username');
    const relationshipSelect = document.getElementById('relationship_type');
    const requestStatusDiv = document.getElementById('request-link-status');
    const dependentForm = document.getElementById('add-dependent-form');
    const dependentStatusDiv = document.getElementById('add-dependent-status');
    const familyStatusGlobalDiv = document.getElementById('family-status-global');
    const linkedUsersList = document.getElementById('linked-users-list');
    const dependentsList = document.getElementById('dependents-list');

    // --- Utility Functions ---

    function showStatus(div, message, isSuccess) {
        div.innerHTML = `<div class="alert alert-${isSuccess ? 'success' : 'danger'}" role="alert">${message}</div>`;
        setTimeout(() => div.innerHTML = '', 5000);
    }
    
    // --- Family Member (Linked User) Logic ---

    if (requestLinkBtn) {
        requestLinkBtn.addEventListener('click', async function() {
            const targetUsername = targetUsernameInput.value.trim();
            const relationship = relationshipSelect.value;

            if (!targetUsername || !relationship) {
                showStatus(requestStatusDiv, 'Please enter a username/email and select a relationship.', false);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'request_link');
            formData.append('target_username', targetUsername);
            formData.append('relationship_type', relationship);

            try {
                const response = await fetch(FAMILY_ENDPOINT, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    showStatus(requestStatusDiv, result.message, true);
                    targetUsernameInput.value = '';
                    // The UI should refresh to show the PENDING request
                    // A proper refresh logic would call a GET endpoint to reload both lists
                    location.reload(); 
                } else {
                    showStatus(requestStatusDiv, result.message, false);
                }
            } catch (error) {
                showStatus(requestStatusDiv, 'An error occurred while sending the link request.', false);
                console.error('Link Request Error:', error);
            }
        });
    }
    
    // Delete Relationship Listener
    linkedUsersList.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-relationship-btn')) {
            const id = e.target.dataset.id;
            if (confirm('Are you sure you want to remove this family link?')) {
                deleteFamilyItem('relationship', id);
            }
        }
    });

    // --- Dependent Logic ---

    if (dependentForm) {
        dependentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(dependentForm);
            formData.append('action', 'add_dependent');

            try {
                const response = await fetch(FAMILY_ENDPOINT, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    showStatus(dependentStatusDiv, result.message, true);
                    dependentForm.reset();
                    // A proper refresh would call a GET endpoint to reload dependents list
                    location.reload(); 
                } else {
                    showStatus(dependentStatusDiv, result.message, false);
                }
            } catch (error) {
                showStatus(dependentStatusDiv, 'An error occurred while adding the dependent.', false);
                console.error('Dependent Add Error:', error);
            }
        });
    }
    
    // Delete Dependent Listener
    dependentsList.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-dependent-btn')) {
            const id = e.target.dataset.id;
            if (confirm('Are you sure you want to remove this dependent?')) {
                deleteFamilyItem('dependent', id);
            }
        }
    });
    
    // --- General Delete Handler ---
    async function deleteFamilyItem(type, id) {
        const formData = new FormData();
        formData.append('action', `delete_${type}`);
        formData.append('id', id);

        try {
            const response = await fetch(FAMILY_ENDPOINT, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                showStatus(familyStatusGlobalDiv, result.message, true);
                // Remove from DOM
                const element = document.querySelector(`[data-id="${id}"]`);
                if (element) element.remove();
            } else {
                showStatus(familyStatusGlobalDiv, result.message, false);
            }
        } catch (error) {
            showStatus(familyStatusGlobalDiv, `An error occurred during ${type} removal.`, false);
            console.error(`${type} Delete Error:`, error);
        }
    }
});