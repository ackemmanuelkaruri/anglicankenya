/**
 * ============================================
 * SERVICE WORKER - PWA Offline Support
 * Fixed version - excludes dynamic pages from cache
 * ============================================
 */
const CACHE_NAME = 'churchms-cache-v4'; // ⚠️ CHANGED VERSION TO FORCE UPDATE
const OFFLINE_URL = '/anglicankenya/offline.html';

// Files to cache for offline (REMOVED login.php and dashboard.php)
const FILES_TO_CACHE = [
  // ❌ REMOVED: '/anglicankenya/login.php' - Never cache login pages!
  // ❌ REMOVED: '/anglicankenya/dashboard.php' - Dynamic content
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
  console.log('[Service Worker] Installing v4...');
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
 console.log('[Service Worker] Activating v4...');
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME)
            .map(key => {
              console.log('[Service Worker] Deleting old cache:', key);
              return caches.delete(key);
            })
      );
    })
  );
  self.clients.claim();
});

/**
 * FETCH - Smart caching strategy
 * Network-first for dynamic pages, cache-first for static assets
 */
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
 const neverCache = [
    'login.php',
    'dashboard.php',
    'logout.php',
    'api/',
    'modules/',
    'callback',
    'process',
    'initiate_payment.php'
];
  
  const shouldNeverCache = neverCache.some(path => url.pathname.includes(path));
  
  // Never cache POST requests or dynamic pages
  if (event.request.method === 'POST' || shouldNeverCache) {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          // If offline and it's a navigation request, show offline page
          if (event.request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
          return new Response('Network error', { status: 503 });
        })
    );
    return;
  }
  
  // For navigation requests (HTML pages), use network-first
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }
  
  // For static assets (CSS, JS, images), use cache-first
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response; // Return cached version
        }
        // Not in cache, fetch from network
        return fetch(event.request).then(networkResponse => {
          // Cache the fetched resource for next time (if it's cacheable)
          if (networkResponse.ok && event.request.url.startsWith('http')) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        });
      })
      .catch(() => {
        // Both cache and network failed
        return new Response('Offline - Resource not available', { status: 503 });
      })
  );
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