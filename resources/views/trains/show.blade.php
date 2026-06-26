@extends('layouts.app')

@section('title', "قطار {$train->number}")
@section('og_title', "قطار {$train->number}" . ($origin && $terminal ? " — {$origin->name_ar} ← {$terminal->name_ar}" : ''))
@section('og_desc', trim((\App\Support\Format::time($depart) ? \App\Support\Format::time($depart) . ' ← ' . \App\Support\Format::time($arrive) . ' · ' : '') . ($duration ? $duration . ' · ' : '') . 'مواعيد وأسعار رحلتك على قطارات مصر.'))

@section('content')
    {{-- هوية القطار + ملخّص الرحلة (هيرو) --}}
    <section
        class="relative overflow-hidden bg-linear-to-br from-rail-800 via-rail-700 to-rail-600 text-white rounded-3xl p-5 mb-5 shadow-xl shadow-rail-800/25">
        {{-- زخرفة قضبان خفيفة --}}
        <svg class="absolute -top-8 -start-10 w-44 h-44 text-white/10" viewBox="0 0 100 100" fill="none"
            stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100" />
            <path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8" />
        </svg>

        <div class="relative">
            {{-- الصف العلوي: الرقم + النوع + الحالة + أزرار --}}
            <div class="flex items-start gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="bg-white/15 ring-1 ring-white/20 text-base font-extrabold px-3 py-1 rounded-xl">قطار
                            {{ $train->number }}</span>
                        @if ($train->active)
                            <span class="inline-flex items-center gap-1 text-xs bg-white/15 px-2 py-1 rounded-md">
                                <x-icon name="check" class="w-3.5 h-3.5" /> مؤكد
                            </span>
                        @endif
                    </div>
                    <p class="text-rail-50/90 text-sm mt-1.5">{{ $train->type_label }}</p>
                </div>

                <div class="ms-auto flex items-center gap-1.5 shrink-0">
                    <button type="button" data-share
                        data-share-title="قطار {{ $train->number }}@if ($origin && $terminal) — {{ $origin->name_ar }} ← {{ $terminal->name_ar }}@endif"
                        aria-label="مشاركة"
                        class="w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 active:scale-90 text-white transition">
                        <x-icon name="share" class="w-5 h-5" />
                    </button>
                    <button id="fav-btn" type="button" aria-label="إضافة للمفضلة"
                        class="w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 active:scale-90 text-white/80 transition">
                        <x-icon name="star" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            {{-- ملخّص الرحلة --}}
            <div class="mt-5 flex items-stretch gap-3">
                <div class="text-center min-w-0">
                    <div class="text-3xl font-extrabold whitespace-nowrap leading-none">
                        {{ \App\Support\Format::time($depart) ?? '—' }}</div>
                    <div class="text-xs text-rail-50/80 mt-1.5 truncate">{{ $origin?->name_ar }}</div>
                </div>

                <div class="flex-1 flex flex-col items-center justify-center px-1">
                    @if ($duration)
                        <div class="text-[11px] bg-white/15 rounded-full px-2 py-0.5 mb-1.5 whitespace-nowrap">{{ $duration }}
                        </div>
                    @endif
                    <div class="w-full flex items-center gap-1">
                        <x-icon name="dot" class="w-2.5 h-2.5 shrink-0 text-white" />
                        <span class="flex-1 border-t-2 border-dashed border-white/40"></span>
                        <x-icon name="train" class="w-4 h-4 shrink-0 text-white" />
                        <span class="flex-1 border-t-2 border-dashed border-white/40"></span>
                        <x-icon name="pin" class="w-3.5 h-3.5 shrink-0 text-amber-300" />
                    </div>
                    @if ($validSegment)
                        <div class="text-[11px] mt-1.5 text-amber-200 font-bold">رحلتك</div>
                    @endif
                </div>

                <div class="text-center min-w-0">
                    <div class="text-3xl font-extrabold whitespace-nowrap leading-none">
                        {{ \App\Support\Format::time($arrive) ?? '—' }}</div>
                    <div class="text-xs text-rail-50/80 mt-1.5 truncate">{{ $terminal?->name_ar }}</div>
                </div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const KEY = 'qm:fav';
            const num = @json($train->number);
            const label = @json(trim(($origin?->name_ar ? $origin->name_ar . ' ← ' . $terminal?->name_ar : ($train->type_label ?? ''))));
            const url = @json(request()->getRequestUri());
            const btn = document.getElementById('fav-btn');
            const get = () => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; } };
            const isFav = () => get().some(f => f.number === num);
            const paint = () => {
                const on = isFav();
                btn.classList.toggle('text-amber-400', on);
                btn.classList.toggle('text-white/80', !on);
            };
            btn.addEventListener('click', () => {
                let list = get();
                list = isFav() ? list.filter(f => f.number !== num) : [{ number: num, label, url }, ...list].slice(0, 12);
                try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { }
                paint();
            });
            paint();
        })();
    </script>

    {{-- فين القطر دلوقتي — تقدير حسب الجدول (وقت القاهرة) --}}
    @php
        $statusStops = $scheduleStops->map(function ($s) {
            $arr = $s->arrival_time ? \Illuminate\Support\Carbon::parse($s->arrival_time) : null;
            $dep = $s->departure_time ? \Illuminate\Support\Carbon::parse($s->departure_time) : null;
            return [
                'name' => $s->station->name_ar,
                'arr' => $arr ? $arr->hour * 60 + $arr->minute : null,
                'dep' => $dep ? $dep->hour * 60 + $dep->minute : null,
            ];
        })->values();
    @endphp
    <section id="status" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-bold flex items-center gap-2"><x-icon name="train" class="w-5 h-5 text-rail-600" /> فين القطر
                دلوقتي؟</h2>
            <span class="text-xs text-slate-400 whitespace-nowrap">تقدير حسب الجدول</span>
        </div>

        <div id="status-summary" class="mt-3">
            <div class="animate-pulse h-14 bg-slate-100 rounded-2xl"></div>
        </div>
    </section>

    <script>
        (() => {
            const summaryEl = document.getElementById('status-summary');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            // — تقدير مكان القطر حسب الجدول (وقت القاهرة الحالي) —
            const STOPS = @json($statusStops);
            function cairoNowMin() {
                try {
                    const p = new Intl.DateTimeFormat('en-GB', { timeZone: 'Africa/Cairo', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(new Date());
                    return (+p.find(x => x.type === 'hour').value) * 60 + (+p.find(x => x.type === 'minute').value);
                } catch (e) { const d = new Date(); return d.getHours() * 60 + d.getMinutes(); }
            }
            const fmtMin = (m) => {
                const h = Math.floor(m / 60), mm = String(m % 60).padStart(2, '0');
                return `${(h % 12) || 12}:${mm} ${h < 12 ? 'ص' : 'م'}`;
            };
            function scheduleEstimate() {
                const stops = STOPS.filter(s => s.dep != null || s.arr != null);
                if (stops.length < 2) return null;
                const eff = stops.map(s => ({ name: s.name, in: s.arr ?? s.dep, out: s.dep ?? s.arr }));
                const firstDep = eff[0].out, lastArr = eff[eff.length - 1].in, last = eff.length - 1;
                if (lastArr <= firstDep) return null; // رحلة عابرة لمنتصف الليل — نتجنّب تقدير غلط
                const now = cairoNowMin();

                // قبل القيام
                if (now < firstDep) return {
                    text: 'لسه ما قامش', sub: `بيقوم ${fmtMin(firstDep)} من ${eff[0].name} (بعد ~${firstDep - now} دقيقة)`,
                    cur: eff[0].name, next: eff[1].name, frac: 0, arrived: false,
                };
                // وصل آخر محطة
                if (now >= lastArr) return {
                    text: `المفروض وصل ${eff[last].name}`, sub: `الوصول حوالي ${fmtMin(lastArr)}`,
                    cur: eff[last].name, next: null, frac: 1, arrived: true,
                };
                // في الطريق — نحدّد القطعة الحالية (المحطة الحالية واللي جايه)
                for (let i = 0; i < last; i++) {
                    if (now >= eff[i].in && now <= eff[i].out) return {
                        text: `في محطة ${eff[i].name}`, sub: `جايه ${eff[i + 1].name} الساعة ${fmtMin(eff[i + 1].in)}`,
                        cur: eff[i].name, next: eff[i + 1].name, frac: 0, arrived: false,
                    };
                    if (now > eff[i].out && now < eff[i + 1].in) {
                        const f = (now - eff[i].out) / (eff[i + 1].in - eff[i].out);
                        return {
                            text: `بين ${eff[i].name} و ${eff[i + 1].name}`, sub: `جايه ${eff[i + 1].name} الساعة ${fmtMin(eff[i + 1].in)}`,
                            cur: eff[i].name, next: eff[i + 1].name, frac: Math.max(0, Math.min(1, f)), arrived: false,
                        };
                    }
                }
                return { text: 'القطار في الطريق', sub: '', cur: eff[0].name, next: eff[last].name, frac: 0.5, arrived: false };
            }

            // شريط القطعة الحالية: المحطة اللي فيها → اللي جايه + صورة القطر في مكانه
            function progressBar(est) {
                const pct = (est.frac * 100).toFixed(1);
                const train = `<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8"/><path d="M7 16l-2 4m12-4l2 4"/></svg>`;
                return `<div class="mt-6 mb-1">
                        <div class="relative h-1.5 rounded-full bg-rail-100">
                            <div class="absolute inset-y-0 rounded-full bg-rail-500" style="inset-inline-start:0;inline-size:${pct}%"></div>
                            <span class="absolute -top-3 grid place-items-center w-8 h-8 rounded-full bg-rail-600 text-white ring-4 ring-white shadow-md"
                                style="inset-inline-start:calc(${pct}% - 16px)">${train}</span>
                        </div>
                        <div class="flex justify-between items-start mt-5 gap-2">
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-rail-700 truncate max-w-[45%]">
                                <span class="w-2 h-2 rounded-full bg-rail-600 shrink-0"></span>${esc(est.cur)}
                            </span>
                            ${est.next
                                ? `<span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 truncate max-w-[50%]">
                                       <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg> جايه: ${esc(est.next)}
                                   </span>`
                                : `<span class="text-xs font-bold text-emerald-600">وصل ✓</span>`}
                        </div>
                    </div>`;
            }

            function render() {
                const est = scheduleEstimate();
                if (!est) {
                    summaryEl.innerHTML = '<div class="rounded-2xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-500">مواعيد الجدول مش كافية لتقدير مكان القطر دلوقتي.</div>';
                    return;
                }
                summaryEl.innerHTML = `<div class="rounded-2xl bg-rail-50 border border-rail-200 p-3.5">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-rail-600 text-white rounded-full px-2 py-0.5">حسب الجدول</span>
                            <span class="font-extrabold text-rail-800">${esc(est.text)}</span>
                        </div>
                        ${progressBar(est)}
                        ${est.sub ? `<p class="text-xs text-rail-700/70 mt-1">${esc(est.sub)}</p>` : ''}
                        <p class="text-[11px] text-slate-400 mt-1.5">تقدير من مواعيد الجدول — مش تتبّع فعلي.</p>
                    </div>`;
            }

            render();
            // نحدّث المكان كل دقيقة طول ما الصفحة مفتوحة
            setInterval(render, 60000);
        })();
    </script>

    {{-- الدرجات والأسعار الرسمية --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <div class="flex items-baseline justify-between gap-2 mb-1">
            <h2 class="font-bold flex items-center gap-2"><x-icon name="ticket" class="w-5 h-5 text-rail-600" /> الأسعار
                الرسمية</h2>
            @if ($origin && $terminal)
                <span class="text-xs text-slate-400 truncate">{{ $origin->name_ar }} ← {{ $terminal->name_ar }}</span>
            @endif
        </div>

        @if ($fares->isNotEmpty())
            @php $minFare = $fares->min('price'); @endphp
            <p class="text-xs text-slate-400 mb-3">من نظام الحجز الرسمي لهيئة السكة الحديد.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                @foreach ($fares as $fare)
                    @php $isCheapest = $fare->price == $minFare; @endphp
                    <div
                        class="relative rounded-2xl border p-3 {{ $isCheapest ? 'border-rail-300 bg-rail-50 ring-1 ring-rail-200' : 'border-slate-200' }}">
                        @if ($isCheapest && $fares->count() > 1)
                            <span
                                class="absolute -top-2 end-2 text-[10px] font-bold bg-rail-600 text-white rounded-full px-2 py-0.5">الأرخص</span>
                        @endif
                        <div class="text-xs text-slate-500 truncate">{{ $fare->class_ar }}</div>
                        <div class="text-lg font-extrabold text-rail-800 mt-0.5 whitespace-nowrap">
                            {{ number_format($fare->price) }} <span class="text-xs font-medium text-slate-400">ج.م</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-center">
                <p class="text-sm text-slate-500">الأسعار الرسمية للمسار ده لسه مش محمّلة — هتلاقيها تحت في «المقاعد المتاحة».
                </p>
            </div>
        @endif

        <a href="{{ route('report', ['type' => 'price', 'train' => $train->number]) }}"
            class="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5" />
            السعر غلط؟ بلّغنا
        </a>
    </section>

    {{-- التوافر اللحظي الرسمي — يُجلب تلقائيًا عند فتح الصفحة --}}
    @php $isAuth = auth()->check(); $isPremium = (bool) auth()->user()?->isPremium(); @endphp
    @if ($origin?->enr_id && $terminal?->enr_id)
        <section id="live" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5 scroll-mt-20">
            <h2 class="font-bold flex items-center gap-2">
                <span class="relative flex w-2.5 h-2.5">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-rail-400 opacity-75 animate-ping"></span>
                    <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-rail-600"></span>
                </span>
                المقاعد المتاحة دلوقتي
            </h2>
            <p class="text-xs text-slate-400 mt-1 mb-3">مباشر من نظام الهيئة — مواعيد دقيقة، عربات، درجات، أسعار، ومقاعد فاضية.
            </p>

            {{-- اختيار محطة القيام والوصول (لجلب توافر قطعة مختلفة من المسار) --}}
            <div class="grid grid-cols-2 gap-2 mb-2">
                <div>
                    <label for="live-from" class="block text-[11px] text-slate-400 mb-1">من محطة</label>
                    <div class="relative">
                        <x-icon name="dot" class="absolute top-1/2 -translate-y-1/2 start-2.5 w-2.5 h-2.5 text-rail-600 pointer-events-none"/>
                        <select id="live-from" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 ps-7 pe-3 py-2 text-sm font-medium focus:bg-white focus:border-rail-400 focus:outline-none">
                            @foreach ($routeStops as $s)
                                <option value="{{ $s['enr'] }}" @selected($origin?->enr_id == $s['enr'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label for="live-to" class="block text-[11px] text-slate-400 mb-1">إلى محطة</label>
                    <div class="relative">
                        <x-icon name="pin" class="absolute top-1/2 -translate-y-1/2 start-2.5 w-3 h-3 text-amber-500 pointer-events-none"/>
                        <select id="live-to" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 ps-7 pe-3 py-2 text-sm font-medium focus:bg-white focus:border-rail-400 focus:outline-none">
                            @foreach ($routeStops as $s)
                                <option value="{{ $s['enr'] }}" @selected($terminal?->enr_id == $s['enr'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap mb-3">
                <div class="relative flex-1 min-w-40">
                    <x-icon name="calendar"
                        class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none" />
                    <input type="date" id="live-date" value="{{ now()->toDateString() }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-2 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                </div>
                <button id="live-btn"
                    class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold rounded-xl px-4 py-2 transition">
                    <x-icon name="refresh" class="w-4 h-4" /> تحديث
                </button>
            </div>
            <div id="live-result"></div>
        </section>

        <script>
            (() => {
                const btn = document.getElementById('live-btn');
                const out = document.getElementById('live-result');
                const dateInput = document.getElementById('live-date');
                const fromSel = document.getElementById('live-from');
                const toSel = document.getElementById('live-to');
                // لو جاي من إشعار/رابط بتاريخ محدد، نستخدمه.
                const _qDate = new URLSearchParams(location.search).get('date');
                if (_qDate && /^\d{4}-\d{2}-\d{2}$/.test(_qDate)) dateInput.value = _qDate;
                const SEARCH_URL = @json(config('enr.search_url'));
                const NUMBER = @json($train->number);
                const ORIGIN_ENR = @json($origin->enr_id);
                const TERMINAL_ENR = @json($terminal->enr_id);
                const ROUTE = @json($routeStops); // [{enr,name,order}] لترتيب المحطات
                const orderOf = (enr) => ROUTE.find(s => s.enr === enr)?.order;
                const nameOf = (enr) => ROUTE.find(s => s.enr === enr)?.name ?? '';
                const SKELETON = '<div class="animate-pulse space-y-3">' +
                    '<div class="bg-white rounded-lg border border-slate-200 p-3">' +
                    '<div class="flex gap-2 mb-3"><div class="h-5 w-14 bg-slate-200 rounded"></div><div class="h-5 w-14 bg-slate-200 rounded"></div><div class="h-5 w-20 bg-slate-200 rounded"></div></div>' +
                    '<div class="flex flex-wrap gap-1.5">' + Array(16).fill('<div class="w-7 h-9 bg-slate-200 rounded-md"></div>').join('') + '</div>' +
                    '</div></div>';
                // محطات قيام أبعد على نفس القطار (الأقرب فالأبعد).
                const ALTS = @json($boardingAlternatives);

                const errBox = (msg) => `<div class="rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">${msg} جرّب زر «تحديث» تاني.</div>`;
                const CSRF = document.querySelector('meta[name=csrf-token]')?.content;

                // مراقبة المقاعد (Premium)
                const IS_AUTH = @json($isAuth);
                const IS_PREMIUM = @json($isPremium);
                const SEATWATCH_URL = @json(route('trains.seatwatch', $train));
                const LOGIN_URL = @json(route('login'));
                const PREMIUM_URL = @json(route('premium'));

                // زر «نبّهني أول ما يفضى كرسي» — يظهر لما القطار مكتمل (مجاني لأي مسجّل).
                function seatWatchCta() {
                    if (!IS_AUTH) {
                        return `<a href="${LOGIN_URL}" class="mt-3 flex items-center justify-center gap-2 rounded-2xl bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold px-4 py-3 transition">🔔 سجّل عشان نبّهك أول ما يفضى كرسي</a>`;
                    }
                    return `<button type="button" data-seatwatch class="mt-3 w-full flex items-center justify-center gap-2 rounded-2xl bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white text-sm font-bold px-4 py-3 transition">🔔 نبّهني أول ما يفضى كرسي</button>`;
                }

                async function onSeatWatch(btn) {
                    const orig = btn.textContent;
                    const fail = (msg) => {
                        btn.disabled = false; btn.textContent = orig;
                        btn.nextElementSibling?.remove();
                        btn.insertAdjacentHTML('afterend', `<p class="seatwatch-err text-xs text-red-600 mt-2 text-center">${msg}</p>`);
                    };
                    btn.disabled = true; btn.textContent = 'جاري التفعيل…';
                    btn.parentElement.querySelector('.seatwatch-err')?.remove();
                    try {
                        const res = await fetch(SEATWATCH_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                            body: JSON.stringify({
                                from_enr: fromSel.value, to_enr: toSel.value,
                                from_name: nameOf(fromSel.value), to_name: nameOf(toSel.value),
                                date: dateInput.value,
                            }),
                        });
                        if (res.status === 401 || res.status === 419) { location.href = LOGIN_URL; return; }
                        if (!res.ok) {
                            let msg = 'تعذّر التفعيل، حاول تاني.';
                            try { const j = await res.json(); if (j.message) msg = j.message; } catch (_) {}
                            fail(msg);
                            return;
                        }
                        btn.outerHTML = '<div class="mt-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold px-4 py-3 text-center">✓ تمام! هنبّهك أول ما يفضى كرسي على المسار ده.</div>';
                    } catch (e) { fail('تعذّر الاتصال، حاول تاني.'); }
                }

                // إرسال رد الهيئة للموقع لتحديث المواعيد/الأسعار — مرّة كل ساعة لكل (مسار+تاريخ).
                function snapshot(data, from) {
                    const key = `${NUMBER}:${from}:${dateInput.value}`;
                    let map = {};
                    try { map = JSON.parse(localStorage.getItem('qm:snap') || '{}'); } catch (e) { }
                    if (map[key] && Date.now() - map[key] < 3600000) return;
                    fetch('{{ route('enr.snapshot') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(data),
                    }).then(() => {
                        map[key] = Date.now();
                        try { localStorage.setItem('qm:snap', JSON.stringify(map)); } catch (e) { }
                    }).catch(() => { });
                }

                // اقتراح محطات أبعد لما مفيش مقاعد من المحطة الحالية.
                // ALTS مرتّبة من الأقرب للأبعد؛ نعرض فقط ما هو أبعد من المحطة الحالية.
                function suggestFarther(currentName, currentEnr) {
                    const idx = ALTS.findIndex(a => a.enr === currentEnr);
                    const candidates = idx === -1 ? ALTS : ALTS.slice(idx + 1);
                    if (!candidates.length) return '';
                    const chips = candidates.map(a =>
                        `<button type="button" data-alt-enr="${a.enr}" data-alt-name="${a.name}"
                                    class="alt-board border border-rail-200 bg-rail-50 hover:bg-rail-100 text-rail-800 text-sm font-medium rounded-lg px-3 py-1.5 transition">
                                    ${a.name}
                                </button>`).join('');
                    return `
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-bold text-amber-800 mb-1">مفيش مقاعد من ${currentName}؟</p>
                                    <p class="text-xs text-amber-700 mb-3">القطار جاي من محطات أبعد — جرّب تحجز من محطة قبلها وانزل في وجهتك، يمكن يكون فيها مقاعد:</p>
                                    <div class="flex flex-wrap gap-2">${chips}</div>
                                </div>`;
                }

                // حالة فارغة: القطار مش شغّال في اليوم ده (إجازة/يوم إجازته) + اقتراح أيام قريبة.
                function dayChip(offset) {
                    const d = new Date(dateInput.value + 'T00:00');
                    if (isNaN(d)) return '';
                    d.setDate(d.getDate() + offset);
                    const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    let label;
                    try { label = new Intl.DateTimeFormat('ar-EG', { weekday: 'long', day: 'numeric', month: 'short' }).format(d); }
                    catch (e) { label = iso; }
                    return `<button type="button" data-go-date="${iso}"
                        class="border border-rail-200 bg-rail-50 hover:bg-rail-100 text-rail-800 text-sm font-bold rounded-xl px-3 py-1.5 transition">${label}</button>`;
                }
                function emptyState() {
                    return `<div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-white text-slate-300 ring-1 ring-slate-200">
                            <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8M7 16l-2 4m12-4l2 4"/></svg>
                        </div>
                        <p class="font-bold text-slate-700">القطار ده مش شغّال في اليوم ده</p>
                        <p class="text-sm text-slate-500 mt-1 leading-relaxed">ممكن يكون إجازة أو يوم إجازته، فمفيش رحلات على نظام الهيئة — حتى لو ميعاده لسه ماجاش. جرّب يوم تاني:</p>
                        <div class="flex flex-wrap gap-2 justify-center mt-3">${[1, 2, 3].map(dayChip).join('')}</div>
                    </div>`;
                }

                async function loadLive() {
                    if (typeof EnrLive === 'undefined') {
                        out.innerHTML = errBox('تعذّر تحميل أداة العرض.');
                        return;
                    }

                    const from = fromSel.value, to = toSel.value;
                    const name = nameOf(from), toName = nameOf(to);
                    const fo = orderOf(from), too = orderOf(to);

                    // لازم محطة القيام تبقى قبل محطة الوصول في المسار.
                    if (fo == null || too == null || fo >= too) {
                        out.innerHTML = errBox('اختار محطة قيام قبل محطة الوصول.');
                        return;
                    }

                    btn.disabled = true;
                    btn.textContent = 'جاري الجلب…';
                    out.innerHTML = SKELETON;

                    // مهلة زمنية حتى لا تظل الصفحة معلّقة لو تأخّر نظام الهيئة.
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 25000);

                    try {
                        const url = EnrLive.buildUrl(SEARCH_URL, { from, to, number: NUMBER, date: dateInput.value });
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, signal: controller.signal });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();

                        // عنوان يوضّح القطعة المعروضة لو مختلفة عن المسار الافتراضي.
                        const heading = (from !== ORIGIN_ENR || to !== TERMINAL_ENR)
                            ? `<p class="text-sm text-rail-700 font-bold mb-2">التوافر: ${name} ← ${toName}</p>`
                            : '';

                        // الهيئة بترجّع فاضي لو القطار مش شغّال في اليوم ده (إجازة مثلًا) — حتى لو الميعاد ماجاش.
                        const hasTrips = Array.isArray(data) && data.some(i => i.steps && i.steps[0]);
                        let html = heading + (hasTrips ? EnrLive.render(data) : emptyState());

                        // لو فيه رحلات لكن كلها بدون مقاعد: نبّهني أول ما يفضى كرسي + اقتراح محطات أبعد.
                        if (hasTrips && EnrLive.totalSeats(data) === 0) {
                            html += seatWatchCta();
                            html += suggestFarther(name, from);
                        }
                        out.innerHTML = html;

                        // نلتقط البيانات (مواعيد + أسعار) لتحديث الموقع تلقائيًا — مرة كل ساعة لكل مسار.
                        if (hasTrips) snapshot(data, from);

                        // اختيار محطة قيام أبعد من الاقتراح: نظبط القائمة ونعيد الجلب.
                        out.querySelectorAll('.alt-board').forEach(b =>
                            b.addEventListener('click', () => { selectFrom(b.dataset.altEnr); loadLive(); }));

                        // تفعيل مراقبة المقاعد.
                        out.querySelectorAll('[data-seatwatch]').forEach(b =>
                            b.addEventListener('click', () => onSeatWatch(b)));

                        // أزرار «جرّب يوم تاني» في الحالة الفارغة.

                        // أزرار «جرّب يوم تاني» في الحالة الفارغة.
                        out.querySelectorAll('[data-go-date]').forEach(b =>
                            b.addEventListener('click', () => { dateInput.value = b.dataset.goDate; loadLive(); }));
                    } catch (e) {
                        out.innerHTML = errBox(e.name === 'AbortError'
                            ? 'انتهت مهلة الاتصال .'
                            : `تعذّر  احضار البيانات (${e.message}).`);
                    } finally {
                        clearTimeout(timer);
                        btn.disabled = false;
                        btn.textContent = 'تحديث';
                    }
                }

                // يضبط قيمة قائمة محطة القيام (يضيف الخيار لو مش موجود).
                function selectFrom(enr) {
                    if (![...fromSel.options].some(o => o.value === enr)) {
                        const o = document.createElement('option');
                        o.value = enr; o.textContent = nameOf(enr) || enr;
                        fromSel.appendChild(o);
                    }
                    fromSel.value = enr;
                }

                btn.addEventListener('click', () => loadLive());
                dateInput.addEventListener('change', () => loadLive());
                fromSel.addEventListener('change', () => loadLive());
                toSel.addEventListener('change', () => loadLive());

                // نجلب التوافر اللحظي لمّا القسم يقرب من الشاشة فقط (أسرع تحميل + طلبات أقل).
                let loaded = false;
                const loadOnce = () => { if (!loaded) { loaded = true; loadLive(); } };
                const section = document.getElementById('live');

                function start() {
                    if ('IntersectionObserver' in window && section) {
                        const io = new IntersectionObserver((entries) => {
                            if (entries.some((e) => e.isIntersecting)) { loadOnce(); io.disconnect(); }
                        }, { rootMargin: '300px' });
                        io.observe(section);
                    } else {
                        loadOnce();
                    }
                    // لو جاي من إشعار المقاعد (#live) ننزل للقسم (وده هيشغّل الجلب).
                    if (location.hash === '#live') {
                        section?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        loadOnce();
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', start);
                } else {
                    start();
                }
            })();
        </script>
    @endif

    {{-- جدول المحطات (تفصيلي) --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <h2 class="font-bold flex items-center gap-2 flex-wrap">
            <x-icon name="station" class="w-5 h-5 text-rail-600" />
            جدول المحطات
            <span class="text-xs font-normal text-slate-400">({{ $scheduleStops->count() }} محطة)</span>
            @if ($validSegment)
                <span class="text-xs font-normal text-rail-600">— رحلتك: {{ $origin->name_ar }} ←
                    {{ $terminal->name_ar }}</span>
            @endif
        </h2>
        <p class="text-xs text-slate-400 mt-1 mb-1">السعر = حتى {{ $terminal?->name_ar }}</p>

        <ol class="mt-3">
            @foreach ($scheduleStops as $stop)
                @php
                    $isFirst = $loop->first;
                    $isLast = $loop->last;
                    $stationFare = $isLast ? null : $stationFares->get($stop->station_id);
                    $arr = \App\Support\Format::time($stop->arrival_time);
                    $dep = \App\Support\Format::time($stop->departure_time);
                @endphp
                <li class="flex items-stretch gap-3">
                    {{-- العمود الزمني (نقطة المحطة + الخط الواصل) --}}
                    <div class="relative flex flex-col items-center w-5 shrink-0">
                        @if ($isFirst)
                            <span class="mt-1.5 w-4 h-4 rounded-full bg-rail-600 ring-4 ring-rail-100 shrink-0"></span>
                        @elseif ($isLast)
                            <span class="mt-1 text-amber-500 shrink-0"><x-icon name="pin" class="w-5 h-5" /></span>
                        @else
                            <span class="mt-2 w-2.5 h-2.5 rounded-full bg-white border-2 border-rail-300 shrink-0"></span>
                        @endif
                        @unless ($isLast)
                            <span class="w-0.5 flex-1 bg-rail-200 my-1"></span>
                        @endunless
                    </div>

                    {{-- بيانات المحطة --}}
                    <div class="flex-1 min-w-0 pb-5">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('stations.show', $stop->station) }}"
                                class="font-bold {{ $isFirst || $isLast ? 'text-rail-800' : 'text-slate-700' }} hover:text-rail-600 transition truncate">
                                {{ $stop->station->name_ar }}
                            </a>
                            @if ($stationFare !== null)
                                <span
                                    class="shrink-0 text-xs font-bold bg-rail-50 text-rail-700 rounded-lg px-2 py-1 whitespace-nowrap">{{ number_format($stationFare) }}
                                    ج.م</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-x-4 mt-1 text-xs text-slate-500">
                            @if ($arr)
                                <span class="inline-flex items-center gap-1"><x-icon name="clock"
                                        class="w-3.5 h-3.5 text-slate-400" /> وصول <b class="text-slate-700">{{ $arr }}</b></span>
                            @endif
                            @if ($dep)
                                <span>قيام <b class="text-slate-700">{{ $dep }}</b></span>
                            @endif
                            @unless ($arr || $dep)
                                <span class="text-slate-300">— الميعاد غير متاح</span>
                            @endunless
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <a href="{{ route('report', ['type' => 'schedule', 'train' => $train->number]) }}"
            class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5" />
            ميعاد غلط؟ بلّغنا
        </a>
    </section>
@endsection