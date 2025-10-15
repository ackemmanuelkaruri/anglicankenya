// Personal section JS extracted from inline script (CSP-compliant)
(function() {
  'use strict';

  let originalValues = {};

  function init() {
    const form = document.getElementById('personal');
    const statusDiv = document.getElementById('save-status');
    if (!form || !statusDiv) return;

    // Store original form values
    form.querySelectorAll('input, select').forEach(field => {
      if (field.type !== 'file') {
        originalValues[field.name] = field.value;
      }
    });

    // Change detection
    form.addEventListener('change', function() {
      const saveBtn = form.querySelector('.btn-save-section');
      if (!saveBtn) return;
      let hasChanges = false;
      form.querySelectorAll('input, select').forEach(field => {
        if (field.type !== 'file' && field.value !== originalValues[field.name]) {
          hasChanges = true;
        }
      });
      const fileInput = form.querySelector('input[type="file"]');
      if (fileInput && fileInput.files.length > 0) hasChanges = true;
      saveBtn.textContent = hasChanges ? 'Save Changes' : 'Save Personal Info';
      saveBtn.style.backgroundColor = hasChanges ? '#007bff' : '';
    });

    // Save button
    const saveBtn = form.querySelector('.btn-save-section');
    if (saveBtn) {
      saveBtn.addEventListener('click', function() {
        statusDiv.innerHTML = '';
        // Validate required
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
          statusDiv.innerHTML = '<div style="color: red;">Please fill in all required fields: ' + missing.join(', ') + '</div>';
          return;
        }
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        submitFormData(form, statusDiv, saveBtn, originalText);
      });
    }
  }

  function submitFormData(form, statusDiv, saveBtn, originalText) {
    const formData = new FormData();
    form.querySelectorAll('input, select').forEach(field => {
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
            form.querySelectorAll('input, select').forEach(field => {
              if (field.type !== 'file') originalValues[field.name] = field.value;
            });
            const fileInput = form.querySelector('input[type="file"]');
            if (fileInput) fileInput.value = '';
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

