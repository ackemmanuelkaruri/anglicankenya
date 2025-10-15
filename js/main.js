/**
 * MAIN INITIALIZATION SCRIPT - FIXED VERSION
 * Handles all initialization without conflicts
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing Church Membership Registration Form...');
    
    try {
        // Initialize tab system first
        initializeTabSystem();
        
        // Initialize form components
        initializeFormComponents();
        
        // Set up event listeners
        setupEventListeners();
        
        // Initialize sections based on saved values
        initializeSections();
        
        // Set up section save functionality
        setupSectionSaveButtons();
        
        console.log('Form initialization completed successfully');
        
    } catch (error) {
        console.error('Error during form initialization:', error);
        showInitializationError();
    }
});

/**
 * Initialize all form components in a unified manner
 */
function initializeFormComponents() {
    console.log('Initializing form components...');
    
    // Service and cell group functionality
    if (typeof setupServiceListener === 'function') {
        console.log('Setting up service listener');
        setupServiceListener();
    }
    
    if (typeof setupCellGroupListener === 'function') {
        console.log('Setting up cell group listener');
        setupCellGroupListener();
    }
    
    // Form validation
    if (typeof initFormValidation === 'function') {
        console.log('Initializing form validation');
        initFormValidation();
    }
    
    // Initialize the "Finish Update" button
    const finishButton = document.getElementById('finish-update');
    if (finishButton && typeof handleFinishUpdate === 'function') {
        console.log('Setting up finish update button');
        finishButton.addEventListener('click', handleFinishUpdate);
    }
    
    // Show success message if available
    if (typeof showSuccessAndRedirect === 'function') {
        console.log('Checking for success message');
        showSuccessAndRedirect();
    }
}

/**
 * Initialize event listeners for form elements
 */
function setupEventListeners() {
    console.log('Initializing event listeners...');
    
    // Baptism radios
    document.querySelectorAll('input[name="baptized"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (typeof toggleBaptismSections === 'function') toggleBaptismSections();
        });
    });
    
    // Confirmation radios
    document.querySelectorAll('input[name="confirmed"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (typeof toggleConfirmationSections === 'function') toggleConfirmationSections();
        });
    });
    
    // Clergy role radios
    document.querySelectorAll('input[name="has_clergy_role"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (typeof toggleClergyDetails === 'function') toggleClergyDetails();
        });
    });
    
    // Current service checkbox
    const currentServiceCheckbox = document.getElementById('is_current_service');
    if (currentServiceCheckbox && typeof toggleCurrentService === 'function') {
        currentServiceCheckbox.addEventListener('change', toggleCurrentService);
    }
    
    // Leadership role listeners
    const firstRoleType = document.querySelector('.leadership-type');
    if (firstRoleType && typeof updateLeadershipOptions === 'function') {
        firstRoleType.addEventListener('change', function() {
            updateLeadershipOptions(this);
        });
    }
    
    const firstRoleSelect = document.querySelector('.leadership-role-select');
    if (firstRoleSelect && typeof toggleOtherRoleField === 'function') {
        firstRoleSelect.addEventListener('change', function() {
            toggleOtherRoleField(this);
        });
    }
    
    const firstCurrentCheckbox = document.querySelector('.is-current-leadership');
    if (firstCurrentCheckbox && typeof toggleCurrentLeadership === 'function') {
        firstCurrentCheckbox.addEventListener('change', function() {
            toggleCurrentLeadership(this);
        });
    }
}

/**
 * Initialize sections based on current values
 */
function initializeSections() {
    console.log('Initializing sections...');
    
    // Baptism
    const baptismTab = document.querySelector('.tab-button[data-tab="baptism"]');
    if (!baptismTab || !baptismTab.classList.contains('active')) {
        if (typeof toggleBaptismSections === 'function') {
            try { toggleBaptismSections(); } 
            catch (e) { console.warn('toggleBaptismSections failed:', e.message); }
        }
    }
    
    // Confirmation
    const confirmationTab = document.querySelector('.tab-button[data-tab="confirmation"]');
    if (!confirmationTab || !confirmationTab.classList.contains('active')) {
        if (typeof toggleConfirmationSections === 'function') {
            try { toggleConfirmationSections(); } 
            catch (e) { console.warn('toggleConfirmationSections failed:', e.message); }
        }
    }
    
    // Current service
    const currentServiceCheckbox = document.getElementById('is_current_service');
    if (currentServiceCheckbox?.checked && typeof toggleCurrentService === 'function') {
        try { toggleCurrentService(); } 
        catch (e) { console.warn('toggleCurrentService failed:', e.message); }
    }
    
    // Employment
    const employmentTab = document.querySelector('.tab-button[data-tab="employment"]');
    if (!employmentTab || !employmentTab.classList.contains('active')) {
        if (typeof initEmploymentFunctionality === 'function') {
            try { initEmploymentFunctionality(); } 
            catch (e) { console.warn('initEmploymentFunctionality failed:', e.message); }
        }
    }
}

/**
 * Show error message if initialization fails
 */
function showInitializationError() {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'initialization-error';
    errorDiv.innerHTML = `
        <strong>Form Initialization Error</strong><br>
        Some features may not work properly. Please refresh the page or contact support.
    `;
    
    document.body.appendChild(errorDiv);
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) errorDiv.parentNode.removeChild(errorDiv);
    }, 10000);
}