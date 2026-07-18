{{-- بانر تثبيت التطبيق (PWA) + تسجيل الـ Service Worker --}}
<div id="pwa-banner" hidden
    class="fixed inset-x-0 z-40 px-3 bottom-[calc(4.5rem+env(safe-area-inset-bottom))]">
    <div class="mx-auto max-w-xl">
        <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-200 p-3 flex items-center gap-3">
            <img src="/icons/icon-192.png?v=8" alt="" class="w-11 h-11 rounded-xl shrink-0">
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

{{-- نافذة تثبيت التطبيق (أندرويد/كروم) --}}
<div id="pwa-modal" hidden class="fixed inset-0 z-50 grid place-items-center p-4 bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 text-center">
        <img src="/icons/icon-192.png?v=8" alt="" class="w-20 h-20 rounded-2xl mx-auto mb-3 shadow-md">
        <h3 class="text-lg font-extrabold mb-1">ثبّت تطبيق قطارات مصر</h3>
        <p class="text-sm text-slate-500 mb-5 leading-relaxed">وصول أسرع، يشتغل بدون نت، وتنبيهات بمواعيد قطارك — زي أي تطبيق على شاشتك.</p>
        <button id="pwa-modal-install" type="button"
            class="w-full bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-3.5 transition shadow-lg shadow-rail-600/25">
            تثبيت الآن
        </button>
        <button id="pwa-modal-later" type="button" class="w-full text-slate-500 font-bold rounded-2xl px-4 py-2.5 mt-2 hover:bg-slate-50 transition">
            مش دلوقتي
        </button>
    </div>
</div>

<div id="qm-toast" hidden
    class="fixed left-1/2 -translate-x-1/2 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] z-50 bg-slate-900 text-white text-sm rounded-full px-4 py-2 shadow-lg"></div>

{{-- لودر التنقّل بين الصفحات — يظهر أثناء تحميل الصفحة التالية --}}
<div id="qm-loader" hidden class="fixed inset-0 z-60 grid place-items-center bg-white/70 dark:bg-slate-900/70 backdrop-blur-sm">
    <div class="flex flex-col items-center gap-3">
        <span class="block w-11 h-11 rounded-full border-4 border-rail-200 border-t-rail-600 animate-spin"></span>
        <span class="text-sm font-bold text-rail-700">لحظة…</span>
    </div>
</div>

{{-- شريط «غير متصل» — يظهر تلقائيًا لما النت يقطع --}}
<div id="qm-offline" hidden
    class="fixed inset-x-0 top-0 z-60 bg-amber-500 text-white text-xs font-bold text-center px-3 py-1.5 pt-[max(0.375rem,env(safe-area-inset-top))] flex items-center justify-center gap-1.5">
    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.58 9M1.42 9a15.91 15.91 0 014.7-2.88M8.53 16.11a6 6 0 016.95 0M12 20h.01"/></svg>
    إنت غير متصل بالنت — بتشوف نسخة محفوظة
</div>

{{-- شريط «في تحديث جديد» — يظهر لما يبقا فيه نسخة أحدث من التطبيق --}}
<div id="pwa-update" hidden class="fixed inset-x-0 z-50 px-3 bottom-[calc(4.5rem+env(safe-area-inset-bottom))]">
    <div class="mx-auto max-w-xl">
        <div class="bg-rail-600 text-white rounded-2xl shadow-xl ring-1 ring-rail-700/40 p-3 flex items-center gap-3">
            <span class="w-9 h-9 grid place-items-center rounded-xl bg-white/15 shrink-0">
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36M21 3v5h-5"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm leading-tight">في تحديث جديد للتطبيق</p>
                <p class="text-xs text-white/80 mt-0.5">اضغط تحديث عشان تشوف آخر نسخة.</p>
            </div>
            <button id="pwa-update-btn" type="button" class="bg-white text-rail-700 text-sm font-extrabold rounded-xl px-4 py-2 active:scale-95 transition shrink-0">تحديث</button>
        </div>
    </div>
</div>

