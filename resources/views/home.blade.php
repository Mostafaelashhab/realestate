@extends('layouts.app')

@section('title', 'مواعيد وأسعار القطارات')
@section('og_desc', 'مواعيد وأسعار قطارات مصر، والمحطات، والمقاعد المتاحة — في تطبيق واحد سريع.')
@section('dark', '1')
@section('hideHeader', '1')

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
    @php
        $stationsJs = $stations->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar, 'lat' => $s->lat, 'lng' => $s->lng])->values();
    @endphp

    @if (session('voice_error'))
        <div class="flex items-center gap-2 bg-amber-50 text-amber-800 text-sm rounded-2xl px-4 py-3 mb-4">
            <x-icon name="alert" class="w-5 h-5 shrink-0"/> {{ session('voice_error') }}
        </div>
    @endif

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

    {{-- رسمة قطار زخرفية أعلى اليسار --}}
    <div aria-hidden="true" class="pointer-events-none absolute start-0 top-0 w-52 -translate-x-6 opacity-25 text-white">
        <x-train-illustration class="w-full"/>
    </div>

    {{-- ترحيب + جرس --}}
    <div class="relative flex items-start justify-between gap-3 pt-[env(safe-area-inset-top)] mb-4">
        <div>
            <p class="text-rail-200/80 text-sm font-medium">مرحبًا</p>
            <h1 class="text-2xl font-extrabold text-white mt-0.5">وين رحلتك الجاية؟</h1>
        </div>
        <a href="{{ route('premium') }}" aria-label="التنبيهات"
            class="relative w-11 h-11 grid place-items-center rounded-2xl bg-white/10 ring-1 ring-white/10 text-white shrink-0 active:scale-95 transition">
            <x-icon name="bell" class="w-5 h-5"/>
            <span class="absolute top-2.5 end-2.5 w-2 h-2 rounded-full bg-rail-400 ring-2 ring-rail-950"></span>
        </a>
    </div>

    {{-- كارت رحلتك القادمة (افتراضي = أشهر مسار، ويتحدّث لآخر قطر شُوهد) --}}
    @php $sug = $popular->first(); @endphp
    <section class="relative overflow-hidden rounded-3xl p-5 mb-5 bg-linear-to-br from-rail-800 via-rail-800 to-rail-900 ring-1 ring-white/10 shadow-xl shadow-black/30">
        <div class="pointer-events-none absolute -top-10 -start-8 w-40 h-40 rounded-full bg-rail-400/15 blur-2xl"></div>
        <div class="relative text-white">
            <div class="flex items-center justify-between gap-2 mb-5">
                <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-white/10 ring-1 ring-white/15 rounded-full px-3 py-1"><span class="w-1.5 h-1.5 rounded-full bg-rail-300"></span> رحلتك القادمة</span>
                <span id="trip-num" class="text-xs font-bold bg-white/10 ring-1 ring-white/15 rounded-full px-3 py-1" hidden></span>
                <svg viewBox="0 0 24 24" class="w-5 h-5 text-amber-300 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z"/></svg>
            </div>

            <div class="flex items-stretch gap-3">
                <div class="flex-1 min-w-0 text-center">
                    <div id="trip-from" class="text-2xl font-extrabold truncate">{{ $sug ? $sug['from']->name_ar : 'القاهرة' }}</div>
                    <div id="trip-ftime" class="text-sm font-bold text-rail-300 mt-1" hidden></div>
                    <div id="trip-fdate" class="text-[11px] text-rail-100/60 mt-0.5">قيام</div>
                </div>

                <div class="flex flex-col items-center justify-center w-24 shrink-0">
                    <div class="w-full flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-rail-300 shrink-0"></span>
                        <span class="flex-1 border-t-2 border-dashed border-white/25"></span>
                        <span class="relative grid place-items-center w-10 h-10 rounded-full bg-rail-600 ring-4 ring-rail-500/30 shadow-lg shadow-rail-500/40 shrink-0">
                            <svg viewBox="0 0 24 24" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8M7 16l-2 4m12-4l2 4"/></svg>
                        </span>
                        <span class="flex-1 border-t-2 border-dashed border-white/25"></span>
                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                    </div>
                    <div id="trip-dur" class="text-[11px] text-rail-100/70 mt-2 whitespace-nowrap">المواعيد بالكامل</div>
                </div>

                <div class="flex-1 min-w-0 text-center">
                    <div id="trip-to" class="text-2xl font-extrabold truncate">{{ $sug ? $sug['to']->name_ar : 'طنطا' }}</div>
                    <div id="trip-ttime" class="text-sm font-bold text-rail-300 mt-1" hidden></div>
                    <div id="trip-tdate" class="text-[11px] text-rail-100/60 mt-0.5">وصول</div>
                </div>
            </div>

            <a id="trip-link" href="{{ $sug ? route('route', ['from' => $sug['from']->slug, 'to' => $sug['to']->slug]) : route('home') }}"
                class="mt-5 flex items-center justify-center gap-1 bg-white/10 hover:bg-white/15 rounded-2xl py-3 text-sm font-bold transition">
                عرض التفاصيل
                <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        </div>
    </section>

    <script>
        (() => {
            let t = null;
            try { t = JSON.parse(localStorage.getItem('qm:lasttrip') || 'null'); } catch (e) {}
            if (!t) return;
            const show = (id, val) => { const el = document.getElementById(id); if (el && val) { el.textContent = val; el.hidden = false; } };
            const setText = (id, val) => { const el = document.getElementById(id); if (el && val) el.textContent = val; };
            setText('trip-from', t.fromName);
            setText('trip-to', t.toName);
            show('trip-num', t.number ? `قطار ${t.number}` : '');
            show('trip-ftime', t.ftime);
            show('trip-ttime', t.ttime);
            setText('trip-fdate', t.fdate || 'قيام');
            setText('trip-tdate', t.tdate || 'وصول');
            setText('trip-dur', t.dur || 'المواعيد بالكامل');
            if (t.url) document.getElementById('trip-link')?.setAttribute('href', t.url);
        })();
    </script>

    {{-- شريط البحث --}}
    <div class="flex items-center gap-2 bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 p-2 mb-3">
        <a href="{{ route('voice') }}" aria-label="بحث صوتي"
            class="w-12 h-12 rounded-full bg-rail-600 hover:bg-rail-700 text-white grid place-items-center shrink-0 active:scale-95 transition">
            <x-icon name="mic" class="w-5 h-5"/>
        </a>
        <button type="button" id="open-search" class="flex-1 flex items-center justify-between gap-2 text-start ps-2 pe-3 py-2 text-slate-400">
            <span class="text-sm">ابحث عن محطة، قطار أو موعد</span>
            <x-icon name="search" class="w-5 h-5"/>
        </button>
    </div>

    {{-- شرائح سريعة --}}
    <div id="quick-chips" class="flex gap-2 overflow-x-auto pb-1 mb-6">
        @if ($sug)
            <a href="{{ route('route', ['from' => $sug['from']->slug, 'to' => $sug['to']->slug]) }}"
                class="shrink-0 inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 text-slate-700 rounded-full px-3 py-1.5 text-sm transition active:scale-95">
                <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/> إلى {{ $sug['to']->name_ar }}
            </a>
            <a href="{{ route('route', ['from' => $sug['from']->slug, 'to' => $sug['to']->slug]) }}"
                class="shrink-0 inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 text-slate-700 rounded-full px-3 py-1.5 text-sm transition active:scale-95">
                <x-icon name="dot" class="w-2.5 h-2.5 text-rail-600"/> من {{ $sug['from']->name_ar }}
            </a>
        @endif
        <button type="button" id="chip-near"
            class="shrink-0 inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 text-slate-700 rounded-full px-3 py-1.5 text-sm transition active:scale-95">
            <x-icon name="pin" class="w-3.5 h-3.5 text-rail-600"/> أقرب محطة
        </button>
    </div>

    {{-- البحث (يفتح من الشريط) --}}
    <section id="wiz" hidden class="relative bg-white rounded-3xl shadow-xl ring-1 ring-slate-100 p-5 mb-6 scroll-mt-4">
        <button type="button" id="close-search" aria-label="إغلاق"
            class="absolute top-3.5 end-3.5 w-8 h-8 grid place-items-center rounded-xl text-slate-400 hover:bg-slate-100 z-10">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        @error('number')
            <div class="flex items-center gap-2 bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-4">
                <x-icon name="alert" class="w-5 h-5 shrink-0"/> {{ $message }}
            </div>
        @enderror

        {{-- مؤشّر التقدّم --}}
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1 flex gap-1.5">
                <span class="wz-bar h-1.5 flex-1 rounded-full bg-rail-600 transition-colors"></span>
                <span class="wz-bar h-1.5 flex-1 rounded-full bg-slate-200 transition-colors"></span>
                <span class="wz-bar h-1.5 flex-1 rounded-full bg-slate-200 transition-colors"></span>
            </div>
            <span class="text-xs font-bold text-slate-400 whitespace-nowrap">خطوة <span id="wz-num">1</span>/3</span>
        </div>

        <form id="search-form" action="{{ route('search') }}" method="GET">
            <input type="hidden" name="from" id="from">
            <input type="hidden" name="to" id="to">

            {{-- خطوة ١: محطة القيام --}}
            <div data-pane="1" class="wz-pane">
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-9 h-9 grid place-items-center rounded-xl bg-rail-50 text-rail-600 shrink-0"><x-icon name="dot" class="w-3 h-3"/></span>
                    <div><p class="font-extrabold leading-tight">من فين؟</p><p class="text-xs text-slate-400">اختار محطة القيام</p></div>
                </div>
                <div class="relative mb-2">
                    <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none"/>
                    <input type="text" data-search="from" placeholder="دوّر على محطة…" autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                </div>
                <button type="button" id="near-btn" class="inline-flex items-center gap-1.5 text-xs font-bold text-rail-700 hover:text-rail-800 mb-2">
                    <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/> أقرب محطة ليّ
                </button>
                <p id="near-msg" hidden class="text-xs text-amber-600 mb-2"></p>
                <ul data-list="from" class="max-h-64 overflow-y-auto"></ul>
            </div>

            {{-- خطوة ٢: محطة الوصول --}}
            <div data-pane="2" class="wz-pane" hidden>
                <button type="button" data-back class="flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-rail-600 mb-2"><x-icon name="chevron-right" class="w-4 h-4 rotate-180"/> رجوع</button>
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="w-9 h-9 grid place-items-center rounded-xl bg-amber-50 text-amber-500 shrink-0"><x-icon name="pin" class="w-4 h-4"/></span>
                    <div><p class="font-extrabold leading-tight">رايح فين؟</p><p class="text-xs text-slate-400">من <span data-show="from" class="text-rail-700 font-bold"></span></p></div>
                </div>
                <div class="relative mb-2">
                    <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none"/>
                    <input type="text" data-search="to" placeholder="دوّر على محطة…" autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                </div>
                <ul data-list="to" class="max-h-64 overflow-y-auto"></ul>
            </div>

            {{-- خطوة ٣: التاريخ --}}
            <div data-pane="3" class="wz-pane" hidden>
                <button type="button" data-back class="flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-rail-600 mb-3"><x-icon name="chevron-right" class="w-4 h-4 rotate-180"/> رجوع</button>
                <div class="flex items-center justify-center gap-2 text-sm font-bold mb-4 bg-slate-50 rounded-2xl py-3 px-3">
                    <span data-show="from2" class="text-rail-700 truncate"></span>
                    <x-icon name="arrow-left" class="w-4 h-4 text-slate-400 shrink-0"/>
                    <span data-show="to2" class="text-amber-600 truncate"></span>
                </div>
                <p class="font-extrabold mb-2">إمتى تسافر؟</p>
                <div class="flex flex-wrap gap-2 mb-3" id="wz-days"></div>
                <div class="relative mb-4">
                    <x-icon name="calendar" class="absolute top-1/2 -translate-y-1/2 start-4 w-5 h-5 text-slate-400 pointer-events-none"/>
                    <input type="date" name="date" id="wz-date" value="{{ now()->toDateString() }}"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-12 pe-3 py-3.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30 focus:border-rail-500 focus:bg-white transition">
                </div>
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-4 transition shadow-lg shadow-rail-600/25">
                    <x-icon name="search" class="w-5 h-5"/>
                    ابحث عن القطارات
                </button>
            </div>
        </form>

        {{-- بحث برقم القطار (اختياري) --}}
        <div class="mt-4 pt-4 border-t border-slate-100">
            <button type="button" id="num-toggle" class="mx-auto flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-rail-600 transition">
                <x-icon name="search" class="w-3.5 h-3.5"/> أو دوّر برقم القطار
            </button>
            <form action="{{ route('search') }}" method="GET" id="num-form" hidden class="flex gap-2 mt-3">
                <input type="text" name="number" inputmode="numeric" placeholder="رقم القطار (مثال: 936)" required
                    class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-rail-500/30 focus:border-rail-500 focus:bg-white transition">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 active:scale-95 text-white font-bold rounded-2xl px-6 transition whitespace-nowrap">
                    اعرض
                </button>
            </form>
        </div>

        <style>
            .wz-pane.wz-in { animation: wzIn .25s ease both; }
            @keyframes wzIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        </style>

        <script>
            (() => {
                const STATIONS = @json($stationsJs);
                // تطبيع عربي بسيط (همزات/تاء مربوطة/ألف مقصورة/تشكيل) لبحث أفضل.
                const norm = (s) => s.replace(/[إأآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/[ً-ْٰ]/g, '').trim();
                const TODAY = @json(now()->toDateString());

                const wiz = document.getElementById('wiz');
                const fromHidden = document.getElementById('from');
                const toHidden = document.getElementById('to');
                const panes = { 1: wiz.querySelector('[data-pane="1"]'), 2: wiz.querySelector('[data-pane="2"]'), 3: wiz.querySelector('[data-pane="3"]') };
                const bars = [...wiz.querySelectorAll('.wz-bar')];
                const numEl = document.getElementById('wz-num');
                let step = 1;

                function go(n) {
                    step = n;
                    [1, 2, 3].forEach(i => panes[i].hidden = (i !== n));
                    numEl.textContent = n;
                    bars.forEach((b, i) => { b.classList.toggle('bg-rail-600', i < n); b.classList.toggle('bg-slate-200', i >= n); });
                    const p = panes[n];
                    p.classList.remove('wz-in'); void p.offsetWidth; p.classList.add('wz-in');
                }

                const setShow = (key, val) => wiz.querySelectorAll(`[data-show="${key}"]`).forEach(el => el.textContent = val);

                function renderList(listEl, query, exclude, onPick) {
                    const nq = norm(query || '');
                    const items = STATIONS.filter(s => String(s.id) !== String(exclude) && (!nq || norm(s.name).includes(nq)));
                    listEl.innerHTML = items.slice(0, 80).map(s =>
                        `<li><button type="button" data-id="${s.id}" class="w-full text-start px-3 py-2.5 rounded-xl text-sm hover:bg-rail-50 active:bg-rail-100 transition">${s.name}</button></li>`
                    ).join('') || '<li class="px-3 py-3 text-sm text-slate-400">مفيش محطة بالاسم ده</li>';
                    listEl.onclick = (e) => { const b = e.target.closest('[data-id]'); if (b) onPick(b.dataset.id, b.textContent.trim()); };
                }

                const fromSearch = wiz.querySelector('[data-search="from"]');
                const fromList = wiz.querySelector('[data-list="from"]');
                const toSearch = wiz.querySelector('[data-search="to"]');
                const toList = wiz.querySelector('[data-list="to"]');

                function pickFrom(id, name) {
                    fromHidden.value = id;
                    setShow('from', name); setShow('from2', name);
                    toSearch.value = '';
                    renderList(toList, '', id, pickTo);
                    go(2);
                }
                function pickTo(id, name) {
                    toHidden.value = id;
                    setShow('to2', name);
                    go(3);
                }

                fromSearch.addEventListener('input', () => renderList(fromList, fromSearch.value, toHidden.value, pickFrom));
                toSearch.addEventListener('input', () => renderList(toList, toSearch.value, fromHidden.value, pickTo));

                // Enter يختار أول نتيجة بدل ما يبعت الفورم بدري.
                [fromSearch, toSearch].forEach(inp => inp.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); inp.closest('.wz-pane').querySelector('[data-id]')?.click(); }
                }));

                wiz.querySelectorAll('[data-back]').forEach(b => b.addEventListener('click', () => go(step - 1)));

                // خطوة التاريخ: كبسات سريعة + إدخال يدوي.
                const dateInput = document.getElementById('wz-date');
                const daysBox = document.getElementById('wz-days');
                const isoAdd = (n) => { const d = new Date(TODAY + 'T00:00'); d.setDate(d.getDate() + n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
                const DAYS = [[0, 'النهاردة'], [1, 'بكرة'], [2, 'بعد بكرة']];
                function paintDays() {
                    daysBox.innerHTML = DAYS.map(([n, lbl]) => {
                        const iso = isoAdd(n), on = dateInput.value === iso;
                        return `<button type="button" data-iso="${iso}" class="rounded-xl px-3 py-1.5 text-sm font-bold transition ${on ? 'bg-rail-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">${lbl}</button>`;
                    }).join('');
                }
                daysBox.addEventListener('click', (e) => { const b = e.target.closest('[data-iso]'); if (b) { dateInput.value = b.dataset.iso; paintDays(); } });
                dateInput.addEventListener('change', paintDays);
                paintDays();

                renderList(fromList, '', '', pickFrom);

                // أقرب محطة ليك (تحديد موقع مرة واحدة، من غير تتبّع).
                const nearBtn = document.getElementById('near-btn');
                const nearMsg = document.getElementById('near-msg');
                const nearLabel = nearBtn.innerHTML;
                const withCoords = STATIONS.filter(s => s.lat && s.lng);
                const haversine = (la1, lo1, la2, lo2) => {
                    const toR = (x) => x * Math.PI / 180, R = 6371;
                    const dLa = toR(la2 - la1), dLo = toR(lo2 - lo1);
                    const a = Math.sin(dLa / 2) ** 2 + Math.cos(toR(la1)) * Math.cos(toR(la2)) * Math.sin(dLo / 2) ** 2;
                    return 2 * R * Math.asin(Math.sqrt(a));
                };
                const fmtKm = (km) => km < 1 ? Math.round(km * 1000) + ' م' : km.toFixed(km < 10 ? 1 : 0) + ' كم';
                nearBtn.addEventListener('click', () => {
                    nearMsg.hidden = true;
                    if (!navigator.geolocation || !withCoords.length) { nearMsg.textContent = 'تحديد الموقع مش متاح دلوقتي.'; nearMsg.hidden = false; return; }
                    nearBtn.disabled = true; nearBtn.textContent = 'بنحدد مكانك…';
                    navigator.geolocation.getCurrentPosition((pos) => {
                        const { latitude: la, longitude: lo } = pos.coords;
                        const near = withCoords
                            .map(s => ({ ...s, km: haversine(la, lo, +s.lat, +s.lng) }))
                            .sort((a, b) => a.km - b.km).slice(0, 8);
                        fromList.innerHTML = near.map(s =>
                            `<li><button type="button" data-id="${s.id}" data-name="${s.name}" class="w-full text-start px-3 py-2.5 rounded-xl text-sm hover:bg-rail-50 active:bg-rail-100 transition flex items-center justify-between gap-2">
                                <span>${s.name}</span><span class="text-xs text-slate-400 shrink-0">${fmtKm(s.km)}</span></button></li>`
                        ).join('');
                        fromList.onclick = (e) => { const b = e.target.closest('[data-id]'); if (b) pickFrom(b.dataset.id, b.dataset.name); };
                        nearBtn.disabled = false; nearBtn.innerHTML = nearLabel;
                    }, (err) => {
                        nearMsg.textContent = err.code === 1 ? 'لازم تسمح بالوصول لمكانك.' : 'تعذّر تحديد مكانك، حاول تاني.';
                        nearMsg.hidden = false;
                        nearBtn.disabled = false; nearBtn.innerHTML = nearLabel;
                    }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
                });

                document.getElementById('search-form').addEventListener('submit', (e) => {
                    if (!fromHidden.value || !toHidden.value) { e.preventDefault(); go(!fromHidden.value ? 1 : 2); }
                });

                const numForm = document.getElementById('num-form');
                document.getElementById('num-toggle').addEventListener('click', () => {
                    numForm.hidden = !numForm.hidden;
                    if (!numForm.hidden) numForm.querySelector('input').focus();
                });

                go(1);
            })();
        </script>
    </section>

    @include('partials.permissions')

    {{-- خدمات سريعة (شبكة ٣×٢) --}}
    <div class="flex items-center gap-2 mb-3">
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-rail-300" fill="currentColor" aria-hidden="true"><path d="M12 2l1.6 5.6L19 9l-5.4 1.4L12 16l-1.6-5.6L5 9l5.4-1.4z"/></svg>
        <h2 class="font-extrabold text-white">خدمات سريعة</h2>
    </div>
    <section class="grid grid-cols-3 gap-3">
        @php
            $services = [
                ['icon' => 'mic',     't' => 'البحث بصوتك',  's' => 'اسأل بسهولة',  'href' => route('voice')],
                ['icon' => 'seat',    't' => 'المقاعد',      's' => 'شوف الفاضي',   'act' => 'search'],
                ['icon' => 'star',    't' => 'المفضلة',      's' => 'المحفوظة',     'href' => route('favorites')],
                ['icon' => 'pin',     't' => 'محطات قريبة', 's' => 'حواليك',       'act' => 'near'],
                ['icon' => 'station', 't' => 'جدول المحطات', 's' => 'المواعيد',     'act' => 'search'],
                ['icon' => 'bell',    't' => 'التنبيهات',    's' => 'المهمة',       'href' => route('premium')],
            ];
        @endphp
        @foreach ($services as $s)
            <{{ isset($s['href']) ? 'a' : 'button' }}
                @if (isset($s['href'])) href="{{ $s['href'] }}" @else type="button" data-quick="{{ $s['act'] }}" @endif
                class="group bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-3 flex flex-col items-center text-center active:scale-95 transition">
                <span class="w-12 h-12 grid place-items-center rounded-full bg-rail-600 text-white mb-2 shadow-md shadow-rail-600/30 group-active:scale-95 transition">
                    <x-icon :name="$s['icon']" class="w-6 h-6"/>
                </span>
                <span class="font-bold text-xs text-slate-800">{{ $s['t'] }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 leading-tight">{{ $s['s'] }}</span>
            </{{ isset($s['href']) ? 'a' : 'button' }}>
        @endforeach
    </section>

    {{-- بانر المقاعد --}}
    <div class="relative overflow-hidden rounded-3xl mt-5 p-5 bg-linear-to-br from-rail-700 via-rail-800 to-rail-900 ring-1 ring-white/10 text-white">
        <svg class="absolute -bottom-4 -end-3 w-28 h-28 text-white/10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="7" y="3" width="10" height="11" rx="3"/><rect x="3.4" y="9" width="3.4" height="8" rx="1.6"/><rect x="17.2" y="9" width="3.4" height="8" rx="1.6"/><rect x="6" y="12.5" width="12" height="5" rx="2"/></svg>
        <div class="relative text-center">
            <h3 class="text-lg font-extrabold">شوف مقعدك المفضل</h3>
            <p class="text-sm text-rail-100/80 mt-1">اعرف الأماكن الفاضية قبل ما تتحرك</p>
            <button type="button" data-quick="search" class="inline-flex items-center gap-1 bg-white text-rail-700 font-bold rounded-full px-5 py-2.5 mt-4 active:scale-95 transition">
                استكشف المقاعد <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <div class="flex justify-center gap-1.5 mt-4">
                <span class="w-5 h-1.5 rounded-full bg-white"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const wiz = document.getElementById('wiz');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            // فتح/إغلاق صندوق البحث
            const openWiz = () => {
                if (!wiz) return;
                wiz.hidden = false;
                wiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setTimeout(() => wiz.querySelector('[data-search="from"]')?.focus({ preventScroll: true }), 300);
            };
            document.getElementById('open-search')?.addEventListener('click', openWiz);
            document.getElementById('close-search')?.addEventListener('click', () => { wiz.hidden = true; });

            // الخدمات السريعة
            document.querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => {
                try { navigator.vibrate?.(10); } catch (e) {}
                openWiz();
                if (b.dataset.quick === 'near') setTimeout(() => document.getElementById('near-btn')?.click(), 400);
            }));

            // أقرب محطة (الشريحة)
            document.getElementById('chip-near')?.addEventListener('click', () => { openWiz(); setTimeout(() => document.getElementById('near-btn')?.click(), 400); });

            // نضيف آخر بحث/قطار مفضّل في أول الشرائح (لو موجود) من غير ما نمسح الافتراضي
            const chipsBox = document.getElementById('quick-chips');
            const get = (k) => { try { return JSON.parse(localStorage.getItem(k) || '[]'); } catch (e) { return []; } };
            const base = 'shrink-0 inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 text-slate-700 rounded-full px-3 py-1.5 text-sm transition active:scale-95';
            const TR = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-rail-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8M7 16l-2 4m12-4l2 4"/></svg>';
            const f = get('qm:fav')[0];
            if (f) chipsBox.insertAdjacentHTML('afterbegin', `<a href="${esc(f.url)}" class="${base}">${TR}<span>قطار ${esc(f.number)}</span></a>`);
            const r = get('qm:recent')[0];
            if (r) {
                const url = `/search?from=${encodeURIComponent(r.from)}&to=${encodeURIComponent(r.to)}&date=${encodeURIComponent(r.date || '')}`;
                chipsBox.insertAdjacentHTML('afterbegin', `<a href="${url}" class="${base}"><span>${esc(r.fromName)} ← ${esc(r.toName)}</span></a>`);
            }
        })();
    </script>
@endsection
