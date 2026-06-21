{{-- بطاقة طلب أذونات الإشعارات والموقع (تُطلب بضغطة المستخدم — مش تلقائيًا) --}}
<section id="perm-card" hidden class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 mb-4">
    <div class="flex items-start justify-between gap-2 mb-1">
        <h3 class="font-bold text-sm">فعّل تجربة أفضل</h3>
        <button id="perm-dismiss" type="button" aria-label="إغلاق" class="w-7 h-7 grid place-items-center rounded-lg text-slate-400 hover:bg-slate-100 -mt-1 -me-1">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <p class="text-xs text-slate-500 mb-3">اسمح بالإشعارات عشان ننبّهك قبل ميعاد القطار وبالمقاعد المتاحة، وبالموقع عشان مشاركة رحلتك مع الأهل.</p>
    <div class="grid grid-cols-2 gap-2">
        @if (config('push.vapid_public'))
            <button id="perm-notif" type="button" class="flex items-center justify-center gap-1.5 text-sm font-bold rounded-2xl px-3 py-2.5 bg-rail-50 text-rail-700 hover:bg-rail-100 transition">
                <x-icon name="alert" class="w-4 h-4"/> <span>الإشعارات</span>
            </button>
        @endif
        <button id="perm-geo" type="button" class="flex items-center justify-center gap-1.5 text-sm font-bold rounded-2xl px-3 py-2.5 bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
            <x-icon name="pin" class="w-4 h-4"/> <span>الموقع</span>
        </button>
    </div>
</section>

<script>
    (() => {
        const card = document.getElementById('perm-card');
        if (!card) return;
        const DISMISS = 'qm:perm-dismissed';
        const notifBtn = document.getElementById('perm-notif');
        const geoBtn = document.getElementById('perm-geo');
        const dismissBtn = document.getElementById('perm-dismiss');

        const isStandalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

        async function permState(name) {
            try { return (await navigator.permissions.query({ name })).state; } catch (e) { return 'unknown'; }
        }
        const label = (btn, text, done) => {
            if (!btn) return;
            btn.querySelector('span').textContent = text;
            if (done) { btn.disabled = true; btn.classList.add('opacity-60'); }
        };

        // وسم الأزرار حسب الحالة الحالية، وإظهار البطاقة فقط لو فيه إذن لسه ماتقررش.
        (async () => {
            try { if (localStorage.getItem(DISMISS)) return; } catch (e) {}

            let show = false;

            // الإشعارات
            if (notifBtn) {
                const n = (typeof Notification !== 'undefined') ? Notification.permission : 'unsupported';
                if (n === 'granted') label(notifBtn, 'الإشعارات مفعّلة ✓', true);
                else if (n === 'denied') label(notifBtn, 'الإشعارات مرفوضة', true);
                else show = true;
            }

            // الموقع
            if (geoBtn) {
                if (!('geolocation' in navigator)) { label(geoBtn, 'الموقع غير مدعوم', true); }
                else {
                    const g = await permState('geolocation');
                    if (g === 'granted') label(geoBtn, 'الموقع مفعّل ✓', true);
                    else if (g === 'denied') label(geoBtn, 'الموقع مرفوض', true);
                    else show = true;
                }
            }

            if (show) card.hidden = false;
        })();

        if (notifBtn) notifBtn.addEventListener('click', async () => {
            const perm = await Notification.requestPermission();
            if (perm === 'granted') {
                if (window.QMPush) await window.QMPush.subscribe();
                label(notifBtn, 'الإشعارات مفعّلة ✓', true);
            } else if (perm === 'denied') {
                label(notifBtn, 'الإشعارات مرفوضة', true);
            }
        });

        if (geoBtn) geoBtn.addEventListener('click', () => {
            navigator.geolocation.getCurrentPosition(
                () => label(geoBtn, 'الموقع مفعّل ✓', true),
                (e) => { if (e.code === e.PERMISSION_DENIED) label(geoBtn, 'الموقع مرفوض', true); },
                { enableHighAccuracy: false, timeout: 10000 }
            );
        });

        dismissBtn.addEventListener('click', () => {
            card.hidden = true;
            try { localStorage.setItem(DISMISS, '1'); } catch (e) {}
        });
    })();
</script>
