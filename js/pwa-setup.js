/**
 * ============================================
 * PWA SETUP SCRIPT
 * Single unified version
 * ============================================
 */

let deferredPrompt;

// Register Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/anglicankenya/service-worker.js')
      .then(registration => {
        console.log('✓ Service Worker registered:', registration.scope);
        // Check for updates on load
        registration.update();
      })
      .catch(error => {
        console.log('✗ Service Worker registration failed:', error);
      });
  });
}

// Handle Add to Home Screen prompt
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault(); // Prevent auto prompt
  deferredPrompt = e;
  showInstallPromotion();
});

// Show custom install banner
function showInstallPromotion() {
  // Skip if dismissed recently
  const dismissed = localStorage.getItem('install-prompt-dismissed');
  if (dismissed && Date.now() - parseInt(dismissed) < 7 * 24 * 60 * 60 * 1000) return;

  const banner = document.createElement('div');
  banner.id = 'install-banner';
  banner.style.cssText = `
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 50px;
    display: flex;
    gap: 15px;
    align-items: center;
    z-index: 9999;
  `;
  banner.innerHTML = `
    <span>📱 Install Church MS App</span>
    <button id="install-button" style="
      background: white;
      color: #667eea;
      border: none;
      padding: 8px 20px;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
    ">Install</button>
    <button id="close-install" style="
      background: transparent;
      color: white;
      border: none;
      font-size: 20px;
      cursor: pointer;
      padding: 0 10px;
    ">×</button>
  `;
  document.body.appendChild(banner);

  // Install button
  document.getElementById('install-button').addEventListener('click', async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      console.log('Install outcome:', outcome);
      deferredPrompt = null;
      banner.remove();
    }
  });

  // Close button
  document.getElementById('close-install').addEventListener('click', () => {
    banner.remove();
    localStorage.setItem('install-prompt-dismissed', Date.now());
  });
}

// Detect when app is installed
window.addEventListener('appinstalled', () => {
  console.log('✓ Church MS App installed successfully!');
  const banner = document.getElementById('install-banner');
  if (banner) banner.remove();
  showToast('App installed successfully! 🎉', 'success');
});

// Toast notifications
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#10b981' : '#f59e0b'};
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10000;
  `;
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// Online/offline status
window.addEventListener('online', () => showToast('✓ Back online!', 'success'));
window.addEventListener('offline', () => showToast('⚠️ You are offline', 'warning'));

// Standalone detection
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('✓ App running in standalone mode');
}
