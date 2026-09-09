const CACHE_NAME = 'sneat-store-v1';
const STATIC_ASSETS = [
  '/',
  '/assets/vendor/css/core.css',
  '/assets/vendor/css/theme-default.css',
  '/assets/css/demo.css',
  '/assets/vendor/libs/jquery/jquery.js',
  '/assets/vendor/js/bootstrap.js',
  '/manifest.json'
];

// Install Event - cache core static shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      // Best effort caching - don't fail installation if some sub-resources are missing
      return Promise.allSettled(
        STATIC_ASSETS.map(url => cache.add(url).catch(err => console.warn('[SW] Pre-cache skipped:', url)))
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate Event - clean up stale caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event - Network-first for HTML / dynamic, Stale-while-revalidate for static
self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Don't intercept non-GET or admin/livewire/POST requests
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Skip admin, api mutating calls, or livewire polling
  if (url.pathname.includes('/admin') || url.pathname.includes('/api/')) {
    return;
  }

  // Static assets (css, js, images, fonts): Cache-first with network fallback
  if (req.destination === 'style' || req.destination === 'script' || req.destination === 'image' || req.destination === 'font') {
    event.respondWith(
      caches.match(req).then((cached) => {
        if (cached) return cached;
        return fetch(req).then((networkRes) => {
          if (networkRes && networkRes.status === 200) {
            const clone = networkRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
          }
          return networkRes;
        }).catch(() => cached);
      })
    );
    return;
  }

  // HTML page navigation: Network-first with cache fallback
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => {
        return caches.match(req).then((cached) => {
          if (cached) return cached;
          return caches.match('/');
        });
      })
    );
  }
});
