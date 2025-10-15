/**
 * ============================================
 * SERVICE WORKER - PWA Offline Support
 * Optimized version with safe caching
 * ============================================
 */

const CACHE_NAME = 'churchms-cache-v1';
const OFFLINE_URL = '/anglicankenya/offline.html';

// Files to cache for offline (all local + CDN)
const FILES_TO_CACHE = [
  '/anglicankenya/login.php',
  '/anglicankenya/dashboard.php',
  '/anglicankenya/css/login.css',
  '/anglicankenya/css/dashboard.css',
  '/anglicankenya/js/pwa-setup.js',
  '/anglicankenya/assets/icons/icon-72x72.png',
  '/anglicankenya/assets/icons/icon-96x96.png',
  '/anglicankenya/assets/icons/icon-128x128.png',
  '/anglicankenya/assets/icons/icon-144x144.png',
  '/anglicankenya/assets/icons/icon-152x152.png',
  '/anglicankenya/assets/icons/icon-192x192.png',
  '/anglicankenya/assets/icons/icon-384x384.png',
  '/anglicankenya/assets/icons/icon-512x512.png',
  '/anglicankenya/manifest.json',
  OFFLINE_URL,
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'
];

/**
 * INSTALL - cache app shell
 */
self.addEventListener('install', event => {
  console.log('[Service Worker] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(async cache => {
      console.log('[Service Worker] Caching app shell...');
      for (const file of FILES_TO_CACHE) {
        try {
          const response = await fetch(file);
          if (response.ok) {
            await cache.put(file, response.clone());
            console.log('[Service Worker] Cached:', file);
          } else {
            console.warn('[Service Worker] Skipped (not found):', file);
          }
        } catch (err) {
          console.warn('[Service Worker] Failed to cache:', file, err);
        }
      }
    })
  );
  self.skipWaiting();
});

/**
 * ACTIVATE - remove old caches
 */
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activating...');
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== CACHE_NAME)
            .map(key => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

/**
 * FETCH - network first for navigation, cache fallback
 */
self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => caches.match(OFFLINE_URL))
    );
  } else {
    event.respondWith(
      caches.match(event.request)
        .then(response => response || fetch(event.request))
    );
  }
});

/**
 * BACKGROUND SYNC (offline forms)
 */
self.addEventListener('sync', event => {
  if (event.tag === 'sync-forms') {
    event.waitUntil(syncForms());
  }
});

async function syncForms() {
  console.log('[Service Worker] Syncing offline forms...');
  // Implement your form syncing logic here
}

/**
 * PUSH NOTIFICATIONS
 */
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New notification',
    icon: '/anglicankenya/assets/icons/icon-192x192.png',
    badge: '/anglicankenya/assets/icons/icon-72x72.png',
    vibrate: [200, 100, 200]
  };

  event.waitUntil(
    self.registration.showNotification('Church MS', options)
  );
});

/**
 * NOTIFICATION CLICK
 */
self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/anglicankenya/dashboard.php')
  );
});
