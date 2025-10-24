/**
 * ============================================
 * ANALYTICS DASHBOARD CUSTOM JAVASCRIPT
 * Handles hierarchical scope selection and control panel updates.
 * ============================================
 */

// Helper to update URL params while preserving others
function updateUrlParams(newParams) {
    const url = new URL(window.location.href);
    
    // Preserve existing parameters
    const existingParams = {
        category: url.searchParams.get('category'),
        query: url.searchParams.get('query'),
        scope_level: url.searchParams.get('scope_level'),
        scope_id: url.searchParams.get('scope_id'),
        view: url.searchParams.get('view'),
        compare: url.searchParams.get('compare')
    };
    
    // Merge new params
    const finalParams = {...existingParams, ...newParams};

    // Clear existing search params
    url.searchParams.forEach((value, key) => url.searchParams.delete(key));

    // Set all final params
    Object.keys(finalParams).forEach(key => {
        if (finalParams[key] !== null && finalParams[key] !== undefined) {
            url.searchParams.set(key, finalParams[key]);
        }
    });

    window.location.href = url.toString();
}

// ============================================
// Control Panel Functions
// ============================================

// Data Scope Change (using the new logic)
function changeScope(scopeValue) {
    // The value is formatted as 'level_id' (e.g., 'diocese_1', 'global_0')
    const [level, idStr] = scopeValue.split('_');
    const id = (level === 'global' || idStr === '0') ? null : idStr;
    
    updateUrlParams({ scope_level: level, scope_id: id });
}

// View Mode Change
function changeView(mode) {
    updateUrlParams({ view: mode });
}

// Comparison Change
function changeComparison(period) {
    updateUrlParams({ compare: period });
}

// Export Function
function exportData() {
    alert('Export to Excel functionality will be implemented.\nThis will export the current query results to an Excel file, filtered by the active scope.');
}

// ============================================
// Initialization Logic
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Sidebar Category Toggle
    document.querySelectorAll('.category-header').forEach(header => {
        header.addEventListener('click', function() {
            const category = this.dataset.category;
            const submenu = document.getElementById('submenu-' + category);
            const icon = this.querySelector('.fa-chevron-down');
            
            // Close all other submenus
            document.querySelectorAll('.submenu').forEach(sm => {
                if (sm !== submenu) {
                    sm.classList.remove('show');
                }
            });
            
            // Toggle current submenu
            submenu.classList.toggle('show');
            icon.style.transform = submenu.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0)';
        });
    });

    // Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('analyticsSidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    // Initialize tooltips (requires jQuery)
    if (typeof jQuery !== 'undefined') {
        jQuery(function ($) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    }
});