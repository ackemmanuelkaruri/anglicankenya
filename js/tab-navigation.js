/**
 * Enhanced Tab Navigation System - FIXED VERSION
 * Manages tabbed navigation with scrollable tabs and smooth interactions
 */
(function() {
    'use strict';
    
    let tabContainer;
    let tabButtons;
    let tabContents;
    let isInitialized = false;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing enhanced tab system...');
        initTabSystem();
        setupScrollArrows();
    });
    
    /**
     * Initialize the tab system
     */
    function initTabSystem() {
        if (isInitialized) {
            console.log('Tab system already initialized');
            return;
        }
        
        tabContainer = document.getElementById('tab-container') || document.querySelector('.tabs');
        tabButtons = document.querySelectorAll('.tab-button');
        tabContents = document.querySelectorAll('.tab-content');
        
        if (!tabContainer || tabButtons.length === 0) {
            console.warn('Tab system components not found');
            return;
        }
        
        // Set up click handlers for tab buttons
        tabButtons.forEach(button => {
            // Remove existing listeners to prevent duplicates
            button.removeEventListener('click', handleTabClick);
            button.addEventListener('click', handleTabClick);
        });
        
        // Initialize the active tab
        const activeTabButton = document.querySelector('.tab-button.active');
        if (activeTabButton) {
            const activeTab = activeTabButton.getAttribute('data-tab');
            switchToTab(activeTab);
            scrollTabIntoView(activeTabButton);
        } else {
            // If no active tab, activate the first one
            const firstTab = tabButtons[0];
            if (firstTab) {
                const firstTabName = firstTab.getAttribute('data-tab');
                switchToTab(firstTabName);
                scrollTabIntoView(firstTab);
            }
        }
        
        // Handle window resize
        window.removeEventListener('resize', checkScrollButtons);
        window.addEventListener('resize', checkScrollButtons);
        
        // Initial check for scroll buttons
        checkScrollButtons();
        
        isInitialized = true;
        console.log('Tab system initialized successfully');
    }
    
    /**
     * Handle tab click events
     */
    function handleTabClick(event) {
        event.preventDefault();
        const targetTab = this.getAttribute('data-tab');
        console.log('Tab clicked:', targetTab);
        switchToTab(targetTab);
        scrollTabIntoView(this);
    }
    
    /**
     * Switch to a specific tab
     */
    function switchToTab(tabName) {
        if (!tabName) {
            console.error('Tab name is required');
            return;
        }
        
        console.log(`Switching to tab: ${tabName}`);
        
        // Remove active class from all tabs and buttons
        tabContents.forEach(tab => {
            tab.classList.remove('active');
            tab.style.display = 'none';
        });
        
        tabButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button and corresponding tab
        const targetButton = document.querySelector(`.tab-button[data-tab="${tabName}"]`);
        if (targetButton) {
            targetButton.classList.add('active');
        } else {
            console.error(`Tab button with data-tab="${tabName}" not found`);
        }
        
        const targetTabElement = document.getElementById(tabName);
        if (targetTabElement) {
            targetTabElement.classList.add('active');
            targetTabElement.style.display = 'block';
            
            // Initialize section-specific functionality
            initTabContent(tabName);
        } else {
            console.error(`Tab content with ID '${tabName}' not found`);
        }
    }
    
    /**
     * Scroll active tab into view
     */
    function scrollTabIntoView(tabButton) {
        if (!tabContainer || !tabButton) {
            console.warn('Cannot scroll tab into view - missing container or button');
            return;
        }
        
        const containerRect = tabContainer.getBoundingClientRect();
        const buttonRect = tabButton.getBoundingClientRect();
        
        // Check if button is fully visible
        const isFullyVisible = 
            buttonRect.left >= containerRect.left && 
            buttonRect.right <= containerRect.right;
        
        if (!isFullyVisible) {
            console.log('Scrolling tab into view');
            
            // Calculate scroll position to center the button
            const containerScrollLeft = tabContainer.scrollLeft;
            const buttonOffsetLeft = tabButton.offsetLeft;
            const containerWidth = tabContainer.clientWidth;
            const buttonWidth = tabButton.offsetWidth;
            
            const targetScroll = buttonOffsetLeft - (containerWidth / 2) + (buttonWidth / 2);
            
            tabContainer.scrollTo({
                left: Math.max(0, targetScroll),
                behavior: 'smooth'
            });
        }
        
        // Update scroll button states after scrolling
        setTimeout(checkScrollButtons, 300);
    }
    
    /**
     * Initialize tab-specific content
     */
    function initTabContent(tabName) {
        console.log(`Initializing content for tab: ${tabName}`);
        
        // Create a custom event to notify other scripts
        try {
            const tabEvent = new CustomEvent('tabActivated', { 
                detail: { tabName: tabName } 
            });
            document.dispatchEvent(tabEvent);
        } catch (error) {
            console.warn('Could not dispatch tabActivated event:', error);
        }
        
        // Tab-specific initializations
        switch(tabName) {
            case 'clergy':
                if (typeof setupClergyListeners === 'function') {
                    try {
                        setupClergyListeners();
                        console.log('Clergy listeners initialized');
                    } catch (error) {
                        console.error('Error initializing clergy listeners:', error);
                    }
                }
                break;
                
            case 'ministry':
                if (typeof setupMinistryHandlers === 'function') {
                    try {
                        setupMinistryHandlers();
                        console.log('Ministry handlers initialized');
                    } catch (error) {
                        console.error('Error initializing ministry handlers:', error);
                    }
                }
                break;
                
            case 'employment':
                if (typeof initEmploymentFunctionality === 'function') {
                    try {
                        initEmploymentFunctionality();
                        console.log('Employment functionality initialized');
                    } catch (error) {
                        console.error('Error initializing employment functionality:', error);
                    }
                }
                break;
                
            case 'leadership':
                if (typeof loadSavedLeadershipRoles === 'function') {
                    try {
                        loadSavedLeadershipRoles();
                        console.log('Leadership roles loaded');
                    } catch (error) {
                        console.error('Error loading leadership roles:', error);
                    }
                }
                break;
                
            case 'family':
                if (typeof initializeFamilySection === 'function') {
                    try {
                        initializeFamilySection();
                        console.log('Family section initialized');
                    } catch (error) {
                        console.error('Error initializing family section:', error);
                    }
                }
                break;
                
            default:
                console.log(`No specific initialization for tab: ${tabName}`);
        }
    }
    
    /**
     * Setup scroll arrows for tab navigation
     */
    function setupScrollArrows() {
        const leftArrow = document.getElementById('scroll-left');
        const rightArrow = document.getElementById('scroll-right');
        
        if (!leftArrow || !rightArrow || !tabContainer) {
            console.log('Scroll arrows not found or not needed');
            return;
        }
        
        console.log('Setting up scroll arrows');
        
        // Remove existing listeners
        leftArrow.removeEventListener('click', scrollLeft);
        rightArrow.removeEventListener('click', scrollRight);
        
        // Add new listeners
        leftArrow.addEventListener('click', scrollLeft);
        rightArrow.addEventListener('click', scrollRight);
        
        // Listen to scroll events
        tabContainer.removeEventListener('scroll', checkScrollButtons);
        tabContainer.addEventListener('scroll', checkScrollButtons);
        
        // Initial check
        checkScrollButtons();
    }
    
    /**
     * Scroll left function
     */
    function scrollLeft() {
        if (!tabContainer) return;
        
        tabContainer.scrollBy({
            left: -150,
            behavior: 'smooth'
        });
        setTimeout(checkScrollButtons, 300);
    }
    
    /**
     * Scroll right function
     */
    function scrollRight() {
        if (!tabContainer) return;
        
        tabContainer.scrollBy({
            left: 150,
            behavior: 'smooth'
        });
        setTimeout(checkScrollButtons, 300);
    }
    
    /**
     * Check if scroll buttons should be visible and enabled
     */
    function checkScrollButtons() {
        if (!tabContainer) return;
        
        const leftArrow = document.getElementById('scroll-left');
        const rightArrow = document.getElementById('scroll-right');
        
        if (!leftArrow || !rightArrow) return;
        
        const canScrollLeft = tabContainer.scrollLeft > 0;
        const canScrollRight = 
            tabContainer.scrollLeft < 
            (tabContainer.scrollWidth - tabContainer.clientWidth - 1);
        
        // Update left arrow
        leftArrow.style.opacity = canScrollLeft ? '1' : '0.3';
        leftArrow.style.pointerEvents = canScrollLeft ? 'auto' : 'none';
        leftArrow.style.cursor = canScrollLeft ? 'pointer' : 'not-allowed';
        
        // Update right arrow
        rightArrow.style.opacity = canScrollRight ? '1' : '0.3';
        rightArrow.style.pointerEvents = canScrollRight ? 'auto' : 'none';
        rightArrow.style.cursor = canScrollRight ? 'pointer' : 'not-allowed';
        
        // Show/hide arrows based on need
        const needsScrolling = tabContainer.scrollWidth > tabContainer.clientWidth;
        const arrowDisplay = needsScrolling ? 'flex' : 'none';
        
        leftArrow.style.display = arrowDisplay;
        rightArrow.style.display = arrowDisplay;
    }
    
    /**
     * Reinitialize tab system (useful for dynamic content)
     */
    function reinitializeTabSystem() {
        console.log('Reinitializing tab system...');
        isInitialized = false;
        initTabSystem();
    }
    
    /**
     * Get current active tab
     */
    function getCurrentTab() {
        const activeButton = document.querySelector('.tab-button.active');
        return activeButton ? activeButton.getAttribute('data-tab') : null;
    }
    
    /**
     * Check if a tab exists
     */
    function tabExists(tabName) {
        const button = document.querySelector(`.tab-button[data-tab="${tabName}"]`);
        const content = document.getElementById(tabName);
        return !!(button && content);
    }
    
    // Make functions globally available
    window.initTabSystem = initTabSystem;
    window.switchToTab = switchToTab;
    window.scrollTabIntoView = scrollTabIntoView;
    window.reinitializeTabSystem = reinitializeTabSystem;
    window.getCurrentTab = getCurrentTab;
    window.tabExists = tabExists;
    
    // Export for module systems
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = {
            initTabSystem,
            switchToTab,
            scrollTabIntoView,
            reinitializeTabSystem,
            getCurrentTab,
            tabExists
        };
    }
})();

