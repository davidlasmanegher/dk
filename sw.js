/* Service Worker — Daniel Khan PWA
 * Cache-first para assets estáticos, network-first (con fallback offline) para el resto.
 */
const CACHE = 'dk-v1';
const ASSETS = [
  'assets/app.css',
  'assets/app.js',
  'assets/icon-192.png',
  'assets/icon-512.png'
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).catch(() => {}));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  // No cachear endpoints dinámicos (API) ni POSTs: siempre red.
  if (url.pathname.includes('/api/')) return;
  // Assets estáticos: cache-first.
  if (/\.(?:css|js|png|jpg|jpeg|svg|webp|woff2?|ico)$/.test(url.pathname)) {
    e.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
        return res;
      }).catch(() => hit))
    );
    return;
  }
  // Navegación/HTML: network-first con fallback a cache.
  e.respondWith(
    fetch(req).then((res) => {
      const copy = res.clone();
      caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
      return res;
    }).catch(() => caches.match(req))
  );
});
