{{-- بطاقة طلب إذن الإشعارات (تُطلب بضغطة المستخدم — مش تلقائيًا) --}}
@if (config('push.vapid_public'))
    <section id="perm-card" hidden class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 mb-4">
        <div class="flex items-start justify-between gap-2 mb-1">
            <h3 class="font-bold text-sm">فعّل التنبيهات</h3>
            <button id="perm-dismiss" type="button" aria-label="إغلاق" class="w-7 h-7 grid place-items-center rounded-lg text-slate-400 hover:bg-slate-100 -mt-1 -me-1">
                <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <p class="text-xs text-slate-500 mb-3">اسمح بالإشعارات عشان ننبّهك قبل ميعاد القطار وبالمقاعد المتاحة قبل القيام.</p>
        <button id="perm-notif" type="button" class="w-full flex items-center justify-center gap-1.5 text-sm font-bold rounded-2xl px-3 py-2.5 bg-rail-50 text-rail-700 hover:bg-rail-100 transition">
            <x-icon name="alert" class="w-4 h-4"/> <span>تفعيل الإشعارات</span>
        </button>
    </section>

    <script>
        (() => {
            const card = document.getElementById('perm-card');
            if (!card) return;
            const DISMISS = 'qm:perm-dismissed';
            const notifBtn = document.getElementById('perm-notif');
            const dismissBtn = document.getElementById('perm-dismiss');
            const label = (text, done) => {
                notifBtn.querySelector('span').textContent = text;
                if (done) { notifBtn.disabled = true; notifBtn.classList.add('opacity-60'); }
            };

            try { if (localStorage.getItem(DISMISS)) return; } catch (e) {}

            const n = (typeof Notification !== 'undefined') ? Notification.permission : 'unsupported';
            if (n === 'unsupported') return;
            if (n === 'granted') { label('الإشعارات مفعّلة ✓', true); return; } // مفعّلة → مفيش داعي للبطاقة
            if (n === 'denied') return;
            card.hidden = false; // 'default' فقط

            notifBtn.addEventListener('click', async () => {
                const perm = await Notification.requestPermission();
                if (perm === 'granted') {
                    if (window.QMPush) await window.QMPush.subscribe();
                    label('الإشعارات مفعّلة ✓', true);
                } else if (perm === 'denied') {
                    label('الإشعارات مرفوضة', true);
                }
            });
            dismissBtn.addEventListener('click', () => {
                card.hidden = true;
                try { localStorage.setItem(DISMISS, '1'); } catch (e) {}
            });
        })();
    </script>
@endif