<script>
    // مشاركة (Web Share API) مع بديل نسخ الرابط
    (() => {
        const toast = document.getElementById('qm-toast');
        let t;
        const showToast = (msg) => {
            if (!toast) return;
            toast.textContent = msg; toast.hidden = false;
            clearTimeout(t); t = setTimeout(() => { toast.hidden = true; }, 2000);
        };
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-share]');
            if (!btn) return;
            const data = { title: btn.dataset.shareTitle || document.title, text: btn.dataset.shareText || '', url: btn.dataset.shareUrl || location.href };
            try {
                if (navigator.share) { await navigator.share(data); return; }
                await navigator.clipboard.writeText(data.url);
                showToast('اتنسخ الرابط ✓');
            } catch (err) { /* أُلغي أو فشل */ }
        });
    })();

    // إلغاء تنبيه (زر data-cancel-alert) — يعيد التحميل بعد النجاح
    (() => {
        const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
        document.addEventListener('click', async (e) => {
            const b = e.target.closest('[data-cancel-alert]');
            if (!b) return;
            b.disabled = true; b.textContent = '...';
            try {
                const res = await fetch(b.dataset.cancelAlert, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: '{}',
                });
                if (res.ok) location.reload(); else { b.disabled = false; b.textContent = 'إلغاء'; }
            } catch (err) { b.disabled = false; b.textContent = 'إلغاء'; }
        });
    })();

    // تبديل الوضع الليلي
    (() => {
        const btn = document.getElementById('theme-toggle');
        if (!btn) return;
        btn.addEventListener('click', () => {
            const dark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('qm:theme', dark ? 'dark' : 'light'); } catch (e) {}
            const meta = document.querySelector('meta[name=theme-color]');
            if (meta) meta.content = dark ? '#0b1220' : '#0b6340';
        });
    })();

    // زر الوسط → اكتب بوست في المجتمع (يركّز على المحرّر لو أنت في الفيد، وإلا يروح المجتمع).
    (() => {
        const fab = document.getElementById('voice-fab');
        if (!fab) return;
        fab.addEventListener('click', () => {
            const opener = document.querySelector('[data-open-composer]');
            if (opener) opener.click();
            else location.href = '{{ route('home') }}';
        });
    })();

    // لودر التنقّل: يظهر عند الضغط على أي رابط داخلي (أو إرسال فورم) أثناء تحميل الصفحة التالية
    (() => {
        const loader = document.getElementById('qm-loader');
        if (!loader) return;
        let timer, safety;
        const show = () => {
            timer = setTimeout(() => { loader.hidden = false; }, 150);
            // أمان: لو التنقّل اتلغى لأي سبب، نخفي اللودر بدل ما يفضل عالق.
            safety = setTimeout(() => { loader.hidden = true; }, 10000);
        };
        const hide = () => { clearTimeout(timer); clearTimeout(safety); loader.hidden = true; };

        const isInternalNav = (a) => {
            if (!a || a.target === '_blank' || a.hasAttribute('download')) return false;
            const href = a.getAttribute('href') || '';
            if (!href || href.startsWith('#') || /^(mailto:|tel:|javascript:)/i.test(href)) return false;
            let url; try { url = new URL(a.href, location.href); } catch (e) { return false; }
            if (url.origin !== location.origin) return false;
            // نفس الصفحة (مجرد hash)؟ مش تنقّل.
            if (url.pathname === location.pathname && url.search === location.search && url.hash) return false;
            return true;
        };

        document.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const a = e.target.closest('a[href]');
            if (a && isInternalNav(a)) show();
        });

        // الفورمات اللي بتنقّل الصفحة (زي البحث)
        document.addEventListener('submit', (e) => {
            const f = e.target;
            if (e.defaultPrevented || f.target === '_blank' || f.hasAttribute('data-no-loader')) return;
            show();
        });

        // إخفاء اللودر لو رجع المستخدم بالـ back (صفحة من كاش المتصفح)
        addEventListener('pageshow', hide);
        addEventListener('pagehide', () => clearTimeout(timer));
    })();

    // شريط «غير متصل»: يظهر/يختفي مع حالة الاتصال
    (() => {
        const bar = document.getElementById('qm-offline');
        if (!bar) return;
        const sync = () => { bar.hidden = navigator.onLine; };
        addEventListener('online', sync);
        addEventListener('offline', sync);
        sync();
    })();

    (() => {
        // 1) تسجيل الـ Service Worker + كشف التحديثات («في نسخة جديدة»)
        if ('serviceWorker' in navigator) {
            const bar = document.getElementById('pwa-update');
            const btn = document.getElementById('pwa-update-btn');
            let waitingSW = null;   // النسخة الجديدة المستنية التفعيل
            let updating = false;   // المستخدم ضغط «تحديث»؟ (عشان مانعملش reload تلقائي غير كده)

            const promptUpdate = (sw) => { waitingSW = sw; if (bar) bar.hidden = false; };

            // نعرض الشريط بس لو فيه SW شغّال بالفعل (يعني ده تحديث مش أول تثبيت).
            const checkWaiting = (reg) => {
                if (reg.waiting && navigator.serviceWorker.controller) promptUpdate(reg.waiting);
            };

            if (btn) btn.addEventListener('click', () => {
                btn.textContent = '…'; updating = true;
                if (waitingSW) waitingSW.postMessage({ type: 'SKIP_WAITING' });
                else location.reload();
            });

            // أول ما النسخة الجديدة تمسك التحكّم بعد الضغط → أعِد التحميل مرة واحدة.
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!updating) return; // مانعملش reload على أول تثبيت
                updating = false;
                location.reload();
            });

            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then((reg) => {
                    checkWaiting(reg);
                    reg.addEventListener('updatefound', () => {
                        const nw = reg.installing;
                        if (!nw) return;
                        nw.addEventListener('statechange', () => {
                            if (nw.state === 'installed' && navigator.serviceWorker.controller) promptUpdate(nw);
                        });
                    });
                    // نفحص وجود تحديث عند كل فتح للصفحة.
                    reg.update().catch(() => {});
                }).catch(() => {});
            });
        }

        // إشعارات الويب (تُفعَّل لما يكون مفتاح VAPID مضبوطًا)
        const vapid = document.querySelector('meta[name=vapid-key]')?.content;
        const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
        const b64ToU8 = (s) => {
            const pad = '='.repeat((4 - s.length % 4) % 4);
            const b = (s + pad).replace(/-/g, '+').replace(/_/g, '/');
            const raw = atob(b);
            return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
        };
        window.QMPush = {
            supported: () => 'serviceWorker' in navigator && 'PushManager' in window && !!vapid,
            async endpoint() {
                if (!('serviceWorker' in navigator)) return null;
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    return sub ? sub.endpoint : null;
                } catch (e) { return null; }
            },
            async subscribe(trainNumber) {
                if (!this.supported()) return false;
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') return false;
                const reg = await navigator.serviceWorker.ready;
                let sub = await reg.pushManager.getSubscription();
                if (!sub) sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToU8(vapid) });
                const j = sub.toJSON();
                await fetch('/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ endpoint: j.endpoint, keys: j.keys, train_number: trainNumber || null }),
                });
                return j.endpoint; // الـ endpoint (truthy) عند النجاح
            },
        };

        // 2) تثبيت التطبيق
        const DISMISS_KEY = 'pwa-banner-dismissed';
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

        // مرفوض خلال آخر 7 أيام؟
        const recentlyDismissed = () => {
            const ts = +(localStorage.getItem(DISMISS_KEY) || 0);
            return ts && (Date.now() - ts) < 7 * 24 * 60 * 60 * 1000;
        };
        const dismiss = () => { try { localStorage.setItem(DISMISS_KEY, String(Date.now())); } catch (e) {} };

        // — أندرويد/كروم: نافذة منبثقة للتثبيت —
        const modal = document.getElementById('pwa-modal');
        const modalInstall = document.getElementById('pwa-modal-install');
        const modalLater = document.getElementById('pwa-modal-later');
        let deferredPrompt = null;

        const hideModal = () => { if (modal) modal.hidden = true; };

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (isStandalone || recentlyDismissed() || !modal) return;
            setTimeout(() => { modal.hidden = false; }, 2500); // نظهرها بعد لحظة مش فجأة
        });

        if (modalInstall) modalInstall.addEventListener('click', async () => {
            hideModal();
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
        });
        if (modalLater) modalLater.addEventListener('click', () => { dismiss(); hideModal(); });
        if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) { dismiss(); hideModal(); } });

        window.addEventListener('appinstalled', hideModal);

        // — iOS: لا يوجد تثبيت برمجي — بانر تعليمات سفلي —
        const banner = document.getElementById('pwa-banner');
        if (isIOS && !isStandalone && !recentlyDismissed() && banner) {
            document.getElementById('pwa-install').hidden = true;
            document.getElementById('pwa-banner-text').innerHTML = 'اضغط زر المشاركة <span class="font-bold">⎙</span> ثم «أضف إلى الشاشة الرئيسية».';
            banner.hidden = false;
            document.getElementById('pwa-dismiss').addEventListener('click', () => { dismiss(); banner.hidden = true; });
        }
    })();
</script>
