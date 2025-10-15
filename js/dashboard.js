/**
 * ============================================
 * DASHBOARD MANAGER - Complete Dashboard Control
 * Handles: Themes, Notifications, Animations, UI Effects
 * ============================================
 */

class DashboardManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.notifications = [];
        this.sidebarOpen = window.innerWidth > 768;
        this.init();
    }

    /**
     * Initialize dashboard on load
     */
    init() {
        this.applyTheme(this.currentTheme);
        this.setupEventListeners();
        this.setupThemeSwitcher();
        this.setupSidebarToggle();
        this.setupNotifications();
        this.setupCardAnimations();
        this.setupTooltips();
        this.setupResponsive();
        this.logInit();
    }

    /**
     * ============================================
     * THEME MANAGEMENT
     * ============================================
     */

    /**
     * Get stored theme from session/localStorage
     */
    getStoredTheme() {
        return document.body.getAttribute('data-theme') || 'light';
    }

    /**
     * Apply theme to dashboard
     */
    applyTheme(theme) {
        const allowedThemes = ['light', 'dark', 'ocean', 'forest'];
        
        if (!allowedThemes.includes(theme)) {
            theme = 'light';
        }

        this.currentTheme = theme;
        document.body.setAttribute('data-theme', theme);
        this.updateThemeButtons(theme);
        this.saveThemePreference(theme);
        this.logThemeChange(theme);
    }

    /**
     * Save theme preference to server
     */
    saveThemePreference(theme) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'theme=' + encodeURIComponent(theme)
        })
        .then(response => response.json())
        .catch(error => console.warn('Theme save failed:', error));
    }

    /**
     * Update theme button active states
     */
    updateThemeButtons(theme) {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-theme') === theme) {
                btn.classList.add('active');
            }
        });
    }

    /**
     * ============================================
     * SIDEBAR MANAGEMENT
     * ============================================
     */

    /**
     * Setup sidebar toggle
     */
    setupSidebarToggle() {
        const toggleBtn = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.toggleSidebar(sidebar);
            });
        }

        // Close sidebar on nav link click (mobile)
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    this.closeSidebar(sidebar);
                }
            });
        });
    }

    /**
     * Toggle sidebar visibility
     */
    toggleSidebar(sidebar) {
        this.sidebarOpen = !this.sidebarOpen;
        if (this.sidebarOpen) {
            sidebar.classList.add('open');
        } else {
            sidebar.classList.remove('open');
        }
    }

    /**
     * Close sidebar
     */
    closeSidebar(sidebar) {
        this.sidebarOpen = false;
        sidebar.classList.remove('open');
    }

    /**
     * ============================================
     * ANIMATIONS & EFFECTS
     * ============================================
     */

    /**
     * Setup card entrance animations
     */
    setupCardAnimations() {
        const cards = document.querySelectorAll('.stat-card, .quick-action-card');
        
        cards.forEach((card, index) => {
            // Stagger animation
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in-up');
        });
    }

    /**
     * Setup card hover effects
     */
    setupCardHoverEffects() {
        document.querySelectorAll('.stat-card, .quick-action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
                this.style.boxShadow = '0 12px 24px rgba(0, 0, 0, 0.15)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '';
            });
        });
    }

    /**
     * ============================================
     * NOTIFICATIONS
     * ============================================
     */

    /**
     * Initialize notification system
     */
    setupNotifications() {
        // Check for notification container, create if not exists
        if (!document.querySelector('.notification-container')) {
            const container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 3000) {
        const container = document.querySelector('.notification-container');
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} fade-in`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${icons[type] || icons.info}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(notification);

        // Close button handler
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.closeNotification(notification);
        });

        // Auto close
        if (duration > 0) {
            setTimeout(() => this.closeNotification(notification), duration);
        }

        return notification;
    }

    /**
     * Close notification
     */
    closeNotification(notification) {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }

    /**
     * ============================================
     * TOOLTIPS
     * ============================================
     */

    /**
     * Setup tooltips
     */
    setupTooltips() {
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', (e) => this.showTooltip(e));
            element.addEventListener('mouseleave', (e) => this.hideTooltip(e));
        });
    }

    /**
     * Show tooltip
     */
    showTooltip(event) {
        const element = event.target;
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip fade-in';
        tooltip.textContent = element.getAttribute('title');
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
        
        element._tooltip = tooltip;
    }

    /**
     * Hide tooltip
     */
    hideTooltip(event) {
        const tooltip = event.target._tooltip;
        if (tooltip) {
            tooltip.classList.add('fade-out');
            setTimeout(() => tooltip.remove(), 300);
            delete event.target._tooltip;
        }
    }

    /**
     * ============================================
     * RESPONSIVE DESIGN
     * ============================================
     */

    /**
     * Handle responsive behavior
     */
    setupResponsive() {
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    /**
     * Handle window resize
     */
    handleResize() {
        const sidebar = document.querySelector('.sidebar');
        
        if (window.innerWidth > 768) {
            if (sidebar) sidebar.classList.add('open');
            this.sidebarOpen = true;
        } else {
            if (sidebar) sidebar.classList.remove('open');
            this.sidebarOpen = false;
        }
    }

    /**
     * ============================================
     * EVENT LISTENERS
     * ============================================
     */

    /**
     * Setup theme switcher event listeners
     */
    setupThemeSwitcher() {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const theme = btn.getAttribute('data-theme');
                this.applyTheme(theme);
                this.showNotification(`${theme.charAt(0).toUpperCase() + theme.slice(1)} theme applied`, 'success', 2000);
            });
        });
    }

    /**
     * Setup general event listeners
     */
    setupEventListeners() {
        // Stat cards click handler
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (card.dataset.action) {
                    this.handleStatCardClick(card);
                }
            });
        });

        // Quick action cards click handler
        document.querySelectorAll('.quick-action-card').forEach(card => {
            card.addEventListener('click', (e) => {
                // Navigate via href
                const href = card.getAttribute('href');
                if (href) {
                    window.location.href = href;
                }
            });
        });

        // Add smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    /**
     * Handle stat card click
     */
    handleStatCardClick(card) {
        const action = card.dataset.action;
        // Implement specific actions based on card
        console.log('Stat card action:', action);
    }

    /**
     * ============================================
     * UTILITY METHODS
     * ============================================
     */

    /**
     * Format number with commas
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Get current theme
     */
    getTheme() {
        return this.currentTheme;
    }

    /**
     * Set sidebar state
     */
    setSidebarOpen(isOpen) {
        const sidebar = document.querySelector('.sidebar');
        this.sidebarOpen = isOpen;
        if (isOpen) {
            sidebar?.classList.add('open');
        } else {
            sidebar?.classList.remove('open');
        }
    }

    /**
     * Refresh dashboard data
     */
    refreshDashboard() {
        this.showNotification('Refreshing dashboard...', 'info');
        
        // Add your refresh logic here
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                console.log('Dashboard refreshed');
                this.showNotification('Dashboard refreshed', 'success');
            })
            .catch(error => {
                console.error('Refresh failed:', error);
                this.showNotification('Failed to refresh dashboard', 'error');
            });
    }

    /**
     * ============================================
     * LOGGING
     * ============================================
     */

    /**
     * Log initialization
     */
    logInit() {
        console.log('%c✓ Dashboard Manager Initialized', 'color: #4caf50; font-weight: bold;');
        console.log(`Theme: ${this.currentTheme}`);
    }

    /**
     * Log theme change
     */
    logThemeChange(theme) {
        console.log(`%cTheme changed to: ${theme}`, `color: #ff9800; font-weight: bold;`);
    }
}

