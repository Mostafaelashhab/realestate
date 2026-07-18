@extends('layouts.app')

@section('title', 'ابحث عن رحلتك')
@section('og_desc', 'مواعيد وأسعار قطارات مصر — ابحث عن رحلتك خطوة بخطوة.')
@section('hideHeader', '1')

@php
    $stationsJs = ($stations ?? collect())->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar, 'lat' => $s->lat, 'lng' => $s->lng])->values();
    $nearJs = ($stations ?? collect())->filter(fn ($s) => $s->lat && $s->lng)->map(fn ($s) => ['id' => $s->id, 'lat' => $s->lat, 'lng' => $s->lng])->values();
@endphp

@section('content')
    @include('partials.home-promos')

    @error('number')
        <div class="relative z-10 flex items-center gap-2 bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-3">
            <x-icon name="alert" class="w-5 h-5 shrink-0"/> {{ $message }}
        </div>
    @enderror

    {{-- ===== ويزارد ملء الشاشة ===== --}}
    <div id="wiz" class="relative overflow-hidden rounded-[2rem] bg-linear-to-b from-rail-50 via-white to-white ring-1 ring-slate-100 shadow-sm p-5">
        {{-- أشكال SVG زخرفية بتملا الخلفية --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
            <div class="absolute -top-20 -end-16 w-64 h-64 rounded-full bg-rail-200/40 blur-3xl"></div>
            <div class="absolute top-1/3 -start-20 w-56 h-56 rounded-full bg-amber-200/30 blur-3xl"></div>
            <div class="absolute bottom-10 end-1/4 w-40 h-40 rounded-full bg-rail-300/20 blur-2xl"></div>
            <svg class="absolute top-24 start-6 w-24 h-24 text-rail-200/60" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><g>@for ($y = 0; $y < 5; $y++)@for ($x = 0; $x < 5; $x++)<circle cx="{{ 8 + $x * 20 }}" cy="{{ 8 + $y * 20 }}" r="3"/>@endfor @endfor</g></svg>
            <svg class="absolute bottom-6 -start-6 w-52 h-52 text-rail-100" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 0v100M45 0v100M70 0v100"/><path d="M0 35h100M0 60h100" stroke-dasharray="5 7"/></svg>
            <svg class="absolute -bottom-6 end-4 w-40 h-40 text-rail-100" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="5" y="3" width="14" height="13" rx="3"/><rect x="3" y="9" width="3" height="7" rx="1.5"/><rect x="18" y="9" width="3" height="7" rx="1.5"/></svg>
        </div>

        {{-- الشريط العلوي: رجوع + تقدّم --}}
        <div class="relative z-10 flex items-center gap-3 mb-8">
            <button type="button" id="wz-back" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
            </button>
            <div class="flex-1 flex gap-1.5">
                <span class="wz-bar h-1.5 flex-1 rounded-full bg-rail-600 transition-colors"></span>
                <span class="wz-bar h-1.5 flex-1 rounded-full bg-slate-200 transition-colors"></span>
                <span class="wz-bar h-1.5 flex-1 rounded-full bg-slate-200 transition-colors"></span>
            </div>
            <span class="text-xs font-bold text-slate-400 whitespace-nowrap">خطوة <span id="wz-num">1</span>/3</span>
        </div>

        <form action="{{ route('search') }}" method="GET" id="wz-form" class="relative z-10">
            <input type="hidden" name="from" id="wz-from">
            <input type="hidden" name="to" id="wz-to">

            {{-- خطوة ١: القيام --}}
            <div data-pane="1" class="wz-pane">
                <div class="mb-4">
                    <span class="inline-grid place-items-center w-14 h-14 rounded-2xl bg-rail-600 text-white shadow-lg shadow-rail-600/25 mb-3">
                        <span class="w-4 h-4 rounded-full bg-white"></span>
                    </span>
                    <h2 class="text-2xl font-extrabold text-slate-800">من فين مسافر؟</h2>
                    <p class="text-sm text-slate-400 mt-1">اختار محطة القيام</p>
                </div>
                <div class="relative mb-2">
                    <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3.5 w-4 h-4 text-slate-400 pointer-events-none"/>
                    <input type="text" data-search="from" placeholder="اكتب اسم المحطة…" autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur ps-10 pe-3 py-3.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                </div>
                <button type="button" id="wz-near" class="self-start inline-flex items-center gap-1.5 text-xs font-bold text-rail-700 mb-2">
                    <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/> أقرب محطة ليّ
                </button>
                <p id="wz-near-msg" hidden class="text-xs text-amber-600 mb-2"></p>
                <ul data-list="from" class="max-h-64 overflow-y-auto overscroll-contain -mx-1 px-1 mt-1"></ul>
            </div>

            {{-- خطوة ٢: النزول --}}
            <div data-pane="2" class="wz-pane" hidden>
                <div class="mb-4">
                    <span class="inline-grid place-items-center w-14 h-14 rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/25 mb-3"><x-icon name="pin" class="w-6 h-6"/></span>
                    <h2 class="text-2xl font-extrabold text-slate-800">رايح فين؟</h2>
                    <p class="text-sm text-slate-400 mt-1">من <span data-show="from" class="text-rail-700 font-bold"></span></p>
                </div>
                <div class="relative mb-2">
                    <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3.5 w-4 h-4 text-slate-400 pointer-events-none"/>
                    <input type="text" data-search="to" placeholder="اكتب اسم المحطة…" autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur ps-10 pe-3 py-3.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                </div>
                <ul data-list="to" class="max-h-64 overflow-y-auto overscroll-contain -mx-1 px-1 mt-1"></ul>
            </div>

            {{-- خطوة ٣: التاريخ --}}
            <div data-pane="3" class="wz-pane" hidden>
                <div class="mb-4">
                    <span class="inline-grid place-items-center w-14 h-14 rounded-2xl bg-sky-500 text-white shadow-lg shadow-sky-500/25 mb-3"><x-icon name="calendar" class="w-6 h-6"/></span>
                    <h2 class="text-2xl font-extrabold text-slate-800">إمتى تسافر؟</h2>
                    <p class="text-sm text-slate-400 mt-1">اختار يوم الرحلة</p>
                </div>
                <div class="flex items-center justify-center gap-2 text-sm font-bold bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm py-3 px-3 mb-4">
                    <span data-show="from2" class="text-rail-700 truncate"></span>
                    <x-icon name="arrow-left" class="w-4 h-4 text-slate-400 shrink-0"/>
                    <span data-show="to2" class="text-amber-600 truncate"></span>
                </div>
                <div class="flex flex-wrap gap-2 mb-3" id="wz-days"></div>
                <div class="relative mb-auto">
                    <x-icon name="calendar" class="absolute top-1/2 -translate-y-1/2 start-4 w-5 h-5 text-slate-400 pointer-events-none"/>
                    <input type="date" name="date" id="wz-date" value="{{ now()->toDateString() }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white ps-12 pe-3 py-3.5 focus:outline-none focus:border-rail-500 transition">
                </div>
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-4 mt-4 shadow-lg shadow-rail-600/25 transition">
                    <x-icon name="search" class="w-5 h-5"/> ابحث عن القطارات
                </button>
            </div>
        </form>

        {{-- بحث برقم القطر --}}
        <div class="relative z-10 text-center mt-3">
            <button type="button" id="num-toggle" class="mx-auto inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-rail-600 transition"><x-icon name="search" class="w-3.5 h-3.5"/> أو دوّر برقم القطار</button>
            <form action="{{ route('search') }}" method="GET" id="num-form" hidden class="flex gap-2 mt-3">
                <input type="text" name="number" inputmode="numeric" placeholder="رقم القطر (مثال: 936)" required class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:border-rail-500 transition">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 active:scale-95 text-white font-bold rounded-2xl px-6 transition whitespace-nowrap">اعرض</button>
            </form>
        </div>
    </div>

    {{-- مسارات شائعة --}}
    @if (($popular ?? collect())->isNotEmpty())
        @php $tints = ['bg-rail-50 text-rail-700', 'bg-amber-50 text-amber-700', 'bg-sky-50 text-sky-700', 'bg-rose-50 text-rose-700', 'bg-teal-50 text-teal-700', 'bg-violet-50 text-violet-700']; @endphp
        <h2 class="font-extrabold text-slate-800 mt-6 mb-3">مسارات شائعة</h2>
        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-1 -mx-4 px-4">
            @foreach ($popular as $i => $p)
                <a href="{{ route('route', ['from' => $p['from']->slug, 'to' => $p['to']->slug]) }}" class="shrink-0 w-44 rounded-3xl p-4 ring-1 ring-slate-100 shadow-sm bg-white active:scale-95 transition">
                    <span class="w-10 h-10 grid place-items-center rounded-2xl {{ $tints[$i % count($tints)] }} mb-3"><x-icon name="train" class="w-5 h-5"/></span>
                    <div class="font-bold text-slate-800 text-sm truncate">{{ $p['from']->name_ar }}</div>
                    <div class="flex items-center gap-1 text-xs text-slate-400 mt-0.5"><x-icon name="arrow-left" class="w-3.5 h-3.5"/><span class="truncate">{{ $p['to']->name_ar }}</span></div>
                </a>
            @endforeach
        </div>
    @endif

    <script>
        (() => {
            const STATIONS = @json($stationsJs);
            const NEAR = @json($nearJs);
            const TODAY = @json(now()->toDateString());
            const norm = (s) => s.replace(/[إأآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/[ً-ْٰ]/g, '').trim();

            const wiz = document.getElementById('wiz');
            const fromHidden = document.getElementById('wz-from'), toHidden = document.getElementById('wz-to');
            const panes = { 1: wiz.querySelector('[data-pane="1"]'), 2: wiz.querySelector('[data-pane="2"]'), 3: wiz.querySelector('[data-pane="3"]') };
            const bars = [...wiz.querySelectorAll('.wz-bar')], numEl = document.getElementById('wz-num');
            let step = 1;
            const setShow = (k, v) => wiz.querySelectorAll(`[data-show="${k}"]`).forEach(el => el.textContent = v);

            function go(n) {
                step = n;
                [1, 2, 3].forEach(i => panes[i].hidden = i !== n);
                numEl.textContent = n;
                bars.forEach((b, i) => { b.classList.toggle('bg-rail-600', i < n); b.classList.toggle('bg-slate-200', i >= n); });
            }

            function renderList(listEl, query, exclude, onPick) {
                const nq = norm(query || '');
                if (!nq) { listEl.innerHTML = '<li class="px-4 py-5 text-sm text-slate-400 text-center">اكتب اسم المحطة عشان تدوّر عليها</li>'; listEl.onclick = null; return; }
                const items = STATIONS.filter(s => String(s.id) !== String(exclude) && norm(s.name).includes(nq));
                listEl.innerHTML = items.slice(0, 100).map(s => `<li><button type="button" data-id="${s.id}" class="w-full text-start px-4 py-3 rounded-2xl text-sm font-medium hover:bg-rail-50 active:bg-rail-100 transition">${s.name}</button></li>`).join('') || '<li class="px-4 py-4 text-sm text-slate-400 text-center">مفيش محطة بالاسم ده</li>';
                listEl.onclick = (e) => { const b = e.target.closest('[data-id]'); if (b) onPick(b.dataset.id, b.textContent.trim()); };
            }

            const fromSearch = wiz.querySelector('[data-search="from"]'), fromList = wiz.querySelector('[data-list="from"]');
            const toSearch = wiz.querySelector('[data-search="to"]'), toList = wiz.querySelector('[data-list="to"]');

            function pickFrom(id, name) { fromHidden.value = id; setShow('from', name); setShow('from2', name); toSearch.value = ''; renderList(toList, '', id, pickTo); go(2); setTimeout(() => toSearch.focus(), 50); }
            function pickTo(id, name) { toHidden.value = id; setShow('to2', name); go(3); }

            fromSearch.addEventListener('input', () => renderList(fromList, fromSearch.value, toHidden.value, pickFrom));
            toSearch.addEventListener('input', () => renderList(toList, toSearch.value, fromHidden.value, pickTo));
            [fromSearch, toSearch].forEach(inp => inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); inp.closest('.wz-pane').querySelector('[data-id]')?.click(); } }));
            renderList(fromList, '', '', pickFrom);

            // رجوع
            document.getElementById('wz-back').addEventListener('click', () => { if (step > 1) go(step - 1); else location.href = @json(route('home')); });

            // التاريخ
            const dateInput = document.getElementById('wz-date'), daysBox = document.getElementById('wz-days');
            const isoAdd = (n) => { const d = new Date(TODAY + 'T00:00'); d.setDate(d.getDate() + n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
            const DAYS = [[0, 'النهاردة'], [1, 'بكرة'], [2, 'بعد بكرة']];
            function paintDays() { daysBox.innerHTML = DAYS.map(([n, lbl]) => { const iso = isoAdd(n), on = dateInput.value === iso; return `<button type="button" data-iso="${iso}" class="rounded-xl px-3.5 py-2 text-sm font-bold transition ${on ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600'}">${lbl}</button>`; }).join(''); }
            daysBox.addEventListener('click', (e) => { const b = e.target.closest('[data-iso]'); if (b) { dateInput.value = b.dataset.iso; paintDays(); } });
            dateInput.addEventListener('change', paintDays);
            paintDays();

            // أقرب محطة
            const nearBtn = document.getElementById('wz-near'), nearMsg = document.getElementById('wz-near-msg'), nearLabel = nearBtn.innerHTML;
            nearBtn.addEventListener('click', () => {
                nearMsg.hidden = true;
                if (!navigator.geolocation || !NEAR.length) { nearMsg.textContent = 'تحديد الموقع مش متاح دلوقتي.'; nearMsg.hidden = false; return; }
                nearBtn.disabled = true; nearBtn.textContent = 'بنحدد مكانك…';
                const toR = (x) => x * Math.PI / 180, dist = (a, b, c, d) => { const dLa = toR(c - a), dLo = toR(d - b); const h = Math.sin(dLa / 2) ** 2 + Math.cos(toR(a)) * Math.cos(toR(c)) * Math.sin(dLo / 2) ** 2; return Math.asin(Math.sqrt(h)); };
                navigator.geolocation.getCurrentPosition((pos) => {
                    const { latitude: la, longitude: lo } = pos.coords; let best = null;
                    NEAR.forEach(s => { const dd = dist(la, lo, +s.lat, +s.lng); if (!best || dd < best.d) best = { id: s.id, d: dd }; });
                    const st = STATIONS.find(s => String(s.id) === String(best.id));
                    if (st) pickFrom(st.id, st.name);
                    nearBtn.disabled = false; nearBtn.innerHTML = nearLabel;
                }, () => { nearMsg.textContent = 'تعذّر تحديد مكانك، حاول تاني.'; nearMsg.hidden = false; nearBtn.disabled = false; nearBtn.innerHTML = nearLabel; }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
            });

            // تحقق + رقم القطر
            document.getElementById('wz-form').addEventListener('submit', (e) => { if (!fromHidden.value) { e.preventDefault(); go(1); } else if (!toHidden.value) { e.preventDefault(); go(2); } });
            const numForm = document.getElementById('num-form');
            document.getElementById('num-toggle').addEventListener('click', () => { numForm.hidden = !numForm.hidden; if (!numForm.hidden) numForm.querySelector('input').focus(); });

            go(1);
        })();
    </script>
@endsection
