@extends('layouts.app')

@section('title', 'المفضلة')

@section('content')
    <div class="flex items-center justify-between gap-2 mb-1">
        <div class="flex items-center gap-2">
            <x-icon name="star" class="w-6 h-6 text-amber-500"/>
            <h1 class="text-xl font-bold">المفضلة</h1>
        </div>
        <a href="{{ route('alerts.mine') }}" class="inline-flex items-center gap-1 text-sm text-rail-700 hover:underline">
            <x-icon name="alert" class="w-4 h-4"/> طلباتي
        </a>
    </div>
    <p class="text-sm text-slate-500 mb-4">قطاراتك المحفوظة وآخر عمليات بحثك (على هذا الجهاز).</p>

    <div id="fav-list" class="space-y-3 mb-5"></div>
    <div id="recent-list"></div>

    <div id="empty" hidden class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center text-slate-500">
        <x-icon name="star" class="w-10 h-10 mx-auto mb-2 text-slate-300"/>
        مفيش قطارات مفضلة لسه — دوس النجمة ⭐ في صفحة أي قطار عشان تحفظه.
    </div>

    <script>
        (() => {
            const get = (k) => { try { return JSON.parse(localStorage.getItem(k) || '[]'); } catch (e) { return []; } };
            const esc = (s) => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const fav = get('qm:fav'), recent = get('qm:recent');
            const favBox = document.getElementById('fav-list');
            const recentBox = document.getElementById('recent-list');

            if (fav.length) {
                favBox.innerHTML = fav.map(f => `
                    <a href="${esc(f.url)}" class="flex items-center gap-3 bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
                        <span class="w-10 h-10 grid place-items-center rounded-2xl bg-amber-50 text-amber-500 shrink-0">
                            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="currentColor"><path d="M12 2.5l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z"/></svg>
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-bold text-sm">قطار ${esc(f.number)}</span>
                            ${f.label ? `<span class="block text-xs text-slate-500 truncate">${esc(f.label)}</span>` : ''}
                        </span>
                        <svg viewBox="0 0 24 24" class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>`).join('');
            }

            if (recent.length) {
                const chips = recent.map(r =>
                    `<a href="/search?from=${encodeURIComponent(r.from)}&to=${encodeURIComponent(r.to)}&date=${encodeURIComponent(r.date)}"
                        class="inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 hover:ring-rail-300 rounded-full px-3 py-1.5 text-sm transition">
                        <span>${esc(r.fromName)}</span>
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6 7-7-7 7-7"/></svg>
                        <span>${esc(r.toName)}</span>
                    </a>`).join('');
                recentBox.innerHTML = `<h3 class="text-xs font-bold text-slate-500 mb-2">آخر عمليات البحث</h3><div class="flex flex-wrap gap-2">${chips}</div>`;
            }

            if (!fav.length && !recent.length) document.getElementById('empty').hidden = false;
        })();
    </script>
@endsection
