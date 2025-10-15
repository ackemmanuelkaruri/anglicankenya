document.addEventListener('DOMContentLoaded', function() {
    const profileId = document.getElementById('profile-id').value;
    
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button:not(.locked)');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
    
    // Edit functionality
    const editButtons = document.querySelectorAll('.btn-edit-tab');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            const form = document.getElementById(tab + '-form');
            const inputs = form.querySelectorAll('input, textarea, select');
            const actions = form.querySelector('.form-actions');
            
            // Enable inputs
            inputs.forEach(input => {
                input.removeAttribute('readonly');
                input.removeAttribute('disabled');
            });
            
            // Show form actions
            actions.style.display = 'block';
            this.style.display = 'none';
        });
    });
    
    // Cancel edit
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-cancel')) {
            const form = e.target.closest('.tab-form');
            const inputs = form.querySelectorAll('input, textarea, select');
            const actions = form.querySelector('.form-actions');
            const editBtn = form.parentElement.querySelector('.btn-edit-tab');
            
            // Disable inputs
            inputs.forEach(input => {
                input.setAttribute('readonly', 'readonly');
            });
            
            // Hide form actions
            actions.style.display = 'none';
            editBtn.style.display = 'block';
            
            // Reset form
            form.reset();
        }
    });
    
    // Form submissions
    const forms = document.querySelectorAll('.tab-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_minor_profile');
            formData.append('profile_id', profileId);
            formData.append('tab_section', this.id.replace('-form', ''));
            
            fetch('handlers/minor_profile_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Disable inputs and hide actions
                    const inputs = this.querySelectorAll('input, textarea, select');
                    const actions = this.querySelector('.form-actions');
                    const editBtn = this.parentElement.querySelector('.btn-edit-tab');
                    
                    inputs.forEach(input => {
                        input.setAttribute('readonly', 'readonly');
                    });
                    
                    actions.style.display = 'none';
                    editBtn.style.display = 'block';
                } else {
                    showNotification(data.message || 'Error updating profile', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            });
        });
    });
    
    // Request activation
    const activationBtn = document.querySelector('.btn-request-activation');
    if (activationBtn) {
        activationBtn.addEventListener('click', function() {
            const profileId = this.getAttribute('data-profile-id');
            
            if (confirm('Request account activation? This will notify administrators for review.')) {
                const formData = new FormData();
                formData.append('action', 'request_activation');
                formData.append('profile_id', profileId);
                
                fetch('handlers/minor_profile_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        this.style.display = 'none';
                        
                        // Show pending message
                        const pendingMsg = document.createElement('span');
                        pendingMsg.className = 'pending-request';
                        pendingMsg.textContent = 'Activation request pending admin approval';
                        this.parentElement.appendChild(pendingMsg);
                    } else {
                        showNotification(data.message || 'Error requesting activation', 'error');
                    }
                });
            }
        });
    }
    
    // Utility function for notifications
    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existing = document.querySelector('.notification');
        if (existing) {
            existing.remove();
        }
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button type="button" class="close-notification">&times;</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
        
        // Close button
        notification.querySelector('.close-notification').addEventListener('click', function() {
            notification.remove();
        });
    }
    
    // Set first accessible tab as active
    const firstAccessibleTab = document.querySelector('.tab-button:not(.locked)');
    if (firstAccessibleTab) {
        firstAccessibleTab.click();
    }
});