/**
 * ============================================
 * INITIALIZE ON DOM READY
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function() {
    window.dashboardManager = new DashboardManager();

    // Make useful methods globally accessible
    window.showNotification = (msg, type, duration) => {
        window.dashboardManager.showNotification(msg, type, duration);
    };

    window.applyTheme = (theme) => {
        window.dashboardManager.applyTheme(theme);
    };

    window.refreshDashboard = () => {
        window.dashboardManager.refreshDashboard();
    };
});

/**
 * ============================================
 * UTILITY FUNCTIONS (Can be used globally)
 * ============================================
 */

/**
 * Simple fetch wrapper with error handling
 */
function apiCall(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json'
        }
    };

    return fetch(url, { ...defaultOptions, ...options })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('API Call failed:', error);
            window.showNotification('An error occurred', 'error');
            throw error;
        });
}

/**
 * Debounce function for performance
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Check if element is visible
 */
function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

  // Theme switcher functionality
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.getAttribute('data-theme');
                document.body.setAttribute('data-theme', theme);
                
                // Save theme preference
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'theme=' + theme
                });
                
                // Update active button
                document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
            
            // Mark current theme as active
            if (this.getAttribute('data-theme') === document.body.getAttribute('data-theme')) {
                this.classList.add('active');
            }
        });