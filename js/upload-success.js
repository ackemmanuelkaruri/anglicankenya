// Upload success page: countdown logic moved to external file
(function() {
  'use strict';
  function init() {
    var seconds = 5;
    var countdownElement = document.getElementById('countdown');
    if (!countdownElement) return;
    var timer = setInterval(function() {
      seconds--;
      countdownElement.textContent = seconds;
      if (seconds <= 0) {
        clearInterval(timer);
      }
    }, 1000);
  }
  document.addEventListener('DOMContentLoaded', init);
})();

