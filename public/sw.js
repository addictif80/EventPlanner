// EventPlanner service worker: offline shell for static assets, Web Push
// display, and a lightweight network-first strategy for HTML pages so the
// app still opens (from cache) when briefly offline. Bump CACHE_VERSION on
// any change to the precache list below.
const CACHE_VERSION = 'eventplanner-v1';
const PRECACHE = [
  '/assets/css/style.css',
  '/assets/js/notifications.js',
  '/assets/icons/icon.svg',
  '/assets/icons/icon-192.png',
  '/offline.html',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_VERSION).then(function (cache) {
      return cache.addAll(PRECACHE);
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (k) { return k !== CACHE_VERSION; }).map(function (k) { return caches.delete(k); }));
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Static assets: cache-first.
  if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/vendor/')) {
    event.respondWith(
      caches.match(request).then(function (cached) {
        return cached || fetch(request).then(function (response) {
          const copy = response.clone();
          caches.open(CACHE_VERSION).then(function (cache) { cache.put(request, copy); });
          return response;
        });
      })
    );
    return;
  }

  // Anything ending in .json (notification feeds, etc.): always network, never cached.
  if (url.pathname.endsWith('.json')) return;

  // HTML navigations: network-first, falling back to a generic offline page.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(function () {
        return caches.match('/offline.html');
      })
    );
  }
});

self.addEventListener('push', function (event) {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) { /* ignore malformed payloads */ }

  const title = data.title || 'EventPlanner';
  const options = {
    body: data.body || '',
    icon: '/assets/icons/icon-192.png',
    badge: '/assets/icons/icon-192.png',
    data: { link: data.link || '/' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const link = (event.notification.data && event.notification.data.link) || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientsList) {
      for (const client of clientsList) {
        if (client.url.includes(link) && 'focus' in client) return client.focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow(link);
    })
  );
});
