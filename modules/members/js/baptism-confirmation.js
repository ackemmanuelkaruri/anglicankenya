/**
 * Baptism and Confirmation Handlers
 * Manages baptism and confirmation form logic and visibility
 */

/**
 * Setup event listeners for baptism radio buttons
 */
function setupBaptismListeners() {
    const baptismRadios = document.querySelectorAll('input[name="baptized"]');
    baptismRadios.forEach(radio => {
        radio.addEventListener('change', toggleBaptismSections);
    });
    
    // Initial setup
    toggleBaptismSections();
} 

/**
 * Setup event listeners for confirmation radio buttons
 */
function setupConfirmationListeners() {
    const confirmationRadios = document.querySelectorAll('input[name="confirmed"]');
    confirmationRadios.forEach(radio => {
        radio.addEventListener('change', toggleConfirmationSections);
    });
    
    // Initial setup
    toggleConfirmationSections();
}

/**
 * Toggle visibility of baptism-related sections
 */
function toggleBaptismSections() {
    const baptismValue = document.querySelector('input[name="baptized"]:checked')?.value;
    const baptismCertSection = document.getElementById('baptism_certificate_section');
    const baptismInterestSection = document.getElementById('baptism_interest_section');
    
    if (!baptismCertSection || !baptismInterestSection) return;
    
    if (baptismValue === 'yes') {
        baptismCertSection.style.display = 'block';
        baptismInterestSection.style.display = 'none';
    } else if (baptismValue === 'no') {
        baptismCertSection.style.display = 'none';
        baptismInterestSection.style.display = 'block';
    } else {
        baptismCertSection.style.display = 'none';
        baptismInterestSection.style.display = 'none';
    }
}

/**
 * Toggle visibility of confirmation-related sections
 */
function toggleConfirmationSections() {
    const confirmationValue = document.querySelector('input[name="confirmed"]:checked')?.value;
    const confirmationCertSection = document.getElementById('confirmation_certificate_section');
    const confirmationInterestSection = document.getElementById('confirmation_interest_section');
    
    if (!confirmationCertSection || !confirmationInterestSection) return;
    
    if (confirmationValue === 'yes') {
        confirmationCertSection.style.display = 'block';
        confirmationInterestSection.style.display = 'none';
    } else if (confirmationValue === 'no') {
        confirmationCertSection.style.display = 'none';
        confirmationInterestSection.style.display = 'block';
    } else {
        confirmationCertSection.style.display = 'none';
        confirmationInterestSection.style.display = 'none';
    }
}