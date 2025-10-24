// Personal section JS extracted from inline script (CSP-compliant)
(function() {
  'use strict';

  let originalValues = {};

  function init() {
    const form = document.getElementById('personal');
    // FIX 1: Use the correct ID from personal_section.php
    const statusDiv = document.getElementById('personal-save-status');
    
    // The button uses class 'section-save-btn' in personal_section.php
    const saveBtn = form.querySelector('.section-save-btn');

    if (!form || !statusDiv || !saveBtn) return;

    // Store original form values
    form.querySelectorAll('input, select').forEach(field => {
      if (field.type !== 'file') {
        originalValues[field.name] = field.value;
      }
    });

    // Change detection (Simplified for brevity, assumes original logic is fine)
    form.addEventListener('change', function() {
      // Logic for change detection and button text/style update goes here...
      let hasChanges = false;
      form.querySelectorAll('input, select').forEach(field => {
        if (field.type !== 'file' && field.value !== originalValues[field.name]) {
          hasChanges = true;
        }
      });
      const fileInput = form.querySelector('input[type="file"]');
      if (fileInput && fileInput.files.length > 0) hasChanges = true;
      saveBtn.textContent = hasChanges ? 'Save Changes' : 'Save Personal Information';
      saveBtn.style.backgroundColor = hasChanges ? '#007bff' : '';
    });

    // Save button event listener
    saveBtn.addEventListener('click', function() {
        statusDiv.innerHTML = '';
        
        // Basic Validation (Check only if required fields are filled)
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        let missing = [];
        requiredFields.forEach(field => {
          if (!field.value.trim()) { 
            field.style.borderColor = 'red';
            isValid = false;
            missing.push(field.name);
          } else {
            field.style.borderColor = '';
          }
        });
        
        if (!isValid) {
          statusDiv.innerHTML = '<div style="color: red;">❌ Please fill in all required fields: ' + missing.join(', ') + '</div>';
          return;
        }

        // Disable button and show saving message
        const originalText = saveBtn.textContent;
        saveBtn.setAttribute('data-original-text', originalText);
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        
        submitFormData(form, statusDiv, saveBtn);
    });
  }

  function submitFormData(form, statusDiv, saveBtn) {
    const formData = new FormData();
    
    // Add form fields to FormData, correctly handling files
    form.querySelectorAll('input, select').forEach(field => {
      if (field.type === 'file') {
        if (field.files.length > 0) formData.append(field.name, field.files[0]);
      } else {
        formData.append(field.name, field.value);
      }
    });
    
    // CRITICAL FIX 2: Add section identifiers required by your PHP handler
    formData.append('section_type', 'personal');
    formData.append('section', 'personal'); 

    // CRITICAL FIX 3: Update to the correct endpoint
    const endpoint = './personal_update.php';

    fetch(endpoint, {
      method: 'POST',
      body: formData
    })
      .then(r => {
        if (!r.ok) {
            // Throw error on 404, 500, etc.
            throw new Error(`HTTP Error: ${r.status} ${r.statusText}`);
        }
        return r.text();
      })
      .then(text => {
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error('JSON parse error:', e, text);
          statusDiv.innerHTML = '<div style="color: red;">❌ Server response error. Check console for details.</div>';
          throw new Error('Server returned invalid JSON.');
        }
        
        // Handle success/failure based on server's JSON
        if (data.success) {
          statusDiv.innerHTML = '<div style="color: green;">✅ ' + (data.message || 'Saved successfully!') + '</div>';
          
          // Reset original values and clear file input
          form.querySelectorAll('input, select').forEach(field => {
            if (field.type !== 'file') originalValues[field.name] = field.value;
          });
          const fileInput = form.querySelector('input[type="file"]');
          if (fileInput) fileInput.value = '';
          
        } else {
          statusDiv.innerHTML = '<div style="color: red;">❌ ' + (data.message || 'Save failed') + '</div>';
        }
      })
      .catch(err => {
        statusDiv.innerHTML = '<div style="color: red;">❌ Network or Server Error: ' + err.message + '</div>';
      })
      .finally(() => {
        // Reset button state
        saveBtn.textContent = saveBtn.getAttribute('data-original-text');
        saveBtn.disabled = false;
        saveBtn.removeAttribute('data-original-text');
      });
  }

  document.addEventListener('DOMContentLoaded', init);
})();