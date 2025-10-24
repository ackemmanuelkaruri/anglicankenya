/**
 * Church Section JavaScript - Responsive Version
 * Handles dynamic form behavior and AJAX submission for church details
 */

let currentService = null;  // ← At the top, outside DOMContentLoaded

document.addEventListener('DOMContentLoaded', function() {
    console.log('Church Section JS: DOM Loaded');
    
    const serviceSelect = document.getElementById('service_attending');
    console.log('Service Select Element:', serviceSelect);

    // Initialize service display on page load
    if (serviceSelect) {
        currentService = serviceSelect.value || '';
        console.log('Initial service value:', currentService);
        
        // Show the correct sections based on current service
        if (currentService) {
            showServiceSections(currentService);
        }
        
        // Initialize baptism/confirmation sections on page load
        const baptizedStatus = document.querySelector('input[name="baptized"]:checked');
        const confirmedStatus = document.querySelector('input[name="confirmed"]:checked');
        
        console.log('Baptized status:', baptizedStatus ? baptizedStatus.value : 'none');
        console.log('Confirmed status:', confirmedStatus ? confirmedStatus.value : 'none');
        
        if (baptizedStatus) {
            handleBaptismChange({ target: baptizedStatus });
        }
        
        if (confirmedStatus) {
            handleConfirmationChange({ target: confirmedStatus });
        }
    }

    // Event: service change
    if (serviceSelect) {
        serviceSelect.addEventListener('change', handleServiceChange);
        console.log('Service change listener attached');
    }

// Event: cell group change
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    if (cellGroupSelect) {
        cellGroupSelect.addEventListener('change', checkAndShowFamilyGroup);
        console.log('Cell group listener attached');
        
        // Initialize family group if cell group is already selected
        if (cellGroupSelect.value) {
            checkAndShowFamilyGroup();
        }
    }

    // Event: baptism / confirmation status
    const baptismYes = document.getElementById('baptized_yes');
    const baptismNo = document.getElementById('baptized_no');
    const confirmationYes = document.getElementById('confirmed_yes');
    const confirmationNo = document.getElementById('confirmed_no');

    if (baptismYes) baptismYes.addEventListener('change', handleBaptismChange);
    if (baptismNo) baptismNo.addEventListener('change', handleBaptismChange);
    if (confirmationYes) confirmationYes.addEventListener('change', handleConfirmationChange);
    if (confirmationNo) confirmationNo.addEventListener('change', handleConfirmationChange);

// Event: save button click
    const churchSaveBtn = document.querySelector('.section-save-btn[data-section="church"]');
    if (churchSaveBtn) {
        churchSaveBtn.addEventListener('click', handleChurchFormSubmit);
        console.log('Save button listener attached');
    }
    
    console.log('Church Section JS: Initialization complete');
});

/**
 * Handle church form submission via AJAX (Fetch)
 */
function handleChurchFormSubmit(e) {
    e.preventDefault();

    const btn = e.currentTarget;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const churchSection = document.getElementById('church');
    if (!churchSection) {
        showNotification('Error: Church section not found', 'danger');
        resetButton(btn, originalText);
        return;
    }

    const formData = new FormData();
    const inputs = churchSection.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if (input.type === 'file' && input.files.length > 0) {
            formData.append(input.name, input.files[0]);
        } else if (input.type === 'radio' || input.type === 'checkbox') {
            if (input.checked) formData.append(input.name, input.value);
        } else {
            formData.append(input.name, input.value);
        }
    });

    // Section identifier for PHP handler
    formData.append('section_type', 'church');

// Log what we're about to send
console.log('Sending church data:', formData);
console.log('Service attending:', formData.get('service_attending'));
console.log('English team:', formData.get('english_service_team'));
console.log('Kikuyu group:', formData.get('kikuyu_cell_group'));
console.log('Baptized:', formData.get('baptized'));
console.log('Confirmed:', formData.get('confirmed'));   

    // Send to the correct PHP handler
