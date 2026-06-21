@extends('layouts.app')

@section('title', 'طلباتي')

@section('content')
    <div class="flex items-center gap-2 mb-1">
        <x-icon name="alert" class="w-6 h-6 text-rail-700"/>
        <h1 class="text-xl font-bold">طلباتي</h1>
    </div>
    <p class="text-sm text-slate-500 mb-4">تنبيهات المقاعد اللي فعّلتها على هذا الجهاز.</p>

    <div id="alerts-loading" class="text-sm text-slate-400">جاري التحميل…</div>
    <div id="alerts-list" class="space-y-3"></div>

    <x-empty id="alerts-empty" icon="alert" hidden>
        مفيش طلبات لسه. افتح صفحة أي قطار وفعّل <b>تنبيه المقاعد</b> لو هتسافر واقف.
    </x-empty>

    <script>
        (() => {
            const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
            const loading = document.getElementById('alerts-loading');
            const list = document.getElementById('alerts-list');
            const empty = document.getElementById('alerts-empty');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            const post = (url, body) => fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });

            function render(alerts, endpoint) {
                if (!alerts.length) { empty.hidden = false; return; }
                list.innerHTML = alerts.map(a => `
                    <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4" data-id="${a.id}">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <a href="/trains/${a.train_id}?from=${a.from_id}&to=${a.to_id}" class="flex items-center gap-2">
                                <span class="bg-rail-50 text-rail-700 text-xs font-bold px-2.5 py-1 rounded-full">قطار ${esc(a.train)}</span>
                            </a>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full ${a.status === 'notified' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${esc(a.status_label)}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-700 mb-1">
                            <span class="font-medium">${esc(a.from)}</span>
                            <svg viewBox="0 0 24 24" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6 7-7-7 7-7"/></svg>
                            <span class="font-medium">${esc(a.to)}</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-3">${esc(a.when)}</p>
                        <button data-cancel="${a.id}" class="text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl px-4 py-2 transition">إلغاء التنبيه</button>
                    </div>`).join('');

                list.querySelectorAll('[data-cancel]').forEach(btn => btn.addEventListener('click', async () => {
                    btn.disabled = true; btn.textContent = '...';
                    try {
                        await post(`/my-alerts/${btn.dataset.cancel}/cancel`, { endpoint });
                        const card = btn.closest('[data-id]');
                        card.remove();
                        if (!list.children.length) empty.hidden = false;
                    } catch (e) { btn.disabled = false; btn.textContent = 'إلغاء التنبيه'; }
                }));
            }

            (async () => {
                const endpoint = window.QMPush ? await window.QMPush.endpoint() : null;
                loading.hidden = true;
                if (!endpoint) { empty.hidden = false; return; }
                try {
                    const { alerts } = await (await post('{{ route('alerts.list') }}', { endpoint })).json();
                    render(alerts || [], endpoint);
                } catch (e) { empty.hidden = false; }
            })();
        })();
    </script>
@endsection
