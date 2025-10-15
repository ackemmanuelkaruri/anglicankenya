/**
 * Coordinated Church Form Handler - Works with PHP backend for certificate clearing
 * FIXED: Coordinates with PHP backend for proper certificate clearing
 */

// Track the current service to detect changes
let currentService = null;

/**
 * Initialize the current service state when page loads
 */
function initializeCurrentService() {
    const serviceSelect = document.getElementById('service_attending');
    if (serviceSelect && serviceSelect.value) {
        currentService = serviceSelect.value;
        console.log('Initial service set to:', currentService);
        // Show appropriate sections on page load
        showServiceSections(currentService);
    }
}

/**
 * Enhanced service change handler with data reset logic
 */
function handleServiceChange() {
    const serviceSelect = document.getElementById('service_attending');
    if (!serviceSelect) {
        console.error('Service select element not found');
        return;
    }
    
    const newService = serviceSelect.value;
    
    console.log('Service changed from:', currentService, 'to:', newService);
    
    if (currentService !== null && currentService !== newService && currentService !== '') {
        const shouldReset = confirmServiceChange(currentService, newService);
        
        if (shouldReset) {
            resetServiceData(currentService);
            currentService = newService;
            showServiceSections(newService);
            showNotification('Service changed successfully. Data will be updated when you submit the form.', 'success');
        } else {
            serviceSelect.value = currentService;
            return;
        }
    } else {
        currentService = newService;
        showServiceSections(newService);
    }
}

/**
 * Show/hide subsections inside church tab
 */
function showServiceSections(service) {
    console.log('Showing sections for service:', service);
    
    const englishSection = document.getElementById('english_service_team_section');
    const kikuyuSection = document.getElementById('kikuyu_cell_group_section');
    const familySection = document.getElementById('family_group_section');
    
    if (englishSection) englishSection.style.display = 'none';
    if (kikuyuSection) kikuyuSection.style.display = 'none';
    if (familySection) familySection.style.display = 'none';
    
    switch (service) {
        case 'english':
            if (englishSection) englishSection.style.display = 'block';
            break;
        case 'kikuyu':
            if (kikuyuSection) {
                kikuyuSection.style.display = 'block';
                checkAndShowFamilyGroup();
            }
            break;
        case 'teens':
            // teens-specific sections
            break;
        case 'sunday_school':
            // sunday school-specific sections
            break;
    }
}

function checkAndShowFamilyGroup() {
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    const familySection = document.getElementById('family_group_section');
    
    if (cellGroupSelect && familySection) {
        if (cellGroupSelect.value) {
            familySection.style.display = 'block';
            loadFamilyGroups(cellGroupSelect.value);
        } else {
            familySection.style.display = 'none';
        }
    }
}

function loadFamilyGroups(cellGroup) {
    const familyGroupSelect = document.getElementById('family_group');
    if (!familyGroupSelect) return;
    
    console.log('Loading family groups for:', cellGroup);
    
    const familyGroupsData = {
        'GACHORUE': ['BETHSAIDA', 'JUDEA', 'SAMARIA', 'CANAAN', 'JERICHO'],
        'MOMBASA': ['BETHANY', 'BETHLEHEM', 'EMMAUS', 'JOPPA', 'BETHSAIDA'],
        'POSTAA': ['ST. PAUL', 'ST. PETER', 'ELISHA', 'DANIEL'],
        'POSTA B': ['CALEB', 'MOSES', 'HARUN'],
        'KAMBARA': ['ST. PAUL', 'ST. PETER', 'ST. JOHN', 'DEBORAH'],
        'GITHIRIA': ['ISAIAH', 'EZEKIEL', 'JEREMIAH']
    };
    
    familyGroupSelect.innerHTML = '<option value="">--Select Family Group--</option>';
    
    if (familyGroupsData[cellGroup]) {
        familyGroupsData[cellGroup].forEach(familyGroup => {
            const option = document.createElement('option');
            option.value = familyGroup;
            option.textContent = familyGroup;
            familyGroupSelect.appendChild(option);
        });
        
        const currentValue = familyGroupSelect.getAttribute('data-current-value');
        if (currentValue && familyGroupsData[cellGroup].includes(currentValue)) {
            familyGroupSelect.value = currentValue;
        }
    }
}

function handleCellGroupChange() {
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    if (cellGroupSelect) {
        checkAndShowFamilyGroup();
    }
}

function confirmServiceChange(oldService, newService) {
    const serviceNames = {
        'english': 'English Service',
        'kikuyu': 'Kikuyu Service',
        'teens': 'Teens Service',
        'sunday_school': 'Sunday School'
    };
    
    const message = `You are switching from ${serviceNames[oldService] || oldService} to ${serviceNames[newService] || newService}.\n\n` +
                   `This will clear the form fields related to your previous service selection.\n` +
                   `Your data will be properly updated when you submit the form.\n\n` +
                   `Are you sure you want to continue?`;
    
    return confirm(message);
}