fetch('../service/church_update.php', {
    method: 'POST',
    body: formData
})
    
    .then(response => response.text())
    .then(text => {
        console.log('========== RAW RESPONSE START ==========');
        console.log(text);
        console.log('========== RAW RESPONSE END ==========');
        
        const data = JSON.parse(text);
        
        if (data.success) {
            showNotification(data.message || 'Church details saved successfully!', 'success');
        } else {
            showNotification(data.message || 'Error saving church details.', 'danger');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        showNotification('An unexpected error occurred while saving.', 'danger');
    })
    .finally(() => {
        resetButton(btn, originalText);
    });
}



/** Helper: Reset button state */
function resetButton(btn, text) {
    btn.disabled = false;
    btn.innerHTML = text;
}

/** Handle service change */
function handleServiceChange() {
    const serviceSelect = document.getElementById('service_attending');
    if (!serviceSelect) return;

    const newService = serviceSelect.value;

    if (currentService !== null && currentService !== newService && currentService !== '') {
        const shouldReset = confirmServiceChange(currentService, newService);
        if (shouldReset) {
            resetServiceData(currentService);
            currentService = newService;
            showServiceSections(newService);
            showNotification('Service changed. Remember to save your changes.', 'info');
        } else {
            serviceSelect.value = currentService;
            return;
        }
    } else {
        currentService = newService;
        showServiceSections(newService);
    }
}

/** Show/hide sections based on service */
function showServiceSections(service) {
    console.log('showServiceSections called with:', service);
    
    const englishSection = document.getElementById('english_service_team_section');
    const kikuyuSection = document.getElementById('kikuyu_cell_group_section');
    const familySection = document.getElementById('family_group_section');

    console.log('English section:', englishSection);
    console.log('Kikuyu section:', kikuyuSection);
    console.log('Family section:', familySection);

    // Hide all sections first
    if (englishSection) englishSection.style.display = 'none';
    if (kikuyuSection) kikuyuSection.style.display = 'none';
    if (familySection) familySection.style.display = 'none';

    console.log('All sections hidden, now showing for service:', service);

    switch (service) {
        case 'english':
            if (englishSection) {
                englishSection.style.display = 'block';
                console.log('✓ English section displayed');
            } else {
                console.error('✗ English section not found!');
            }
            break;
        case 'kikuyu':
            if (kikuyuSection) {
                kikuyuSection.style.display = 'block';
                console.log('✓ Kikuyu section displayed');
                checkAndShowFamilyGroup();
            } else {
                console.error('✗ Kikuyu section not found!');
            }
            break;
        case 'teens':
        case 'sunday_school':
            console.log('No additional sections for', service);
            break;
        default:
            console.log('No service selected or unknown service:', service);
    }
}

/** Check and show family groups */
function checkAndShowFamilyGroup() {
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    const familySection = document.getElementById('family_group_section');

    console.log('checkAndShowFamilyGroup called');
    console.log('Cell group value:', cellGroupSelect ? cellGroupSelect.value : 'not found');

    if (cellGroupSelect && familySection) {
        if (cellGroupSelect.value) {
            familySection.style.display = 'block';
            console.log('✓ Family group section displayed');
            loadFamilyGroups(cellGroupSelect.value);
        } else {
            familySection.style.display = 'none';
            console.log('Family group section hidden (no cell group selected)');
        }
    }
}