// Additional debugging and error handling
window.addEventListener('error', function(event) {
    if (event.filename && event.filename.includes('tab')) {
        console.error('Tab system error:', event.error);
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && typeof checkScrollButtons === 'function') {
        setTimeout(checkScrollButtons, 100);
    }
});

function initializeTabSystem() {
    console.log('Initializing tab system...');
    
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show the selected tab content
            const targetTab = document.getElementById(tabId);
            if (targetTab) {
                targetTab.style.display = 'block';
            } else {
                console.error(`Tab content with ID '${tabId}' not found`);
            }
            
            // Update active tab button
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    
    // Set the first tab as active by default
    if (tabButtons.length > 0) {
        tabButtons[0].click();
    }
    
    return true;
}

// Add this to tab-navigation.js
function switchToTab(tabId) {
    console.log(`Switching to tab: ${tabId}`);
    
    // Hide all tab contents
    const allTabContents = document.querySelectorAll('.tab-content');
    allTabContents.forEach(content => {
        content.style.display = 'none';
    });
    
    // Show the selected tab content
    const targetTab = document.getElementById(tabId);
    if (targetTab) {
        targetTab.style.display = 'block';
        console.log(`Tab ${tabId} found and displayed`);
        
        // Initialize tab-specific functionality
        initializeTabContent(tabId);
    } else {
        console.error(`Tab content with ID '${tabId}' not found`);
    }
    
    // Update active tab button
    const allTabButtons = document.querySelectorAll('.tab-btn');
    allTabButtons.forEach(button => {
        if (button.getAttribute('data-tab') === tabId) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
}

function initializeTabContent(tabId) {
    console.log(`Initializing content for tab: ${tabId}`);
    
    switch(tabId) {
        case 'family':
            // Initialize family section functionality
            if (typeof initFamilySection === 'function') {
                initFamilySection();
            }
            break;
        // Add other tab initializations as needed
        default:
            console.log(`No specific initialization for tab: ${tabId}`);
    }
}