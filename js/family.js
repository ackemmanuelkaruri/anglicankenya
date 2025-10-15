// Family section JS - REWRITTEN TO MATCH PERSONAL SECTION
(function() {
  'use strict';
  let originalValues = {};
  
  function init() {
    const form = document.getElementById('family');
    const statusDiv = document.getElementById('family-save-status');
    if (!form || !statusDiv) return;
    
    // Store original form values
    form.querySelectorAll('input, select, textarea').forEach(field => {
      if (field.type !== 'file') {
        originalValues[field.name] = field.value;
      }
    });
    
    // Family-specific functionality
    setupFamilyManagement();
    
    // Save button functionality
    const saveBtn = form.querySelector('.btn-save-section');
    if (saveBtn) {
      saveBtn.addEventListener('click', function() {
        statusDiv.innerHTML = '';
        
        // Check if we have an action set
        const actionField = document.getElementById('family-action');
        if (!actionField || !actionField.value) {
          statusDiv.innerHTML = '<div style="color: red;">No action specified. Please add or remove a family member first.</div>';
          return;
        }
        
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        
        submitFormData(form, statusDiv, saveBtn, originalText);
      });
    }
  }
  
  function setupFamilyManagement() {
    const familySection = document.getElementById('family');
    if (!familySection) return;
    
    // Elements
    const addFamilyBtn = familySection.querySelector('.btn-add-new-family');
    const memberTypeSelection = familySection.querySelector('#member-type-selection');
    const existingUserForm = familySection.querySelector('#existing-user-form');
    const minorForm = familySection.querySelector('#minor-form');
    const actionField = document.getElementById('family-action');
    
    // Show member type selection
    function showMemberTypeSelection() {
      hideAllForms();
      memberTypeSelection.style.display = 'block';
      addFamilyBtn.style.display = 'none';
    }
    
    // Hide all forms
    function hideAllForms() {
      memberTypeSelection.style.display = 'none';
      existingUserForm.style.display = 'none';
      minorForm.style.display = 'none';
    }
    
    // Show add family button
    function showAddButton() {
      hideAllForms();
      addFamilyBtn.style.display = 'block';
      clearForms();
      actionField.value = '';
    }
    
    // Clear all forms
    function clearForms() {
      const forms = familySection.querySelectorAll('form');
      forms.forEach(form => form.reset());
    }
    
    // Event Listeners
    addFamilyBtn.addEventListener('click', showMemberTypeSelection);
    
    // Close form buttons
    familySection.addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-cancel-family')) {
        e.preventDefault();
        showAddButton();
      }
    });
    
    // Member type selection
    familySection.addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-member-type') || e.target.closest('.btn-member-type')) {
        e.preventDefault();
        const btn = e.target.classList.contains('btn-member-type') ? e.target : e.target.closest('.btn-member-type');
        const type = btn.getAttribute('data-type');
        
        hideAllForms();
        if (type === 'existing') {
          existingUserForm.style.display = 'block';
        } else if (type === 'minor') {
          minorForm.style.display = 'block';
        }
      }
    });
    
    // Family save buttons (for individual forms)
    familySection.addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-save-family')) {
        e.preventDefault();
        const action = e.target.getAttribute('data-action');
        actionField.value = action;
        
        // Validate the appropriate form
        let isValid = true;
        let formToValidate = null;
        
        if (action === 'add_existing_user_family') {
          formToValidate = existingUserForm;
        } else if (action === 'add_minor_family') {
          formToValidate = minorForm;
          
          // Validate age for minors
          const dobInput = formToValidate.querySelector('#minor-dob');
          if (dobInput.value) {
            const birthDate = new Date(dobInput.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
              age--;
            }
            
            if (age >= 18) {
              isValid = false;
              const statusDiv = document.getElementById('family-save-status');
              statusDiv.innerHTML = '<div style="color: red;">Minor family members must be under 18 years old. Please use "Existing User" for adults.</div>';
            }
          }
        }
        
        if (isValid && formToValidate) {
          const requiredFields = formToValidate.querySelectorAll('[required]');
          requiredFields.forEach(field => {
            if (!field.value.trim()) {
              field.style.borderColor = 'red';
              isValid = false;
            } else {
              field.style.borderColor = '';
            }
          });
          
          if (!isValid) {
            const statusDiv = document.getElementById('family-save-status');
            statusDiv.innerHTML = '<div style="color: red;">Please fill in all required fields.</div>';
          }
        }
        
        if (isValid) {
          // Trigger the main save button
          const mainSaveBtn = familySection.querySelector('.btn-save-section');
          if (mainSaveBtn) {
            mainSaveBtn.click();
          }
        }
      }
    });
    
    // Delete family member
    familySection.addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-delete-member')) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to remove this family member?')) {
          return;
        }
        
        const memberId = e.target.getAttribute('data-id');
        
        // Create a hidden input for the member ID
        let memberIdInput = document.getElementById('delete-member-id');
        if (!memberIdInput) {
          memberIdInput = document.createElement('input');
          memberIdInput.type = 'hidden';
          memberIdInput.id = 'delete-member-id';
          memberIdInput.name = 'member_id';
          familySection.appendChild(memberIdInput);
        }
        memberIdInput.value = memberId;
        
        // Set the action
        actionField.value = 'delete_family_member';
        
        // Trigger the main save button
        const mainSaveBtn = familySection.querySelector('.btn-save-section');
        if (mainSaveBtn) {
          mainSaveBtn.click();
        }
      }
    });
  }
  
  function submitFormData(form, statusDiv, saveBtn, originalText) {
    const formData = new FormData();
    form.querySelectorAll('input, select, textarea').forEach(field => {
      if (field.type === 'file') {
        if (field.files.length > 0) formData.append(field.name, field.files[0]);
      } else {
        formData.append(field.name, field.value);
      }
    });
    
    fetch('../user/usection_update.php', {
      method: 'POST',
      body: formData
    })
      .then(r => r.text())
      .then(text => {
        try {
          const data = JSON.parse(text);
          if (data.success) {
            statusDiv.innerHTML = '<div style="color: green;">✅ ' + (data.message || 'Saved.') + '</div>';
            
            // For family operations, we might need to reload the page
            if (data.data && data.data.action && 
                (data.data.action === 'add_existing_user_family' || 
                 data.data.action === 'add_minor_family' || 
                 data.data.action === 'delete_family_member')) {
              // Reload after a short delay to show the success message
              setTimeout(() => {
                location.reload();
              }, 1500);
            }
          } else {
            statusDiv.innerHTML = '<div style="color: red;">❌ ' + (data.message || 'Save failed') + '</div>';
          }
        } catch (e) {
          console.error('JSON parse error:', e, text);
          statusDiv.innerHTML = '<div style="color: red;">❌ Server response error. Check console for details.</div>';
        }
      })
      .catch(err => {
        statusDiv.innerHTML = '<div style="color: red;">❌ Network error: ' + err.message + '</div>';
      })
      .finally(() => {
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
      });
  }
  
  document.addEventListener('DOMContentLoaded', init);
})();