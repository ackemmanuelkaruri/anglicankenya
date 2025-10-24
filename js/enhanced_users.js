/**
 * Enhanced Users Management JavaScript - CONSOLIDATED & FIXED
 * Handles all user management interactions
 */

(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('Enhanced Users JS: Initializing...');
        
        // Initialize variables
        let selectedUsers = [];
        const bulkActionsBar = $('#bulkActionsBar');
        const selectedCount = $('#selectedCount');
        const selectAllCheckbox = $('#selectAll, #selectAllHeader');
        const userCheckboxes = $('.user-checkbox');
        const applyBulkActionBtn = $('#applyBulkAction');
        const cancelBulkActionBtn = $('#cancelBulkAction');
        const bulkActionSelect = $('#bulkAction');
        const exportBtn = $('#exportBtn');
        const loadingOverlay = $('#loadingOverlay');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Initialize all components
        initializeTooltips();
        initializeFilterToggle();
        initializeHierarchyDropdown();
        initializeDeleteConfirmations();
        initializeDropdowns(); // NEW: Fix dropdown visibility
        initializeRoleChangeHandlers();
        initializeStatusChangeHandlers();
        initializeCheckboxHandlers();
        initializeBulkActionHandlers();
        initializeExportHandler();
        initializeImpersonationWarnings();
        
        // ============================================
        // DROPDOWN VISIBILITY FIX
        // ============================================
        function initializeDropdowns() {
            console.log('Initializing dropdowns...');
            
            // Ensure Bootstrap dropdowns are initialized
            $('.dropdown-toggle').each(function() {
                if (!$(this).data('bs.dropdown')) {
                    new bootstrap.Dropdown(this);
                }
            });
            
            // Fix dropdown positioning for bottom rows
            $('.table tbody').on('show.bs.dropdown', '.dropdown', function() {
                const $dropdown = $(this);
                const $menu = $dropdown.find('.dropdown-menu');
                const $row = $dropdown.closest('tr');
                
                // Check if dropdown is in bottom 3 rows
                const rowIndex = $row.index();
                const totalRows = $('.table tbody tr').length;
                
                if (totalRows - rowIndex <= 3) {
                    // Show dropdown upward
                    $menu.css({
                        'bottom': '100%',
                        'top': 'auto',
                        'margin-bottom': '0.125rem'
                    });
                } else {
                    // Show dropdown downward (default)
                    $menu.css({
                        'bottom': 'auto',
                        'top': '100%',
                        'margin-top': '0.125rem'
                    });
                }
            });
            
            // Prevent dropdown from closing table overflow
            $('.dropdown-menu').on('click', function(e) {
                e.stopPropagation();
            });
            
            console.log('Dropdowns initialized successfully');
        }
        
        // ============================================
        // ROLE CHANGE HANDLERS
        // ============================================
        function initializeRoleChangeHandlers() {
            console.log('Initializing role change handlers...');
            
            $(document).on('click', '.role-change-link', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent dropdown from closing
                
                const userId = $(this).data('user-id');
                const newRole = $(this).data('role');
                const userName = $(this).data('user-name');
                const roleDisplayName = $(this).text().trim().replace(/\s+/g, ' ');
                
                console.log('Role change clicked:', { userId, newRole, userName, roleDisplayName });
                
                if (!userId || !newRole) {
                    showAlert('Invalid user or role data', 'danger');
                    return;
                }
                
                // Confirm action
                let confirmMsg = `Are you sure you want to change ${userName}'s role to ${roleDisplayName}?`;
                
                if (['super_admin', 'national_admin'].includes(newRole)) {
                    confirmMsg += '\n\n⚠️ This grants elevated system privileges!';
                }
                
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                updateUserRole(userId, newRole, roleDisplayName);
            });
        }
        
        // ============================================
        // STATUS CHANGE HANDLERS
        // ============================================
        function initializeStatusChangeHandlers() {
            console.log('Initializing status change handlers...');
            
            $(document).on('click', '.status-change-link', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent dropdown from closing
                
                const userId = $(this).data('user-id');
                const newStatus = $(this).data('status');
                const userName = $(this).data('user-name');
                const statusDisplayName = $(this).text().trim().replace(/\s+/g, ' ');
                
                console.log('Status change clicked:', { userId, newStatus, userName, statusDisplayName });
                
                if (!userId || !newStatus) {
                    showAlert('Invalid user or status data', 'danger');
                    return;
                }
                
                // Confirm action
                let confirmMsg = `Are you sure you want to change ${userName}'s status to ${statusDisplayName}?`;
                
                if (newStatus === 'suspended') {
                    confirmMsg += '\n\n⚠️ This will immediately prevent them from logging in!';
                } else if (newStatus === 'active') {
                    confirmMsg += '\n\n✓ This will grant them full access to their account.';
                }
                
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                updateUserStatus(userId, newStatus, statusDisplayName);
            });
        }
        
        // ============================================
        // CHECKBOX HANDLERS
        // ============================================
        function initializeCheckboxHandlers() {
            userCheckboxes.on('change', function() {
                updateSelectedUsers();
            });
            
            selectAllCheckbox.on('change', function() {
                const isChecked = $(this).prop('checked');
                userCheckboxes.prop('checked', isChecked);
                selectAllCheckbox.prop('checked', isChecked);
                updateSelectedUsers();
            });
        }
        
        // ============================================
        // BULK ACTION HANDLERS
        // ============================================
        function initializeBulkActionHandlers() {
            applyBulkActionBtn.on('click', function() {
                const action = bulkActionSelect.val();
                
                if (!action) {
                    showAlert('Please select an action', 'warning');
                    return;
                }
                
                if (selectedUsers.length === 0) {
                    showAlert('Please select at least one user', 'warning');
                    return;
                }
                
                // Confirmation
                let confirmMessage = '';
                switch(action) {
                    case 'delete':
                        confirmMessage = `⚠️ DELETE ${selectedUsers.length} USER(S)?\n\nThis action CANNOT be undone!`;
                        break;
                    case 'suspend':
                        confirmMessage = `Suspend ${selectedUsers.length} user(s)?`;
                        break;
                    case 'activate':
                        confirmMessage = `Activate ${selectedUsers.length} user(s)?`;
                        break;
                    case 'export':
                        performBulkExport();
                        return;
                    default:
                        confirmMessage = `Apply this action to ${selectedUsers.length} user(s)?`;
                }
                
                if (!confirm(confirmMessage)) {
                    return;
                }
                
                performBulkAction(action);
            });
            
            cancelBulkActionBtn.on('click', function() {
                clearSelection();
            });
        }
        
        // ============================================
        // EXPORT HANDLER
        // ============================================
        function initializeExportHandler() {
            exportBtn.on('click', function() {
                if (selectedUsers.length > 0) {
                    const choice = confirm('Export SELECTED users?\n\nOK = Selected users only\nCancel = All filtered results');
                    exportUsers(choice ? 'selected' : 'filtered');
                } else {
                    exportUsers('filtered');
                }
            });
        }
        
        // ============================================
        // IMPERSONATION WARNINGS
        // ============================================
        function initializeImpersonationWarnings() {
            $(document).on('click', 'a[href*="impersonate.php"]', function(e) {
                const userName = $(this).closest('tr').find('strong').first().text();
                
                const confirmed = confirm(
                    `⚠️ IMPERSONATION WARNING\n\n` +
                    `You are about to impersonate: ${userName}\n\n` +
                    `All actions performed will be:\n` +
                    `• Logged with your original account\n` +
                    `• Marked as impersonation\n` +
                    `• Visible in audit logs\n\n` +
                    `Do you want to proceed?`
                );
                
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        // ============================================
        // HELPER FUNCTIONS
        // ============================================
        
        function initializeTooltips() {
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        delay: { show: 500, hide: 100 }
                    });
                });
                console.log('Tooltips initialized');
            }
        }
        
        function initializeFilterToggle() {
            const toggleBtn = $('#toggleFilters');
            const filterContent = $('#filterContent');
            const filterIcon = $('#filterToggleIcon');
            
            if (toggleBtn.length && filterContent.length) {
                toggleBtn.on('click', function() {
                    if (filterContent.is(':visible')) {
                        filterContent.slideUp(300);
                        filterIcon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    } else {
                        filterContent.slideDown(300);
                        filterIcon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    }
                });
            }
        }
        
        function initializeHierarchyDropdown() {
            const hierarchyLevel = $('#hierarchyLevel');
            const hierarchyId = $('#hierarchyId');
            
            if (hierarchyLevel.length && hierarchyId.length) {
                hierarchyLevel.on('change', function() {
                    const level = $(this).val();
                    
                    hierarchyId.html('<option value="">Select...</option>');
                    hierarchyId.prop('disabled', !level);
                    
                    if (!level) return;
                    
                    showLoading('Loading options...');
                    
                    $.ajax({
                        url: 'get_hierarchy_options.php',
                        method: 'GET',
                        data: { level: level },
                        dataType: 'json',
                        timeout: 10000,
                        success: function(response) {
                            hideLoading();
                            
                            if (response.success && response.options && response.options.length > 0) {
                                response.options.forEach(function(option) {
                                    const idKey = level + '_id';
                                    const nameKey = level + '_name';
                                    
                                    if (option[idKey] && option[nameKey]) {
                                        hierarchyId.append($('<option></option>')
                                            .val(option[idKey])
                                            .text(option[nameKey])
                                        );
                                    }
                                });
                                hierarchyId.prop('disabled', false);
                            } else {
                                showAlert('No options available for this level', 'info');
                            }
                        },
                        error: function() {
                            hideLoading();
                            showAlert('Failed to load hierarchy options', 'danger');
                        }
                    });
                });
            }
        }
        
        function initializeDeleteConfirmations() {
            $(document).on('click', '.confirm-delete', function(e) {
                const userName = $(this).data('user-name') || 'this user';
                if (!confirm(`⚠️ DELETE USER?\n\nAre you sure you want to delete ${userName}?\n\nThis action CANNOT be undone!`)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        function updateSelectedUsers() {
            selectedUsers = [];
            userCheckboxes.each(function() {
                if ($(this).prop('checked')) {
                    selectedUsers.push($(this).val());
                }
            });
            
            const count = selectedUsers.length;
            selectedCount.text(count);
            
            if (count > 0) {
                bulkActionsBar.addClass('show');
            } else {
                bulkActionsBar.removeClass('show');
            }
            
            // Update select all state
            const totalCheckboxes = userCheckboxes.length;
            const checkedCheckboxes = userCheckboxes.filter(':checked').length;
            
            selectAllCheckbox.prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
            selectAllCheckbox.prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        }
        
        function clearSelection() {
            userCheckboxes.prop('checked', false);
            selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
            bulkActionSelect.val('');
            updateSelectedUsers();
        }
        
        function showLoading(message = 'Processing...') {
            if (loadingOverlay.length) {
                loadingOverlay.find('p').text(message);
                loadingOverlay.addClass('show');
            }
        }
        
        function hideLoading() {
            if (loadingOverlay.length) {
                loadingOverlay.removeClass('show');
            }
        }
        
        function showAlert(message, type = 'info') {
            const icons = {
                success: 'check-circle',
                danger: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };
            
            const icon = icons[type] || icons.info;
            
            const alert = $(`
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${icon} me-2"></i>
                    ${escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('.main-content > .alert').remove();
            $('.page-header').after(alert);
            
            setTimeout(function() {
                alert.fadeOut(300, function() { $(this).remove(); });
            }, 5000);
            
            $('html, body').animate({ scrollTop: alert.offset().top - 100 }, 300);
        }
        
        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        
        function updateUserRole(userId, newRole, roleDisplayName) {
            if (!csrfToken) {
                showAlert('Security token missing. Please refresh the page.', 'danger');
                return;
            }
            
            showLoading('Updating role...');
            
            $.ajax({
                url: 'assign_role_ajax.php',
                method: 'POST',
                data: {
                    user_id: userId,
                    role_level: newRole,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    hideLoading();
                    
                    if (response.status === 'success' || response.success) {
                        showAlert(`✓ Role updated to ${roleDisplayName}!`, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert(response.message || 'Failed to update role', 'danger');
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    let errorMsg = 'Failed to update role';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showAlert(errorMsg, 'danger');
                }
            });
        }
        
        function updateUserStatus(userId, newStatus, statusDisplayName) {
            if (!csrfToken) {
                showAlert('Security token missing. Please refresh the page.', 'danger');
                return;
            }
            
            showLoading('Updating status...');
            
            $.ajax({
                url: 'change_status_ajax.php',
                method: 'POST',
                data: {
                    user_id: userId,
                    new_status: newStatus,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    hideLoading();
                    
                    if (response.status === 'success' || response.success) {
                        showAlert(`✓ Status updated to ${statusDisplayName}!`, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert(response.message || 'Failed to update status', 'danger');
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    let errorMsg = 'Failed to update status';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showAlert(errorMsg, 'danger');
                }
            });
        }
        
        function performBulkAction(action) {
            if (!csrfToken || selectedUsers.length === 0) {
                showAlert('Invalid request', 'danger');
                return;
            }
            
            showLoading('Processing bulk action...');
            
            $.ajax({
                url: 'bulk_action_ajax.php',
                method: 'POST',
                data: {
                    action: action,
                    user_ids: selectedUsers,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    hideLoading();
                    
                    if (response.status === 'success' || response.success) {
                        showAlert(response.message || 'Bulk action completed!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert(response.message || 'Bulk action failed', 'danger');
                    }
                },
                error: function() {
                    hideLoading();
                    showAlert('Bulk action failed. Please try again.', 'danger');
                }
            });
        }
        
        function performBulkExport() {
            showAlert(`Exporting ${selectedUsers.length} user(s)...`, 'info');
            exportUsers('selected');
        }
        
        function exportUsers(type) {
            const urlParams = new URLSearchParams(window.location.search);
            const filters = {
                search: urlParams.get('search') || '',
                status: urlParams.get('status') || '',
                role: urlParams.get('role') || '',
                hierarchy_level: urlParams.get('hierarchy_level') || '',
                hierarchy_id: urlParams.get('hierarchy_id') || ''
            };
            
            const form = $('<form></form>')
                .attr('method', 'POST')
                .attr('action', 'export_users.php')
                .css('display', 'none');
            
            form.append($('<input>').attr({ type: 'hidden', name: 'export_type', value: type }));
            form.append($('<input>').attr({ type: 'hidden', name: 'csrf_token', value: csrfToken }));
            
            if (type === 'filtered') {
                form.append($('<input>').attr({ type: 'hidden', name: 'filters', value: JSON.stringify(filters) }));
            } else if (type === 'selected') {
                form.append($('<input>').attr({ type: 'hidden', name: 'user_ids', value: JSON.stringify(selectedUsers) }));
            }
            
            form.appendTo('body').submit();
            
            setTimeout(() => {
                form.remove();
                showAlert('Export started. Download should begin shortly.', 'success');
            }, 1000);
        }
    });
    
})(jQuery);