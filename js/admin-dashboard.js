// Admin Dashboard: move inline jQuery logic into external file (CSP-safe)
(function() {
  'use strict';

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function() {
    if (!window.jQuery) {
      console.warn('jQuery missing for admin dashboard.');
      return;
    }
    var $ = window.jQuery;

    // Original inline logic moved here
    loadStatistics();
    loadData('pcc_members', 'pcc-members-container', createPCCTable);
    loadData('department_heads', 'department-heads-container', createDepartmentHeadsTable);
    loadData('clergy_members', 'clergy-members-container', createClergyTable);
    loadData('leadership_history', 'leadership-history-container', createLeadershipHistoryTable);
    loadData('family_relationships', 'parent-child-container', createParentChildTable);
    loadData('spouse_relationships', 'spouse-relationships-container', createSpouseTable);
    loadData('family_units', 'family-units-container', createFamilyUnitsTable);
    loadData('extended_family', 'extended-family-container', createExtendedFamilyTable);
    loadData('single_parents', 'single-parents-container', createSingleParentsTable);
    loadData('youth_members', 'youth-members-container', createYouthTable);
    loadData('senior_members', 'senior-members-container', createSeniorsTable);

    $('#refresh-data').on('click', function() { location.reload(); });
    $('#status-filter, #role-filter').on('change', function() { applyFilters(); });
  });

  // Below functions rely on existing definitions embedded in the page templates
  function loadStatistics() {
    var $ = window.jQuery;
    $.ajax({ url: 'api/get_stats.php?action=total_members', method: 'GET', success: function(data){ $('#total-members-count').text(data.count); } });
    $.ajax({ url: 'api/get_stats.php?action=leadership_count', method: 'GET', success: function(data){ $('#leadership-count').text(data.count); } });
    $.ajax({ url: 'api/get_stats.php?action=clergy_count', method: 'GET', success: function(data){ $('#clergy-count').text(data.count); } });
    $.ajax({ url: 'api/get_stats.php?action=family_count', method: 'GET', success: function(data){ $('#family-count').text(data.count); } });
  }

  function loadData(action, containerId, tableBuilder) {
    var $ = window.jQuery;
    $.ajax({
      url: 'api/get_data.php?action=' + action,
      method: 'GET',
      success: function(data) {
        if (data.error) {
          $('#' + containerId).html('<div class="error-message">' + data.error + '</div>');
        } else if (data.length > 0) {
          $('#' + containerId).html(tableBuilder(data));
        } else {
          $('#' + containerId).html('<div class="no-data"><i class="fas fa-inbox"></i><p>No data available</p></div>');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        $('#' + containerId).html('<div class="error-message">Error loading data. Please check the database connection and table structure.</div>');
      }
    });
  }

  // Stubs: these are expected to exist in the page context
  function createPCCTable(data){ return buildTable(data); }
  function createDepartmentHeadsTable(data){ return buildTable(data); }
  function createClergyTable(data){ return buildTable(data); }
  function createLeadershipHistoryTable(data){ return buildTable(data); }
  function createParentChildTable(data){ return buildTable(data); }
  function createSpouseTable(data){ return buildTable(data); }
  function createFamilyUnitsTable(data){ return buildTable(data); }
  function createExtendedFamilyTable(data){ return buildTable(data); }
  function createSingleParentsTable(data){ return buildTable(data); }
  function createYouthTable(data){ return buildTable(data); }
  function createSeniorsTable(data){ return buildTable(data); }
  function applyFilters(){}

  function buildTable(data) {
    if (!Array.isArray(data) || data.length === 0) return '<div class="no-data"><i class="fas fa-inbox"></i><p>No data available</p></div>';
    var keys = Object.keys(data[0]);
    var thead = '<thead><tr>' + keys.map(function(k){ return '<th>' + escapeHtml(k) + '</th>'; }).join('') + '</tr></thead>';
    var rows = data.map(function(row){
      return '<tr>' + keys.map(function(k){ return '<td>' + escapeHtml(String(row[k] ?? '')) + '</td>'; }).join('') + '</tr>';
    }).join('');
    return '<table class="table table-striped table-hover">' + thead + '<tbody>' + rows + '</tbody></table>';
  }

  function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
  }
})();

