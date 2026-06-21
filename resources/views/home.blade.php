@extends('layouts.app')

@section('title', 'مواعيد وأسعار القطارات')
@section('og_desc', 'مواعيد وأسعار قطارات مصر، والمحطات، والمقاعد المتاحة — في تطبيق واحد سريع.')

@push('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'قطارات مصر',
        'url' => url('/'),
        'inLanguage' => 'ar-EG',
        'description' => 'مواعيد وأسعار قطارات مصر والمقاعد المتاحة.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    {{-- العروض/البانرات --}}
    @if ($promos->isNotEmpty())
        @php
            $promoStyles = [
                'rail' => 'bg-rail-50 text-rail-900 ring-rail-200',
                'amber' => 'bg-amber-50 text-amber-900 ring-amber-200',
                'sky' => 'bg-sky-50 text-sky-900 ring-sky-200',
            ];
        @endphp
        <div class="space-y-2 mb-4">
            @foreach ($promos as $promo)
                <div class="promo-banner rounded-2xl ring-1 p-3 flex items-center gap-3 {{ $promoStyles[$promo->variant] ?? $promoStyles['rail'] }}"
                    data-promo="{{ $promo->id }}" hidden>
                    @if ($promo->url)
                        <a href="{{ $promo->url }}" target="_blank" rel="noopener" class="flex-1 min-w-0">
                            <p class="font-bold text-sm">{{ $promo->title }}</p>
                            @if ($promo->body)<p class="text-xs mt-0.5 opacity-80">{{ $promo->body }}</p>@endif
                        </a>
                    @else
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm">{{ $promo->title }}</p>
                            @if ($promo->body)<p class="text-xs mt-0.5 opacity-80">{{ $promo->body }}</p>@endif
                        </div>
                    @endif
                    <button type="button" class="promo-dismiss w-7 h-7 grid place-items-center rounded-lg hover:bg-black/5 shrink-0" aria-label="إغلاق">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>
            @endforeach
        </div>
        <script>
            (() => {
                const KEY = 'qm:promo-dismissed';
                let dismissed = [];
                try { dismissed = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) {}
                document.querySelectorAll('.promo-banner').forEach(el => {
                    const id = el.dataset.promo;
                    if (dismissed.includes(id)) return;
                    el.hidden = false;
                    el.querySelector('.promo-dismiss').addEventListener('click', () => {
                        el.hidden = true;
                        dismissed.push(id);
                        try { localStorage.setItem(KEY, JSON.stringify(dismissed.slice(-50))); } catch (e) {}
                    });
                });
            })();
        </script>
    @endif

    {{-- ترحيب --}}
    <section class="relative overflow-hidden bg-linear-to-br from-rail-800 via-rail-700 to-rail-600 text-white rounded-3xl p-6 mb-5 shadow-xl shadow-rail-800/25">
        {{-- زخرفة قضبان خفيفة --}}
        <svg class="absolute -top-6 -start-10 w-48 h-48 text-white/10" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100"/>
            <path d="M0 25h100M0 50h100M0 75h100" stroke-dasharray="6 8"/>
        </svg>
        <div class="relative">
            <h1 class="text-3xl font-extrabold mb-1.5 tracking-tight">رايح فين؟</h1>
            <p class="text-rail-50/90 text-sm leading-relaxed">مواعيد وأسعار قطارات مصر، والمقاعد المتاحة — في مكان واحد.</p>
            <div class="mt-4 inline-flex items-center gap-1.5 bg-white/15 ring-1 ring-white/15 rounded-full px-3 py-1.5 text-xs font-bold backdrop-blur">
                <x-icon name="train" class="w-4 h-4"/> {{ number_format($trainCount) }} قطار على الشبكة
            </div>
        </div>
    </section>

    @error('number')
        <div class="flex items-center gap-2 bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-4">
            <x-icon name="alert" class="w-5 h-5 shrink-0"/> {{ $message }}
        </div>
    @enderror

    {{-- بحث المحطتين --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4">
        <form id="search-form" action="{{ route('search') }}" method="GET" class="space-y-4">
            {{-- مجموعة المحطتين المتصلة + زر التبديل --}}
            <div class="relative rounded-2xl border border-slate-200 bg-slate-50">
                {{-- خط واصل بين النقطة والدبوس --}}
                <span class="absolute start-[1.45rem] top-[2.85rem] h-[calc(100%-5.7rem)] w-px bg-slate-300" aria-hidden="true"></span>

                <x-station-select name="from" placeholder="محطة القيام" icon="dot" icon-class="text-rail-600" round="rounded-t-2xl"/>

                <div class="mx-4 border-t border-slate-200"></div>

                <x-station-select name="to" placeholder="محطة الوصول" icon="pin" icon-class="text-amber-500" round="rounded-b-2xl"/>

                {{-- زر تبديل المحطتين --}}
                <button type="button" id="swap-btn" aria-label="عكس المحطتين"
                    class="absolute end-3 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-full bg-white text-rail-600 ring-1 ring-slate-200 shadow-sm hover:bg-rail-50 active:scale-90 transition">
                    <x-icon name="swap" class="w-4 h-4"/>
                </button>
            </div>

            <p id="search-error" hidden class="text-sm text-red-600 flex items-center gap-1.5">
                <x-icon name="alert" class="w-4 h-4 shrink-0"/> اختار محطة القيام والوصول الأول.
            </p>

            {{-- التاريخ --}}
            <div class="relative">
                <x-icon name="calendar" class="absolute top-1/2 -translate-y-1/2 start-4 w-5 h-5 text-slate-400 pointer-events-none"/>
                <input type="date" name="date" value="{{ now()->toDateString() }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-12 pe-3 py-3.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30 focus:border-rail-500 focus:bg-white transition">
            </div>

            <button type="submit"
                class="w-full flex items-center justify-center gap-2 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-4 transition shadow-lg shadow-rail-600/25">
                <x-icon name="search" class="w-5 h-5"/>
                ابحث عن القطارات
            </button>
        </form>

        <script>
            (() => {
                const STATIONS = @json($stations->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar])->values());
                // تطبيع عربي بسيط لبحث أفضل (همزات/تاء مربوطة/ألف مقصورة/تشكيل).
                const norm = (s) => s.replace(/[إأآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه')
                    .replace(/[ً-ٰٟ]/g, '').trim();

                function initSelect(root) {
                    const hidden = root.querySelector('input[type=hidden]');
                    const trigger = root.querySelector('[data-trigger]');
                    const panel = root.querySelector('[data-panel]');
                    const search = root.querySelector('[data-search]');
                    const list = root.querySelector('[data-list]');
                    const label = root.querySelector('[data-label]');
                    const empty = root.querySelector('[data-empty]');

                    function render(q = '') {
                        const nq = norm(q);
                        const items = STATIONS.filter(s => !nq || norm(s.name).includes(nq));
                        list.innerHTML = items.slice(0, 60).map(s =>
                            `<li><button type="button" data-id="${s.id}" data-name="${s.name}"
                                class="w-full text-start px-4 py-2.5 text-sm hover:bg-rail-50 ${String(s.id) === hidden.value ? 'bg-rail-50 text-rail-700 font-bold' : ''}">${s.name}</button></li>`
                        ).join('');
                        empty.hidden = items.length > 0;
                    }
                    function open() { panel.hidden = false; render(search.value); search.focus(); }
                    function close() { panel.hidden = true; }
                    function pick(id, nm) {
                        hidden.value = id;
                        label.textContent = nm;
                        label.classList.remove('text-slate-400');
                        label.classList.add('text-slate-800', 'font-medium');
                        close();
                    }

                    trigger.addEventListener('click', () => panel.hidden ? open() : close());
                    search.addEventListener('input', () => render(search.value));
                    list.addEventListener('click', (e) => {
                        const b = e.target.closest('[data-id]');
                        if (b) pick(b.dataset.id, b.dataset.name);
                    });
                    search.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') { close(); trigger.focus(); }
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const b = list.querySelector('[data-id]');
                            if (b) pick(b.dataset.id, b.dataset.name);
                        }
                    });
                    document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(); });

                    root._pick = pick; // للاستخدام في التبديل
                    render();
                }

                const selects = [...document.querySelectorAll('[data-station-select]')];
                selects.forEach(initSelect);

                // تبديل المحطتين (القيمة + النص الظاهر).
                document.getElementById('swap-btn').addEventListener('click', () => {
                    const [a, b] = selects;
                    const ah = a.querySelector('input[type=hidden]'), bh = b.querySelector('input[type=hidden]');
                    const al = a.querySelector('[data-label]'), bl = b.querySelector('[data-label]');
                    const av = ah.value, bv = bh.value;
                    const at = av ? al.textContent : '', bt = bv ? bl.textContent : '';
                    av ? b._pick(av, at) : resetSelect(b);
                    bv ? a._pick(bv, bt) : resetSelect(a);
                });

                function resetSelect(root) {
                    const hidden = root.querySelector('input[type=hidden]');
                    const label = root.querySelector('[data-label]');
                    hidden.value = '';
                    label.textContent = root.dataset.name === 'from' ? 'محطة القيام' : 'محطة الوصول';
                    label.classList.add('text-slate-400');
                    label.classList.remove('text-slate-800', 'font-medium');
                }

                // منع الإرسال لو محطة ناقصة.
                document.getElementById('search-form').addEventListener('submit', (e) => {
                    const ok = document.getElementById('from').value && document.getElementById('to').value;
                    document.getElementById('search-error').hidden = ok;
                    if (!ok) e.preventDefault();
                });
            })();
        </script>

        <div class="flex items-center gap-3 my-4 text-xs text-slate-400">
            <span class="flex-1 border-t border-slate-100"></span>
            أو ابحث برقم القطار
            <span class="flex-1 border-t border-slate-100"></span>
        </div>

        <form action="{{ route('search') }}" method="GET" class="flex gap-2">
            <input type="text" name="number" inputmode="numeric" placeholder="رقم القطار (مثال: 936)" required
                class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-rail-500/30 focus:border-rail-500 focus:bg-white transition">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 active:scale-95 text-white font-bold rounded-2xl px-6 transition whitespace-nowrap">
                اعرض
            </button>
        </form>
    </section>

    {{-- وجهات شائعة --}}
    @if ($popular->isNotEmpty())
        <section class="mb-4">
            <h3 class="text-xs font-bold text-slate-500 mb-2">وجهات شائعة</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($popular as $p)
                    <a href="{{ route('route', ['from' => $p['from']->slug, 'to' => $p['to']->slug]) }}"
                        class="inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 hover:ring-rail-300 rounded-full ps-3 pe-2 py-1.5 text-sm transition">
                        <span>{{ $p['from']->name_ar }}</span>
                        <x-icon name="arrow-left" class="w-3.5 h-3.5 text-slate-400"/>
                        <span>{{ $p['to']->name_ar }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- المفضلة + آخر بحث (من التخزين المحلي) --}}
    <section id="qm-quick" hidden class="mb-4 space-y-4"></section>

    <script>
        (() => {
            const get = (k) => { try { return JSON.parse(localStorage.getItem(k) || '[]'); } catch (e) { return []; } };
            const esc = (s) => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const box = document.getElementById('qm-quick');
            const fav = get('qm:fav'), recent = get('qm:recent');
            let html = '';

            const chip = (href, inner) =>
                `<a href="${href}" class="inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 hover:ring-rail-300 rounded-full ps-3 pe-2 py-1.5 text-sm transition">${inner}</a>`;
            const card = (title, icon, chips) =>
                `<div><h3 class="text-xs font-bold text-slate-500 mb-2 flex items-center gap-1.5">${icon} ${title}</h3><div class="flex flex-wrap gap-2">${chips}</div></div>`;

            const STAR = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-amber-500" fill="currentColor"><path d="M12 2.5l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z"/></svg>';
            const HIST = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l3 2"/></svg>';
            const ARROW = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6 7-7-7 7-7"/></svg>';

            if (fav.length) {
                const chips = fav.map(f =>
                    chip(esc(f.url), `${STAR}<span class="font-bold">قطار ${esc(f.number)}</span>${f.label ? `<span class="text-xs text-slate-400">${esc(f.label)}</span>` : ''}`)
                ).join('');
                html += card('قطاراتك المفضلة', STAR, chips);
            }

            if (recent.length) {
                const chips = recent.map(r =>
                    chip(`/search?from=${encodeURIComponent(r.from)}&to=${encodeURIComponent(r.to)}&date=${encodeURIComponent(r.date)}`,
                        `<span>${esc(r.fromName)}</span>${ARROW}<span>${esc(r.toName)}</span>`)
                ).join('');
                html += card('آخر عمليات البحث', HIST, chips);
            }

            if (html) { box.innerHTML = html; box.hidden = false; }
        })();
    </script>

    {{-- اختصارات --}}
    <section class="grid grid-cols-2 gap-3">
        <a href="{{ route('fines') }}" class="group bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 hover:ring-amber-200 active:scale-95 transition">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-amber-50 text-amber-600 mb-3 group-hover:bg-amber-100 transition">
                <x-icon name="scale" class="w-6 h-6"/>
            </div>
            <h3 class="font-bold text-sm flex items-center gap-1">الغرامات <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-amber-500 transition"/></h3>
            <p class="text-xs text-slate-500 mt-0.5">المخالفات وقيمة كل غرامة</p>
        </a>
        <a href="{{ route('report') }}" class="group bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 hover:ring-rail-200 active:scale-95 transition">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-rail-50 text-rail-600 mb-3 group-hover:bg-rail-100 transition">
                <x-icon name="flag" class="w-6 h-6"/>
            </div>
            <h3 class="font-bold text-sm flex items-center gap-1">بلّغ عن خطأ <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-rail-500 transition"/></h3>
            <p class="text-xs text-slate-500 mt-0.5">ميعاد أو سعر غلط أو مشكلة</p>
        </a>
    </section>
@endsection
