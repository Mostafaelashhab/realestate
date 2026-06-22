// Service Worker — قطارات مصر PWA
const VERSION = 'v11';
const CACHE = `qm-${VERSION}`;
const OFFLINE_URL = '/';

// أصول أساسية تُخزّن مسبقًا (الأيقونات + المانيفست + الصفحة الرئيسية كاحتياطي).
const PRECACHE = [
    '/',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// إشعارات الويب
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) {}
    const title = data.title || 'قطارات مصر';
    event.waitUntil(self.registration.showNotification(title, {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        dir: 'rtl',
        lang: 'ar',
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(clients.matchAll({ type: 'window' }).then((wins) => {
        for (const w of wins) { if (w.url.includes(url) && 'focus' in w) return w.focus(); }
        return clients.openWindow(url);
    }));
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return; // لا نتدخّل في طلبات نظام الهيئة الخارجية

    // التنقّل بين الصفحات: الشبكة أولًا ثم الكاش ثم الصفحة الرئيسية كاحتياطي.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(request, copy)).catch(() => {});
                    return res;
                })
                .catch(() => caches.match(request).then((r) => r || caches.match(OFFLINE_URL)))
        );
        return;
    }

    // الأصول الثابتة (build/icons): الكاش أولًا مع تحديث في الخلفية.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const network = fetch(request).then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(request, copy)).catch(() => {});
                    return res;
                }).catch(() => cached);
                return cached || network;
            })
        );
    }
});
