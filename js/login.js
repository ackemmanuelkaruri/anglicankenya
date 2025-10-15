if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/anglicankenya/service-worker.js')
    .then(reg => console.log('Service Worker registered:', reg))
    .catch(err => console.error('Service Worker registration failed:', err));
}
