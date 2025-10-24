// ROLE ASSIGNMENT SCRIPT - SECURED WITH CSRF PROTECTION
console.log('=== ROLE ASSIGNMENT SCRIPT STARTING ===');
console.log('Script loaded at:', new Date().toISOString());
console.log('jQuery available:', typeof jQuery !== 'undefined');
console.log('$ available:', typeof $ !== 'undefined');
console.log('Bootstrap available:', typeof bootstrap !== 'undefined');

// Wrap everything to ensure jQuery is loaded
(function($) {
    'use strict';
    
    console.log('Inside jQuery wrapper');

    $(document).ready(function() {
        console.log('=== DOCUMENT READY ===');
        console.log('Current page:', window.location.href);
        
        // ==========================================
        // 🔒 CSRF TOKEN RETRIEVAL
        // ==========================================
        const csrfToken = $('meta[name="csrf-token"]').attr('content') || 
                         $('input[name="csrf_token"]').val();
        
        if (!csrfToken) {
            console.error('❌ CSRF token not found! Please add <meta name="csrf-token"> to your HTML.');
            console.error('Add this to your PHP: <meta name="csrf-token" content="<?php echo $_SESSION[\'csrf_token\']; ?>">');
        } else {
            console.log('✅ CSRF token loaded successfully');
            console.log('Token length:', csrfToken.length);
        }
        
        // ==========================================
        // Initialize DataTable if available
        // ==========================================
        if ($.fn.DataTable) {
            console.log('DataTable available, initializing...');
            try {
                $('#usersTable').DataTable({
                    pageLength: 25,
                    responsive: true,
                    order: [[0, 'asc']],
                    language: {
                        search: "Search users:",
                        lengthMenu: "Show _MENU_ users per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ users",
                        emptyTable: "No users found"
                    }
                });
                console.log('✅ DataTable initialized successfully');
            } catch(e) {
                console.error('❌ DataTable initialization failed:', e);
            }
        } else {
            console.warn('⚠️ DataTable not available');
        }

        // ==========================================
        // Count and log dropdown items
        // ==========================================
        const dropdownItems = $('.dropdown-item[data-role]');
        console.log('Dropdown items found:', dropdownItems.length);
        
        if (dropdownItems.length === 0) {
            console.error('❌ NO DROPDOWN ITEMS FOUND! Check your HTML structure.');
            return;
        }
        
        // Log each dropdown item
        dropdownItems.each(function(index) {
            const $item = $(this);
            console.log(`Dropdown ${index}:`, {
                role: $item.attr('data-role'),
                userId: $item.attr('data-user-id'),
                text: $item.text().trim()
            });
        });

        // ==========================================
        // ROLE CHANGE CLICK HANDLER
        // ==========================================
        console.log('Attaching click handlers...');
        
        $(document).on('click', '.dropdown-item[data-role]', function(e) {
            console.log('🖱️ Dropdown item clicked');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            console.log('Event prevented and stopped');
            
            const $item = $(this);
            const newRole = $item.attr('data-role');
            const userId = $item.attr('data-user-id');
            
            console.log('Clicked item data:', {
                newRole: newRole,
                userId: userId,
                hasRole: !!newRole,
                hasUserId: !!userId
            });
            
            // ==========================================
            // VALIDATION: Check for required data
            // ==========================================
            if (!userId || !newRole) {
                console.error('❌ MISSING DATA:', { userId, newRole });
                showNotification('error', 'Error: Missing user ID or role data');
                return false;
            }
            
            // ==========================================
            // VALIDATION: Check for CSRF token
            // ==========================================
            if (!csrfToken) {
                console.error('❌ CSRF token missing - cannot proceed');
                showNotification('error', 'Security token missing. Please refresh the page.');
                return false;
            }
            
            const $row = $item.closest('tr');
            const $dropdown = $item.closest('.dropdown-menu');
            const $dropdownBtn = $item.closest('.role-dropdown').find('.dropdown-toggle');
            
            console.log('Found elements:', {
                row: $row.length,
                dropdown: $dropdown.length,
                button: $dropdownBtn.length
            });
            
            // Role names for confirmation
            const roleNames = {
                'super_admin': 'Super Admin',
                'national_admin': 'National Admin',
                'diocese_admin': 'Diocese Admin',
                'archdeaconry_admin': 'Archdeaconry Admin',
                'deanery_admin': 'Deanery Admin',
                'parish_admin': 'Parish Admin',
                'member': 'Member'
            };
            
            const roleName = roleNames[newRole] || newRole;
            const confirmMsg = `Are you sure you want to assign the role "${roleName}" to this user?`;
            
            console.log('Showing confirmation dialog');
            
            if (!confirm(confirmMsg)) {
                console.log('User cancelled');
                return false;
            }
            
            console.log('✓ User confirmed, proceeding...');
            
            // Close the dropdown
            try {
                $dropdown.removeClass('show');
                $dropdownBtn.removeClass('show').attr('aria-expanded', 'false');
                $('.dropdown-backdrop').remove();
                console.log('✓ Dropdown closed');
            } catch(e) {
                console.warn('⚠️ Error closing dropdown:', e);
            }
            
            // Disable button and show loading
            $dropdownBtn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i>');
            
            console.log('Button disabled, preparing AJAX request...');
            
            // ==========================================
            // PREPARE AJAX DATA WITH CSRF TOKEN
            // ==========================================
            const ajaxData = {
                user_id: userId,
                role_level: newRole,
                csrf_token: csrfToken  // 🔒 CSRF TOKEN INCLUDED
            };
            
            console.log('AJAX Request:', {
                url: '../users/assign_role_ajax.php',
                data: ajaxData,
                csrfIncluded: !!ajaxData.csrf_token
            });
            
            // ==========================================
            // PERFORM AJAX REQUEST
            // ==========================================
            $.ajax({
                url: '../users/assign_role_ajax.php',
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                timeout: 10000,
                beforeSend: function(xhr) {
                    console.log('📤 AJAX beforeSend - Request initiated');
                },
                success: function(response) {
                    console.log('=== ✅ AJAX SUCCESS ===');
                    console.log('Response:', response);
                    
                    if (response.status === 'success') {
                        console.log('✓ Role updated successfully');
                        
                        // Update the badge
                        const $roleBadge = $row.find('td:nth-child(3) .badge');
                        console.log('Updating badge:', $roleBadge.length);
                        
                        $roleBadge.removeClass(function(index, className) {
                            return (className.match(/badge-\S+/g) || []).join(' ');
                        });
                        $roleBadge.addClass('badge-' + newRole);
                        $roleBadge.text(response.new_role_display);
                        
                        // Update row class
                        $row.removeClass(function(index, className) {
                            return (className.match(/role-\S+/g) || []).join(' ');
                        });
                        $row.addClass('role-' + newRole);
                        
                        // Update avatar
                        const $avatar = $row.find('.user-avatar');
                        $avatar.removeClass(function(index, className) {
                            return (className.match(/(super_admin|national_admin|diocese_admin|archdeaconry_admin|deanery_admin|parish_admin|member)/g) || []).join(' ');
                        });
                        $avatar.addClass(newRole);
                        
                        showNotification('success', response.message || 'Role updated successfully!');
                        
                        $dropdownBtn.prop('disabled', false)
                            .html('<i class="fas fa-user-cog"></i>');
                        
                        console.log('Reloading page in 2 seconds...');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                        
                    } else if (response.status === 'info') {
                        // Handle info status (e.g., user already has this role)
                        console.log('ℹ️ Info response:', response.message);
                        showNotification('info', response.message);
                        $dropdownBtn.prop('disabled', false)
                            .html('<i class="fas fa-user-cog"></i>');
                    } else {
                        console.error('❌ Server returned error:', response.message);
                        showNotification('error', response.message || 'Failed to update role');
                        $dropdownBtn.prop('disabled', false)
                            .html('<i class="fas fa-user-cog"></i>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('=== ❌ AJAX ERROR ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Status Code:', xhr.status);
                    console.error('Response Text:', xhr.responseText);
                    
                    let errorMessage = 'An error occurred while updating the role.';
                    
                    if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMessage = errorResponse.message;
                            }
                        } catch (e) {
                            // Not JSON, handle HTTP status codes
                            if (xhr.status === 404) {
                                errorMessage = '404: assign_role_ajax.php not found. Check file path.';
                            } else if (xhr.status === 500) {
                                errorMessage = '500: Server error. Check PHP error logs.';
                            } else if (xhr.status === 403) {
                                errorMessage = '403: Permission denied. Check your access rights.';
                            } else if (status === 'timeout') {
                                errorMessage = 'Request timed out. Please try again.';
                            } else if (xhr.responseText.length < 200) {
                                errorMessage = 'Server error: ' + xhr.responseText;
                            }
                        }
                    }
                    
                    showNotification('error', errorMessage);
                    $dropdownBtn.prop('disabled', false)
                        .html('<i class="fas fa-user-cog"></i>');
                },
                complete: function() {
                    console.log('📥 AJAX complete');
                }
            });
            
            return false;
        });
        
        console.log('✅ Click handlers attached successfully');
        
        // Test click detection after 2 seconds
        setTimeout(function() {
            console.log('=== 2 SECOND CHECK ===');
            console.log('Dropdown items still present:', $('.dropdown-item[data-role]').length);
            console.log('CSRF token still available:', !!csrfToken);
        }, 2000);
    });
    
    // ==========================================
    // NOTIFICATION FUNCTION
    // ==========================================
    function showNotification(type, message) {
        console.log('📢 Showing notification:', type, message);
        
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'info' ? 'alert-info' : 
                          'alert-danger';
        
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                         type === 'info' ? 'fa-info-circle' :
                         'fa-exclamation-circle';
        
        const $notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" 
                 role="alert" 
                 style="position: fixed; top: 20px; right: 20px; z-index: 99999; 
                        min-width: 300px; max-width: 500px; 
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <i class="fas ${iconClass} me-2"></i>
                <strong>${message}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(400, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Make notification function global for testing
    window.testNotification = showNotification;
    
    console.log('=== ✅ SCRIPT INITIALIZATION COMPLETE ===');
    console.log('💡 Test notifications: window.testNotification("success", "Test message")');
    console.log('💡 Test types: "success", "error", "info"');
    
})(jQuery);

// Final check
console.log('✅ Script execution completed');