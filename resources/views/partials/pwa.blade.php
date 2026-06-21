{{-- بانر تثبيت التطبيق (PWA) + تسجيل الـ Service Worker --}}
<div id="pwa-banner" hidden
    class="fixed inset-x-0 z-40 px-3 bottom-[calc(4.5rem+env(safe-area-inset-bottom))]">
    <div class="mx-auto max-w-xl">
        <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-200 p-3 flex items-center gap-3">
            <img src="/icons/icon-192.png" alt="" class="w-11 h-11 rounded-xl shrink-0">
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm leading-tight">ثبّت تطبيق قطارات مصر</p>
                <p id="pwa-banner-text" class="text-xs text-slate-500 mt-0.5">وصول أسرع وبدون متصفّح، زي أي تطبيق.</p>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button id="pwa-install" type="button"
                    class="bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold rounded-xl px-4 py-2 transition">
                    تثبيت
                </button>
                <button id="pwa-dismiss" type="button" aria-label="إغلاق"
                    class="w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:bg-slate-100 transition">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        // 1) تسجيل الـ Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        // 2) بانر التثبيت
        const banner = document.getElementById('pwa-banner');
        const installBtn = document.getElementById('pwa-install');
        const dismissBtn = document.getElementById('pwa-dismiss');
        const bannerText = document.getElementById('pwa-banner-text');
        const DISMISS_KEY = 'pwa-banner-dismissed';

        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

        // مخفي لو مثبّت بالفعل أو رفضه المستخدم خلال آخر 7 أيام
        function recentlyDismissed() {
            const ts = +(localStorage.getItem(DISMISS_KEY) || 0);
            return ts && (Date.now() - ts) < 7 * 24 * 60 * 60 * 1000;
        }

        function show() {
            if (isStandalone || recentlyDismissed()) return;
            banner.hidden = false;
        }
        function hide() { banner.hidden = true; }

        dismissBtn.addEventListener('click', () => {
            try { localStorage.setItem(DISMISS_KEY, String(Date.now())); } catch (e) {}
            hide();
        });

        let deferredPrompt = null;

        // أندرويد/كروم: نلتقط حدث التثبيت ونعرض زر "تثبيت"
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installBtn.hidden = false;
            show();
        });

        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            hide();
        });

        window.addEventListener('appinstalled', hide);

        // iOS: لا يوجد تثبيت برمجي — نعرض تعليمات
        if (isIOS && !isStandalone) {
            installBtn.hidden = true;
            bannerText.innerHTML = 'اضغط زر المشاركة <span class="font-bold">⎙</span> ثم «أضف إلى الشاشة الرئيسية».';
            show();
        }
    })();
</script>
