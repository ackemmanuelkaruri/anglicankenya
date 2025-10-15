/**
 * Form Initialization - FIXED VERSION
 * Handles form setup and event listeners initialization
 */

/**
 * Function to initialize the form with existing data and apply conditions
 */
function initializeFormConditions() {
    // Check if user previously said "No" and mark it
    const baptizedNo = document.getElementById('baptized_no');
    if (baptizedNo && baptizedNo.checked) {
        baptizedNo.setAttribute('data-was-checked', 'true');
    }
    
    const confirmedNo = document.getElementById('confirmed_no');
    if (confirmedNo && confirmedNo.checked) {
        confirmedNo.setAttribute('data-was-checked', 'true');
    }
    
    // Initialize current selections - FIXED function names
    if (typeof handleServiceChange === 'function') handleServiceChange();
    if (typeof handleCellGroupChange === 'function') {
        // Only call if there's a cell group selected
        const cellGroupSelect = document.getElementById('kikuyu_cell_group');
        if (cellGroupSelect && cellGroupSelect.value) {
            handleCellGroupChange();
        }
    }
    
    // Initialize church sections
    if (typeof initializeChurchSections === 'function') {
        initializeChurchSections();
    }
}

/**
 * Setup all event listeners when the page loads
 */
function setupEventListeners() {
    // Setup church form listeners - FIXED function name
    if (typeof setupChurchFormListeners === 'function') {
        setupChurchFormListeners();
    }
    
    // Service selection event listeners - FIXED to use correct function
    const serviceSelect = document.getElementById('service_attending');
    if (serviceSelect && typeof handleServiceChange === 'function') {
        serviceSelect.addEventListener('change', handleServiceChange);
    }
    
    // Cell group selection event listeners - FIXED to use correct function
    const cellGroupSelect = document.getElementById('kikuyu_cell_group');
    if (cellGroupSelect && typeof handleCellGroupChange === 'function') {
        cellGroupSelect.addEventListener('change', handleCellGroupChange);
    }
    
    // Family group selection event listeners (if needed)
    const familyGroupSelect = document.getElementById('family_group');
    if (familyGroupSelect) {
        familyGroupSelect.addEventListener('change', function() {
            // Family group selection handler if needed
            console.log('Family group changed to:', this.value);
        });
    }
    
    // Track when "No" is selected for baptism
    const baptizedNo = document.getElementById('baptized_no');
    if (baptizedNo) {
        baptizedNo.addEventListener('change', function() {
            if (this.checked) {
                this.setAttribute('data-was-checked', 'true');
            }
        });
        if (typeof handleBaptismChange === 'function') {
            baptizedNo.addEventListener('change', handleBaptismChange);
        }
    }
    
    // Track when "No" is selected for confirmation
    const confirmedNo = document.getElementById('confirmed_no');
    if (confirmedNo) {
        confirmedNo.addEventListener('change', function() {
            if (this.checked) {
                this.setAttribute('data-was-checked', 'true');
            }
        });
        if (typeof handleConfirmationChange === 'function') {
            confirmedNo.addEventListener('change', handleConfirmationChange);
        }
    }
    
    // Baptism radio button event listeners
    const baptizedYes = document.getElementById('baptized_yes');
    if (baptizedYes) {
        baptizedYes.addEventListener('change', function() {
            if (typeof handleBaptismChange === 'function') handleBaptismChange();
        });
    }
    
    // Confirmation radio button event listeners
    const confirmedYes = document.getElementById('confirmed_yes');
    if (confirmedYes) {
        confirmedYes.addEventListener('change', function() {
            if (typeof handleConfirmationChange === 'function') handleConfirmationChange();
        });
    }
    
    // Setup section save buttons - only if function exists
    if (typeof setupSectionSaveButtons === 'function') {
        setupSectionSaveButtons();
    }
}

/**
 * Make functions available globally for HTML onclick handlers - FIXED VERSION
 */
function setupGlobalFunctions() {
    // Church functions
    if (typeof handleServiceChange === 'function') window.handleServiceChange = handleServiceChange;
    if (typeof updateFamilyGroups === 'function') window.updateFamilyGroups = updateFamilyGroups;
    if (typeof handleBaptismChange === 'function') window.handleBaptismChange = handleBaptismChange;
    if (typeof handleConfirmationChange === 'function') window.handleConfirmationChange = handleConfirmationChange;
    
    // Legacy function names for backward compatibility
    if (typeof toggleServiceFields === 'function') window.toggleServiceFields = toggleServiceFields;
    if (typeof enforceServiceAttendingSelection === 'function') window.enforceServiceAttendingSelection = enforceServiceAttendingSelection;
    if (typeof toggleBaptismSections === 'function') window.toggleBaptismSections = toggleBaptismSections;
    if (typeof toggleConfirmationSections === 'function') window.toggleConfirmationSections = toggleConfirmationSections;
    
    // Other functions - only add if they exist
    if (typeof toggleClergyDetails === 'function') window.toggleClergyDetails = toggleClergyDetails;
    if (typeof toggleCurrentService === 'function') window.toggleCurrentService = toggleCurrentService;
    
    // Leadership functions - only add if they exist
    if (typeof addLeadershipRole === 'function') window.addLeadershipRole = addLeadershipRole;
    if (typeof removeLeadershipRole === 'function') window.removeLeadershipRole = removeLeadershipRole;
    if (typeof updateLeadershipOptions === 'function') window.updateLeadershipOptions = updateLeadershipOptions;
    if (typeof toggleOtherRoleField === 'function') window.toggleOtherRoleField = toggleOtherRoleField;
    if (typeof toggleCurrentLeadership === 'function') window.toggleCurrentLeadership = toggleCurrentLeadership;
    
    // Employment functions - only add if they exist
    if (typeof addEmploymentRole === 'function') window.addEmploymentRole = addEmploymentRole;
    if (typeof removeEmploymentRole === 'function') window.removeEmploymentRole = removeEmploymentRole;
    
    // Save function - only add if it exists
    if (typeof saveSectionData === 'function') window.saveSectionData = saveSectionData;
}

// Expose the functions for main.js to call
window.initializeFormConditions = initializeFormConditions;
window.setupEventListeners = setupEventListeners;
window.setupGlobalFunctions = setupGlobalFunctions;