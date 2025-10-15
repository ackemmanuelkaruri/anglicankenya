// Leadership init to replace inline script and inline handlers
(function() {
  'use strict';

  function initLeadership() {
    // Initialize existing leadership roles UI state
    const leadershipRoles = document.querySelectorAll('.leadership-role');
    leadershipRoles.forEach(role => {
      const leadershipType = role.querySelector('.leadership-type');
      if (leadershipType && leadershipType.value && typeof window.updateLeadershipOptions === 'function') {
        window.updateLeadershipOptions(leadershipType);
      }
      const roleSelect = role.querySelector('.leadership-role-select');
      if (roleSelect && roleSelect.value && typeof window.toggleOtherRoleField === 'function') {
        window.toggleOtherRoleField(roleSelect);
      }
    });

    // Show remove buttons if more than one role
    if (leadershipRoles.length > 1) {
      document.querySelectorAll('.btn-remove-role').forEach(btn => {
        btn.style.display = 'block';
      });
    }

    // Save button
    const saveButton = document.querySelector('.btn-save-section[data-section="leadership"]');
    if (saveButton && typeof window.saveLeadershipRoles === 'function') {
      saveButton.addEventListener('click', window.saveLeadershipRoles);
    }

    // Edit toggle button
    const editBtn = document.querySelector('.btn-edit-leadership');
    if (editBtn) {
      editBtn.addEventListener('click', function() {
        const formContainer = document.getElementById('leadership-form-container');
        const summarySection = document.querySelector('.leadership-summary');
        if (formContainer) formContainer.classList.remove('hidden');
        if (summarySection) summarySection.classList.add('hidden');
      });
    }

    // Cancel edit button
    const cancelBtn = document.querySelector('.btn-cancel-edit');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function() {
        const formContainer = document.getElementById('leadership-form-container');
        const summarySection = document.querySelector('.leadership-summary');
        if (formContainer) formContainer.classList.add('hidden');
        if (summarySection) summarySection.classList.remove('hidden');
      });
    }

    // Delegate change events for dynamic rows
    const container = document.getElementById('leadership-roles-container');
    if (container) {
      container.addEventListener('change', function(e) {
        if (e.target.classList.contains('leadership-type') && typeof window.updateLeadershipOptions === 'function') {
          window.updateLeadershipOptions(e.target);
        }
        if (e.target.classList.contains('leadership-role-select') && typeof window.toggleOtherRoleField === 'function') {
          window.toggleOtherRoleField(e.target);
        }
        if (e.target.classList.contains('is-current-leadership') && typeof window.toggleCurrentLeadership === 'function') {
          window.toggleCurrentLeadership(e.target);
        }
      });

      // Delegate remove button clicks
      container.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.btn-remove-role');
        if (removeBtn && typeof window.removeLeadershipRole === 'function') {
          e.preventDefault();
          window.removeLeadershipRole(removeBtn);
        }
      });
    }

    // Add role button
    const addBtn = document.querySelector('.btn-add-role');
    if (addBtn && typeof window.addLeadershipRole === 'function') {
      addBtn.addEventListener('click', window.addLeadershipRole);
    }

    console.log('Leadership init completed');
  }

  document.addEventListener('DOMContentLoaded', initLeadership);
})();

