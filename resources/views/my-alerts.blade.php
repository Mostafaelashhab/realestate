@extends('layouts.app')

@section('title', 'طلباتي')

@section('content')
    <div class="flex items-center gap-2 mb-1">
        <x-icon name="alert" class="w-6 h-6 text-rail-700"/>
        <h1 class="text-xl font-bold">طلباتي</h1>
    </div>
    <p class="text-sm text-slate-500 mb-4">التنبيهات اللي فعّلتها على هذا الجهاز.</p>

    <div id="alerts-loading" class="text-sm text-slate-400">جاري التحميل…</div>
    <div id="alerts-wrap" class="space-y-6"></div>

    <x-empty id="alerts-empty" icon="alert" hidden>
        مفيش طلبات لسه. من صفحة أي قطار فعّل <b>«نبّهني قبل ميعاد القطار»</b> أو <b>«تنبيه المقاعد»</b>.
    </x-empty>

    <script>
        (() => {
            const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
            const loading = document.getElementById('alerts-loading');
            const wrap = document.getElementById('alerts-wrap');
            const empty = document.getElementById('alerts-empty');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const ARROW = '<svg viewBox="0 0 24 24" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6 7-7-7 7-7"/></svg>';

            const post = (url, body) => fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });

            function card(inner, id, cancelUrl, endpoint) {
                return `<div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4" data-card="${id}">
                    ${inner}
                    <button data-url="${cancelUrl}" class="cancel-btn text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl px-4 py-2 mt-3 transition">إلغاء</button>
                </div>`;
            }

            function render(data, endpoint) {
                const reminders = data.reminders || [];
                const seats = data.seatAlerts || [];
                if (!reminders.length && !seats.length) { empty.hidden = false; return; }
                let html = '';

                if (reminders.length) {
                    html += `<div><h2 class="text-xs font-bold text-slate-500 mb-2">تنبيهات المواعيد</h2><div class="space-y-3">` +
                        reminders.map(r => card(`
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <a href="/trains/${r.train_id}?from=${r.from_id}" class="bg-rail-50 text-rail-700 text-xs font-bold px-2.5 py-1 rounded-full">قطار ${esc(r.train)}</a>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rail-50 text-rail-700">قبل الميعاد بـ ${esc(r.lead)} د</span>
                            </div>
                            <p class="text-sm text-slate-700">القيام من <b>${esc(r.from)}</b> الساعة ${esc(r.time)}</p>`,
                            r.id, `/reminders/${r.id}/cancel`)).join('') + `</div></div>`;
                }

                if (seats.length) {
                    html += `<div><h2 class="text-xs font-bold text-slate-500 mb-2">تنبيهات المقاعد (للواقفين)</h2><div class="space-y-3">` +
                        seats.map(a => card(`
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <a href="/trains/${a.train_id}?from=${a.from_id}&to=${a.to_id}" class="bg-rail-50 text-rail-700 text-xs font-bold px-2.5 py-1 rounded-full">قطار ${esc(a.train)}</a>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">${esc(a.status_label)}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-700 mb-1"><span class="font-medium">${esc(a.from)}</span>${ARROW}<span class="font-medium">${esc(a.to)}</span></div>
                            <p class="text-xs text-slate-400">${esc(a.when)}</p>`,
                            a.id, `/my-alerts/${a.id}/cancel`)).join('') + `</div></div>`;
                }

                wrap.innerHTML = html;
                wrap.querySelectorAll('.cancel-btn').forEach(btn => btn.addEventListener('click', async () => {
                    btn.disabled = true; btn.textContent = '...';
                    try {
                        await post(btn.dataset.url, { endpoint });
                        btn.closest('[data-card]').remove();
                        if (!wrap.querySelector('[data-card]')) empty.hidden = false;
                    } catch (e) { btn.disabled = false; btn.textContent = 'إلغاء'; }
                }));
            }

            (async () => {
                const endpoint = window.QMPush ? await window.QMPush.endpoint() : null;
                loading.hidden = true;
                if (!endpoint) { empty.hidden = false; return; }
                try {
                    const data = await (await post('{{ route('alerts.list') }}', { endpoint })).json();
                    render(data, endpoint);
                } catch (e) { empty.hidden = false; }
            })();
        })();
    </script>
@endsection