/** Load family groups based on cell group */
function loadFamilyGroups(cellGroup) {
    const familyGroupSelect = document.getElementById('family_group');
    if (!familyGroupSelect) {
        console.error('Family group select not found!');
        return;
    }

    console.log('Loading family groups for:', cellGroup);

    const familyGroupsData = {
        'GACHORUE': ['BETHSAIDA', 'JUDEA', 'SAMARIA', 'CANAAN', 'JERICHO'],
        'MOMBASA': ['BETHANY', 'BETHLEHEM', 'EMMAUS', 'JOPPA', 'BETHSAIDA'],
        'POSTAA': ['ST. PAUL', 'ST. PETER', 'ELISHA', 'DANIEL'],
        'POSTA B': ['CALEB', 'MOSES', 'HARUN'],
        'KAMBARA': ['ST. PAUL', 'ST. PETER', 'ST. JOHN', 'DEBORAH'],
        'GITHIRIA': ['ISAIAH', 'EZEKIEL', 'JEREMIAH']
    };

    const currentValue = familyGroupSelect.value;
    familyGroupSelect.innerHTML = '<option value="">--Select Family Group--</option>';

    if (familyGroupsData[cellGroup]) {
        familyGroupsData[cellGroup].forEach(group => {
            const option = document.createElement('option');
            option.value = group;
            option.textContent = group;
            if (group === currentValue) option.selected = true;
            familyGroupSelect.appendChild(option);
        });
    }
}

/** Reset service-related data */
function resetServiceData(oldService) {
    const englishTeam = document.querySelector('select[name="english_service_team"]');
    const cellGroup = document.querySelector('select[name="kikuyu_cell_group"]');
    const familyGroup = document.querySelector('select[name="family_group"]');

    if (englishTeam) englishTeam.value = '';
    if (cellGroup) cellGroup.value = '';
    if (familyGroup) familyGroup.value = '';

    console.log(`Reset data for service change from ${oldService}`);
}

/** Confirm service change */
function confirmServiceChange(oldService, newService) {
    const names = {
        'english': 'English Service',
        'kikuyu': 'Kikuyu Service',
        'teens': 'Teens Service',
        'sunday_school': 'Sunday School'
    };

    return confirm(
        `Changing from ${names[oldService] || oldService} to ${names[newService] || newService} will reset team/cell assignments. Continue?`
    );
}

/** Handle baptism status change */
function handleBaptismChange(e) {
    const cert = document.getElementById('baptism_certificate_section');
    const interest = document.getElementById('baptism_interest_section');

    if (e.target.value === 'yes') {
        if (cert) cert.style.display = 'block';
        if (interest) interest.style.display = 'none';
    } else {
        if (cert) cert.style.display = 'none';
        if (interest) interest.style.display = 'block';
    }
}

/** Handle confirmation status change */
function handleConfirmationChange(e) {
    const cert = document.getElementById('confirmation_certificate_section');
    const interest = document.getElementById('confirmation_interest_section');

    if (e.target.value === 'yes') {
        if (cert) cert.style.display = 'block';
        if (interest) interest.style.display = 'none';
    } else {
        if (cert) cert.style.display = 'none';
        if (interest) interest.style.display = 'block';
    }
}

/**
 * Display notifications - RESPONSIVE VERSION
 * Adjusts positioning based on screen size
 */
function showNotification(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.setAttribute('role', 'alert');
    
    // Responsive positioning
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Mobile: Full width at top
        alert.style.position = 'fixed';
        alert.style.top = '10px';
        alert.style.left = '10px';
        alert.style.right = '10px';
        alert.style.width = 'calc(100% - 20px)';
        alert.style.zIndex = '9999';
        alert.style.margin = '0';
    } else {
        // Desktop: Top right corner
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.style.maxWidth = '500px';
    }
    
    // Add shadow for better visibility
    alert.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alert);

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 150);
    }, 5000);
    
    // Add close button functionality if Bootstrap isn't handling it
    const closeBtn = alert.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 150);
        });
    }
}

// Handle window resize to reposition notifications if needed
window.addEventListener('resize', function() {
    const notifications = document.querySelectorAll('.alert[role="alert"]');
    const isMobile = window.innerWidth <= 768;
    
    notifications.forEach(alert => {
        if (isMobile) {
            alert.style.top = '10px';
            alert.style.left = '10px';
            alert.style.right = '10px';
            alert.style.width = 'calc(100% - 20px)';
            alert.style.minWidth = 'auto';
            alert.style.maxWidth = 'none';
        } else {
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.left = 'auto';
            alert.style.width = 'auto';
            alert.style.minWidth = '300px';
            alert.style.maxWidth = '500px';
        }
    });
});