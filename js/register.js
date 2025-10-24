/**
 * ============================================
 * MULTI-TENANT REGISTRATION SYSTEM
 * Client-Side Registration Logic (AJAX and Validation)
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registrationForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMismatchText = document.getElementById('passwordMismatch');
    
    // Get CSRF token from the hidden input field
    const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

    // Throttle utility to limit how often a function can run (prevents server spam)
    const throttle = (func, limit) => {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    };

    /**
     * ============================================
     * Real-Time Username/Email Availability Check (AJAX)
     * ============================================
     */
    
    // Function to update the status icon and help text
    function updateStatus(inputElement, statusElement, helpElement, status, message) {
        let iconHtml = '';
        let colorClass = 'text-muted';

        // Clear previous classes
        statusElement.innerHTML = '';
        inputElement.classList.remove('is-valid', 'is-invalid');
        
        if (status === 'checking') {
            iconHtml = '<i class="fas fa-spinner fa-spin text-info"></i>';
            helpElement.textContent = 'Checking availability...';
            colorClass = 'text-info';
        } else if (status === 'available') {
            iconHtml = '<i class="fas fa-check-circle text-success"></i>';
            colorClass = 'text-success';
            inputElement.classList.add('is-valid');
            helpElement.textContent = message;
        } else if (status === 'taken' || status === 'invalid') {
            iconHtml = '<i class="fas fa-times-circle text-danger"></i>';
            colorClass = 'text-danger';
            inputElement.classList.add('is-invalid');
            helpElement.textContent = message;
        } else {
            // Default/Empty state
            inputElement.classList.remove('is-valid', 'is-invalid');
            if(inputElement.id === 'username') {
                helpElement.textContent = '4-30 characters: letters, numbers, underscore, or dash only';
            } else {
                helpElement.textContent = 'Must be a valid email address.';
            }
            colorClass = 'text-muted';
        }
        
        statusElement.innerHTML = iconHtml;
        helpElement.classList.remove('text-success', 'text-danger', 'text-info', 'text-muted');
        helpElement.classList.add(colorClass);

        // Crucial: Set a 'data-status' attribute for form submission guard
        inputElement.setAttribute('data-status', status); 
    }

    // AJAX function to check credentials
    const checkCredential = (inputElement, type) => {
        const value = inputElement.value.trim();
        const statusElement = document.getElementById(type + 'Status');
        const helpElement = document.getElementById(type + 'Help');
        
        // Skip check if value is too short (for initial formatting check)
        if (type === 'username' && value.length < 4) {
            updateStatus(inputElement, statusElement, helpElement, 'default', '');
            return;
        }
        if (type === 'email' && value.length < 5) {
            updateStatus(inputElement, statusElement, helpElement, 'default', '');
            return;
        }

        updateStatus(inputElement, statusElement, helpElement, 'checking', '');

        const formData = new FormData();
        formData.append('action', 'check_' + type);
        formData.append(type, value);
        formData.append('csrf_token', csrfToken);

        fetch('check_credentials.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.status === 429) {
                updateStatus(inputElement, statusElement, helpElement, 'error', 'Too many checks. Slow down.');
                return { status: 'error', message: 'Too many checks.' };
            }
            return response.json();
        })
        .then(data => {
            let message = '';
            if (data.status === 'available') {
                message = type.charAt(0).toUpperCase() + type.slice(1) + ' is available.';
            } else if (data.status === 'taken') {
                message = type.charAt(0).toUpperCase() + type.slice(1) + ' is already in use.';
            } else if (data.status === 'invalid') {
                message = data.message;
            } else if (data.status === 'error') {
                message = data.message;
            }
            // Only update if the user hasn't typed something new while waiting for the AJAX response
            if(inputElement.value.trim() === value) {
                updateStatus(inputElement, statusElement, helpElement, data.status, message);
            }
        })
        .catch(error => {
            console.error('AJAX check failed:', error);
            updateStatus(inputElement, statusElement, helpElement, 'error', 'Server error. Cannot check availability.');
        });
    };

    // Throttle the checks to prevent spamming the server
    const throttledCheckUsername = throttle(() => checkCredential(usernameInput, 'username'), 500);
    const throttledCheckEmail = throttle(() => checkCredential(emailInput, 'email'), 500);

    if (usernameInput) {
        usernameInput.addEventListener('input', throttledCheckUsername);
    }
    
    if (emailInput) {
        emailInput.addEventListener('input', throttledCheckEmail);
    }

    /**
     * ============================================
     * Real-Time Password Strength and Match Check
     * ============================================
     */
    
    // Function to check password against criteria
    function checkPasswordCriteria(password) {
        const criteria = {
            'p-length': { regex: /.{8,}/, message: 'At least 8 characters' },
            'p-uppercase': { regex: /[A-Z]/, message: 'One uppercase letter' },
            'p-lowercase': { regex: /[a-z]/, message: 'One lowercase letter' },
            'p-number': { regex: /[0-9]/, message: 'One number' },
            'p-special': { regex: /[^A-Za-z0-9]/, message: 'One special character' }
        };

        let passedCount = 0;
        const totalCriteria = Object.keys(criteria).length;

        for (const [id, { regex }] of Object.entries(criteria)) {
            const criterionElement = document.getElementById(id);
            if (!criterionElement) continue;
            
            const badge = criterionElement.querySelector('.badge');
            const icon = badge ? badge.querySelector('i') : null;

            if (regex.test(password)) {
                passedCount++;
                criterionElement.classList.remove('text-danger');
                criterionElement.classList.add('text-success');
                
                if (badge) {
                    badge.classList.remove('bg-danger');
                    badge.classList.add('bg-success');
                }
                
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-check');
                }
            } else {
                criterionElement.classList.remove('text-success');
                criterionElement.classList.add('text-danger');
                
                if (badge) {
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-danger');
                }
                
                if (icon) {
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-times');
                }
            }
        }
        
        return { passedCount, totalCriteria };
    }

    // Function to update the strength bar
    function updatePasswordStrengthBar(passedCount, totalCriteria) {
        const bar = document.getElementById('passwordStrengthBar');
        if (!bar) return;
        
        const percentage = (passedCount / totalCriteria) * 100;
        
        bar.style.width = percentage + '%';
        
        if (percentage < 33) {
            bar.style.backgroundColor = '#dc3545'; // Red - Weak
        } else if (percentage < 66) {
            bar.style.backgroundColor = '#ffc107'; // Orange - Medium
        } else if (percentage < 100) {
            bar.style.backgroundColor = '#9acd32'; // Yellow-green - Strong
        } else {
            bar.style.backgroundColor = '#28a745'; // Green - Very Strong
        }

        // Add a 'data-strength' for form submission check
        if (passwordInput) {
            passwordInput.setAttribute('data-strength', passedCount === totalCriteria ? 'strong' : 'weak');
        }
    }

    // Function to check password match
    function checkPasswordMatch() {
        if (!confirmPasswordInput) return;
        
        // Clear old browser validation messages
        confirmPasswordInput.setCustomValidity(''); 
        
        if (passwordInput && passwordInput.value && confirmPasswordInput.value) {
            if (passwordInput.value === confirmPasswordInput.value) {
                confirmPasswordInput.classList.remove('is-invalid');
                confirmPasswordInput.classList.add('is-valid');
                if (passwordMismatchText) {
                    passwordMismatchText.classList.add('d-none');
                }
                confirmPasswordInput.setAttribute('data-match', 'true');
            } else {
                confirmPasswordInput.classList.remove('is-valid');
                confirmPasswordInput.classList.add('is-invalid');
                if (passwordMismatchText) {
                    passwordMismatchText.classList.remove('d-none');
                }
                confirmPasswordInput.setAttribute('data-match', 'false');
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            }
        } else {
            confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
            if (passwordMismatchText) {
                passwordMismatchText.classList.add('d-none');
            }
            confirmPasswordInput.setAttribute('data-match', 'false');
        }
    }

    // Event listeners for password fields
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const result = checkPasswordCriteria(this.value);
            updatePasswordStrengthBar(result.passedCount, result.totalCriteria);
            checkPasswordMatch();
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    /**
     * ============================================
     * Form Submission Guard
     * ============================================
     */
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            // Get status attributes set by JS logic
            const usernameStatus = usernameInput ? usernameInput.getAttribute('data-status') : null;
            const emailStatus = emailInput ? emailInput.getAttribute('data-status') : null;
            const passwordStrength = passwordInput ? passwordInput.getAttribute('data-strength') : null;
            const passwordMatch = confirmPasswordInput ? confirmPasswordInput.getAttribute('data-match') : null;

            let isFormValid = true;
            let errorMessages = [];

            // 1. Check Username status
            if (usernameInput && (!usernameInput.value || usernameStatus !== 'available')) {
                isFormValid = false;
                errorMessages.push('Username must be available');
                usernameInput.classList.add('is-invalid');
            }

            // 2. Check Email status
            if (emailInput && (!emailInput.value || emailStatus !== 'available')) {
                isFormValid = false;
                errorMessages.push('Email must be available');
                emailInput.classList.add('is-invalid');
            }

            // 3. Check Password Strength
            if (passwordInput && (!passwordInput.value || passwordStrength !== 'strong')) {
                isFormValid = false;
                errorMessages.push('Password does not meet all strength criteria');
                passwordInput.classList.add('is-invalid');
            }
            
            // 4. Check Password Match
            if (confirmPasswordInput && (!confirmPasswordInput.value || passwordMatch !== 'true')) {
                isFormValid = false;
                errorMessages.push('Passwords must match');
                confirmPasswordInput.classList.add('is-invalid');
            }
            
            if (!isFormValid) {
                e.preventDefault();
                alert('Please correct the following:\n\n• ' + errorMessages.join('\n• '));
                return false;
            }
        });
    }
    
    /**
     * ============================================
     * Church Hierarchy Selection Logic
     * ============================================
     */
    const dioceseSelect = document.getElementById('diocese');
    const archdeaconrySelect = document.getElementById('archdeaconry');
    const deanerySelect = document.getElementById('deanery');
    const parishSelect = document.getElementById('parish');

    // Load dioceses on page load
    if (dioceseSelect) {
        fetch('get_hierarchy.php?level=diocese')
            .then(response => response.json())
            .then(data => {
                dioceseSelect.innerHTML = '<option value="">-- Select Diocese --</option>';
                data.forEach(diocese => {
                    const option = document.createElement('option');
                    option.value = diocese.name;
                    option.textContent = diocese.name;
                    dioceseSelect.appendChild(option);
                });
                dioceseSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading dioceses:', error);
                dioceseSelect.innerHTML = '<option value="">Error loading dioceses</option>';
            });
    }

    // Diocese change event
    if (dioceseSelect && archdeaconrySelect && deanerySelect && parishSelect) {
        dioceseSelect.addEventListener('change', function() {
            const diocese = this.value;
            archdeaconrySelect.innerHTML = '<option value="">-- Select Archdeaconry --</option>';
            deanerySelect.innerHTML = '<option value="">-- Select Deanery --</option>';
            parishSelect.innerHTML = '<option value="">-- Select Parish --</option>';
            
            archdeaconrySelect.disabled = true;
            deanerySelect.disabled = true;
            parishSelect.disabled = true;
            
            if (diocese) {
                fetch(`get_hierarchy.php?level=archdeaconry&diocese=${encodeURIComponent(diocese)}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(archdeaconry => {
                            const option = document.createElement('option');
                            option.value = archdeaconry.name;
                            option.textContent = archdeaconry.name;
                            archdeaconrySelect.appendChild(option);
                        });
                        archdeaconrySelect.disabled = false;
                    })
                    .catch(error => console.error('Error loading archdeaconries:', error));
            }
        });

        // Archdeaconry change event
        archdeaconrySelect.addEventListener('change', function() {
            const diocese = dioceseSelect.value;
            const archdeaconry = this.value;
            deanerySelect.innerHTML = '<option value="">-- Select Deanery --</option>';
            parishSelect.innerHTML = '<option value="">-- Select Parish --</option>';
            
            deanerySelect.disabled = true;
            parishSelect.disabled = true;
            
            if (archdeaconry) {
                fetch(`get_hierarchy.php?level=deanery&diocese=${encodeURIComponent(diocese)}&archdeaconry=${encodeURIComponent(archdeaconry)}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(deanery => {
                            const option = document.createElement('option');
                            option.value = deanery.name;
                            option.textContent = deanery.name;
                            deanerySelect.appendChild(option);
                        });
                        deanerySelect.disabled = false;
                    })
                    .catch(error => console.error('Error loading deaneries:', error));
            }
        });

        // Deanery change event
        deanerySelect.addEventListener('change', function() {
            const diocese = dioceseSelect.value;
            const archdeaconry = archdeaconrySelect.value;
            const deanery = this.value;
            parishSelect.innerHTML = '<option value="">-- Select Parish --</option>';
            
            parishSelect.disabled = true;
            
            if (deanery) {
                fetch(`get_hierarchy.php?level=parish&diocese=${encodeURIComponent(diocese)}&archdeaconry=${encodeURIComponent(archdeaconry)}&deanery=${encodeURIComponent(deanery)}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(parish => {
                            const option = document.createElement('option');
                            option.value = parish.name;
                            option.textContent = parish.name;
                            parishSelect.appendChild(option);
                        });
                        parishSelect.disabled = false;
                    })
                    .catch(error => console.error('Error loading parishes:', error));
            }
        });
    }
});