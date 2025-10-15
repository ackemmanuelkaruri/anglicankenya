document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const password = document.getElementById('password');
    const strengthBar = document.getElementById('passwordStrength');
    
    password.addEventListener('input', function() {
        const val = this.value;
        const strength = val.length < 6 ? 'weak' : (val.length < 10 ? 'medium' : 'strong');
        strengthBar.className = 'password-strength strength-' + strength;
    });

    // Password match validator
    const confirmPassword = document.getElementById('confirm_password');
    const mismatchMsg = document.getElementById('passwordMismatch');
    
    confirmPassword.addEventListener('input', function() {
        if (this.value && this.value !== password.value) {
            mismatchMsg.classList.remove('d-none');
            this.setCustomValidity('Passwords do not match');
        } else {
            mismatchMsg.classList.add('d-none');
            this.setCustomValidity('');
        }
    });

    // Church hierarchy selection
    const dioceseSelect = document.getElementById('diocese');
    const archdeaconrySelect = document.getElementById('archdeaconry');
    const deanerySelect = document.getElementById('deanery');
    const parishSelect = document.getElementById('parish');

    // Load dioceses on page load
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
            // Enable the diocese dropdown
            dioceseSelect.disabled = false;
        })
        .catch(error => console.error('Error loading dioceses:', error));

    // Diocese change event
    dioceseSelect.addEventListener('change', function() {
        const diocese = this.value;
        archdeaconrySelect.innerHTML = '<option value="">-- Select Archdeaconry --</option>';
        deanerySelect.innerHTML = '<option value="">-- Select Deanery --</option>';
        parishSelect.innerHTML = '<option value="">-- Select Parish --</option>';
        
        // Reset disabled states
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
                    // Enable the archdeaconry dropdown
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
        
        // Reset disabled states
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
                    // Enable the deanery dropdown
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
        
        // Reset disabled state
        parishSelect.disabled = true;
        
        if (deanery) {
            fetch(`get_hierarchy.php?level=parish&diocese=${encodeURIComponent(diocese)}&archdeaconry=${encodeURIComponent(archdeaconry)}&deanery=${encodeURIComponent(deanery)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(parish => {
                        const option = document.createElement('option');
                        option.value = parish.name; // Updated to use parish.name
                        option.textContent = parish.name; // Updated to use parish.name
                        parishSelect.appendChild(option);
                    });
                    // Enable the parish dropdown
                    parishSelect.disabled = false;
                })
                .catch(error => console.error('Error loading parishes:', error));
        }
    });
});