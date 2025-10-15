// Admin: Advanced Queries page handlers (CSP-compliant)
(function() {
  'use strict';

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function exportToCSV() {
    var table = document.querySelector('.table');
    if (!table) return;
    var rows = table.rows;
    var csv = [];

    var headers = [];
    for (var i = 0; i < rows[0].cells.length; i++) {
      headers.push(rows[0].cells[i].innerText);
    }
    csv.push(headers.join(','));

    for (var r = 1; r < rows.length; r++) {
      var row = [];
      for (var c = 0; c < rows[r].cells.length; c++) {
        row.push('"' + rows[r].cells[c].innerText + '"');
      }
      csv.push(row.join(','));
    }

    var csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    var downloadLink = document.createElement('a');
    downloadLink.download = 'query_results.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.click();
  }

  onReady(function() {
    // jQuery-based UI present in page; keep it but add our listeners in vanilla
    var exportBtn = document.getElementById('export-csv-btn');
    if (exportBtn) exportBtn.addEventListener('click', exportToCSV);

    var printBtn = document.getElementById('print-results-btn');
    if (printBtn) printBtn.addEventListener('click', function() { window.print(); });

    // Maintain existing jQuery behavior if jQuery is present
    if (window.jQuery) {
      var $ = window.jQuery;
      $('#query_type').on('change', function() {
        var queryType = $(this).val();
        $('.query-option').removeClass('active');
        if (queryType) {
          $('#' + queryType + '_options').addClass('active');
        }
      });
      $('#query_type').trigger('change');
    }
  });
})();