function resetServiceData(oldService) {
    console.log('Resetting form fields for service:', oldService);
    
    switch (oldService) {
        case 'english':
            resetEnglishServiceData();
            break;
        case 'kikuyu':
            resetKikuyuServiceData();
            break;
        case 'teens':
            resetTeensServiceData();
            break;
        case 'sunday_school':
            resetSundaySchoolData();
            break;
    }
}

function resetEnglishServiceData() {
    const englishTeamSelect = document.querySelector('[name="english_service_team"]');
    if (englishTeamSelect) {
        englishTeamSelect.value = '';
    }
    console.log('English service form fields reset');
}

function resetKikuyuServiceData() {
    const cellGroupSelect = document.querySelector('[name="kikuyu_cell_group"]');
    const familyGroupSelect = document.querySelector('[name="family_group"]');
    
    if (cellGroupSelect) cellGroupSelect.value = '';
    if (familyGroupSelect) {
        familyGroupSelect.innerHTML = '<option value="">--Select Family Group--</option>';
        familyGroupSelect.value = '';
    }
    
    console.log('Kikuyu service form fields reset');
}

function resetTeensServiceData() {
    console.log('Teens service form fields reset');
}

function resetSundaySchoolData() {
    console.log('Sunday School form fields reset');
}

/**
 * Baptism & Confirmation Handlers
 */
function handleBaptismChange() {
    const baptismCertSection = document.getElementById('baptism_certificate_section');
    const baptismInterestSection = document.getElementById('baptism_interest_section');
    const currentSelection = document.querySelector('input[name="baptized"]:checked');
    const selectedValue = currentSelection ? currentSelection.value : null;
    
    console.log('Baptism status changed to:', selectedValue);
    
    if (selectedValue === 'yes') {
        if (baptismCertSection) baptismCertSection.style.display = 'block';
        if (baptismInterestSection) baptismInterestSection.style.display = 'none';
        clearInterestSelection('baptism_interest');
    } else if (selectedValue === 'no') {
        if (baptismCertSection) baptismCertSection.style.display = 'none';
        if (baptismInterestSection) baptismInterestSection.style.display = 'block';
        showNotification('Certificate will be cleared when you save the form since you selected "No"', 'info');
    } else {
        if (baptismCertSection) baptismCertSection.style.display = 'none';
        if (baptismInterestSection) baptismInterestSection.style.display = 'none';
    }
}

function handleConfirmationChange() {
    const confirmationCertSection = document.getElementById('confirmation_certificate_section');
    const confirmationInterestSection = document.getElementById('confirmation_interest_section');
    const currentSelection = document.querySelector('input[name="confirmed"]:checked');
    const selectedValue = currentSelection ? currentSelection.value : null;
    
    console.log('Confirmation status changed to:', selectedValue);
    
    if (selectedValue === 'yes') {
        if (confirmationCertSection) confirmationCertSection.style.display = 'block';
        if (confirmationInterestSection) confirmationInterestSection.style.display = 'none';
        clearInterestSelection('confirmation_interest');
    } else if (selectedValue === 'no') {
        if (confirmationCertSection) confirmationCertSection.style.display = 'none';
        if (confirmationInterestSection) confirmationInterestSection.style.display = 'block';
        showNotification('Certificate will be cleared when you save the form since you selected "No"', 'info');
    } else {
        if (confirmationCertSection) confirmationCertSection.style.display = 'none';
        if (confirmationInterestSection) confirmationInterestSection.style.display = 'none';
    }
}

function clearInterestSelection(interestName) {
    document.querySelectorAll(`input[name="${interestName}"]`).forEach(radio => {
        radio.checked = false;
    });
}

function initializeBaptismConfirmationSections() {
    if (document.querySelector('input[name="baptized"]:checked')) handleBaptismChange();
    if (document.querySelector('input[name="confirmed"]:checked')) handleConfirmationChange();
}

/**
 * Utility notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        z-index: 10000;
        max-width: 300px;
        font-weight: bold;
    `;
    
    switch (type) {
        case 'success': notification.style.backgroundColor = '#4CAF50'; break;
        case 'error': notification.style.backgroundColor = '#f44336'; break;
        case 'warning': notification.style.backgroundColor = '#ff9800'; break;
        default: notification.style.backgroundColor = '#2196F3';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

/**
 * Main setup
 */
function setupChurchFormListeners() {
    console.log('Setting up church form listeners...');
    initializeCurrentService();
    initializeBaptismConfirmationSections();
    
    const serviceSelect = document.getElementById('service_attending');
    if (serviceSelect) serviceSelect.addEventListener('change', handleServiceChange);
    
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    if (cellGroupSelect) cellGroupSelect.addEventListener('change', handleCellGroupChange);
    
    document.querySelectorAll('input[name="baptized"]').forEach(r => r.addEventListener('change', handleBaptismChange));
    document.querySelectorAll('input[name="confirmed"]').forEach(r => r.addEventListener('change', handleConfirmationChange));
    
    console.log('All church form listeners set up successfully');
}

// Init
document.addEventListener('DOMContentLoaded', setupChurchFormListeners);

// Export
window.setupChurchFormListeners = setupChurchFormListeners;
window.handleServiceChange = handleServiceChange;
