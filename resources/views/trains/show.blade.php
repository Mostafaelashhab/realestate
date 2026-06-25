@extends('layouts.app')

@section('title', "قطار {$train->number}")
@section('og_title', "قطار {$train->number}" . ($origin && $terminal ? " — {$origin->name_ar} ← {$terminal->name_ar}" : ''))
@section('og_desc', trim((\App\Support\Format::time($depart) ? \App\Support\Format::time($depart).' ← '.\App\Support\Format::time($arrive).' · ' : '') . ($duration ? $duration.' · ' : '') . 'مواعيد وأسعار رحلتك على قطارات مصر.'))

@section('content')
    {{-- هوية القطار + ملخّص الرحلة (هيرو) --}}
    <section class="relative overflow-hidden bg-linear-to-br from-rail-800 via-rail-700 to-rail-600 text-white rounded-3xl p-5 mb-5 shadow-xl shadow-rail-800/25">
        {{-- زخرفة قضبان خفيفة --}}
        <svg class="absolute -top-8 -start-10 w-44 h-44 text-white/10" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100"/>
            <path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8"/>
        </svg>

        <div class="relative">
            {{-- الصف العلوي: الرقم + النوع + الحالة + أزرار --}}
            <div class="flex items-start gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="bg-white/15 ring-1 ring-white/20 text-base font-extrabold px-3 py-1 rounded-xl">قطار {{ $train->number }}</span>
                        @if ($train->active)
                            <span class="inline-flex items-center gap-1 text-xs bg-white/15 px-2 py-1 rounded-md">
                                <x-icon name="check" class="w-3.5 h-3.5"/> مؤكد
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
                        <x-icon name="share" class="w-5 h-5"/>
                    </button>
                    <button id="fav-btn" type="button" aria-label="إضافة للمفضلة"
                        class="w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 active:scale-90 text-white/80 transition">
                        <x-icon name="star" class="w-5 h-5"/>
                    </button>
                </div>
            </div>

            {{-- ملخّص الرحلة --}}
            <div class="mt-5 flex items-stretch gap-3">
                <div class="text-center min-w-0">
                    <div class="text-3xl font-extrabold whitespace-nowrap leading-none">{{ \App\Support\Format::time($depart) ?? '—' }}</div>
                    <div class="text-xs text-rail-50/80 mt-1.5 truncate">{{ $origin?->name_ar }}</div>
                </div>

                <div class="flex-1 flex flex-col items-center justify-center px-1">
                    @if ($duration)
                        <div class="text-[11px] bg-white/15 rounded-full px-2 py-0.5 mb-1.5 whitespace-nowrap">{{ $duration }}</div>
                    @endif
                    <div class="w-full flex items-center gap-1">
                        <x-icon name="dot" class="w-2.5 h-2.5 shrink-0 text-white"/>
                        <span class="flex-1 border-t-2 border-dashed border-white/40"></span>
                        <x-icon name="train" class="w-4 h-4 shrink-0 text-white"/>
                        <span class="flex-1 border-t-2 border-dashed border-white/40"></span>
                        <x-icon name="pin" class="w-3.5 h-3.5 shrink-0 text-amber-300"/>
                    </div>
                    @if ($validSegment)
                        <div class="text-[11px] mt-1.5 text-amber-200 font-bold">رحلتك</div>
                    @endif
                </div>

                <div class="text-center min-w-0">
                    <div class="text-3xl font-extrabold whitespace-nowrap leading-none">{{ \App\Support\Format::time($arrive) ?? '—' }}</div>
                    <div class="text-xs text-rail-50/80 mt-1.5 truncate">{{ $terminal?->name_ar }}</div>
                </div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const KEY = 'qm:fav';
            const num = @json($train->number);
            const label = @json(trim(($origin?->name_ar ? $origin->name_ar.' ← '.$terminal?->name_ar : ($train->type_label ?? ''))));
            const url = @json(request()->getRequestUri());
            const btn = document.getElementById('fav-btn');
            const get = () => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; } };
            const isFav = () => get().some(f => f.number === num);
            const paint = () => {
                const on = isFav();
                btn.classList.toggle('text-amber-400', on);
                btn.classList.toggle('text-white/80', ! on);
            };
            btn.addEventListener('click', () => {
                let list = get();
                list = isFav() ? list.filter(f => f.number !== num) : [{ number: num, label, url }, ...list].slice(0, 12);
                try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
                paint();
            });
            paint();
        })();
    </script>

    {{-- حالة القطر من الركّاب (crowdsourced) — مع تقدير من الجدول لو مفيش بلاغات --}}
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
            <h2 class="font-bold flex items-center gap-2"><x-icon name="alert" class="w-5 h-5 text-rail-600"/> حالة القطر دلوقتي</h2>
            <span class="text-xs text-slate-400 whitespace-nowrap">من الركّاب · آخر ٣ ساعات</span>
        </div>

        <div id="status-summary" class="mt-3">
            <div class="animate-pulse h-14 bg-slate-100 rounded-2xl"></div>
        </div>

        {{-- بلاغ سريع --}}
        <div class="mt-4 border-t border-slate-100 pt-4">
            <p class="text-sm font-medium mb-2">ركبت القطر ده؟ بلّغ غيرك:</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-report="on_time"
                    class="report-btn inline-flex items-center gap-1.5 border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 text-sm font-bold rounded-xl px-3.5 py-2 transition">
                    <x-icon name="check" class="w-4 h-4"/> في الموعد
                </button>
                <button type="button" data-report="delayed"
                    class="report-btn inline-flex items-center gap-1.5 border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 text-sm font-bold rounded-xl px-3.5 py-2 transition">
                    <x-icon name="clock" class="w-4 h-4"/> متأخر
                </button>
                <button type="button" data-report="cancelled"
                    class="report-btn inline-flex items-center gap-1.5 border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-sm font-bold rounded-xl px-3.5 py-2 transition">
                    <x-icon name="alert" class="w-4 h-4"/> اتلغى/وقف
                </button>
            </div>

            <div id="delay-row" hidden class="mt-3 flex items-center gap-2 flex-wrap">
                <input id="delay-min" type="number" min="0" max="600" inputmode="numeric" placeholder="التأخير بالدقايق"
                    class="w-36 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <input id="status-note" maxlength="200" placeholder="ملاحظة (اختياري)"
                    class="flex-1 min-w-40 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <button id="delay-send" type="button"
                    class="bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold rounded-xl px-4 py-2 transition">إرسال</button>
            </div>
            <p id="status-msg" hidden class="text-sm mt-2"></p>
        </div>
    </section>

    <script>
        (() => {
            const SHOW_URL = @json(route('trains.status', $train));
            const STORE_URL = @json(route('trains.status.store', $train));
            const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
            const summaryEl = document.getElementById('status-summary');
            const delayRow = document.getElementById('delay-row');
            const msgEl = document.getElementById('status-msg');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            const STYLE = {
                on_time: ['bg-emerald-50', 'text-emerald-800', 'border-emerald-200', '✓'],
                delayed: ['bg-amber-50', 'text-amber-800', 'border-amber-200', '⏱'],
                cancelled: ['bg-red-50', 'text-red-700', 'border-red-200', '✕'],
            };

            // — تقدير مكان القطر حسب الجدول (وقت القاهرة الحالي) — بديل لما مفيش بلاغات —
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
                const firstDep = eff[0].out, lastArr = eff[eff.length - 1].in;
                if (lastArr <= firstDep) return null; // رحلة عابرة لمنتصف الليل — نتجنّب تقدير غلط
                const now = cairoNowMin();
                if (now < firstDep) {
                    const diff = firstDep - now;
                    return { text: `لسه ما قامش — بيقوم ${fmtMin(firstDep)}`, sub: `بعد ~${diff} دقيقة من ${eff[0].name}` };
                }
                if (now >= lastArr) return { text: 'المفروض الرحلة خلصت', sub: `الوصول حوالي ${fmtMin(lastArr)}` };
                for (let i = 0; i < eff.length; i++) {
                    if (now >= eff[i].in && now <= eff[i].out) return { text: `المفروض دلوقتي في محطة ${eff[i].name}`, sub: '' };
                    if (i < eff.length - 1 && now > eff[i].out && now < eff[i + 1].in)
                        return { text: `المفروض دلوقتي بين ${eff[i].name} و ${eff[i + 1].name}`, sub: `الوصول ${eff[i + 1].name} ${fmtMin(eff[i + 1].in)}` };
                }
                return { text: 'القطار في الطريق', sub: '' };
            }

            function render(s) {
                if (!s || !s.count) {
                    const est = scheduleEstimate();
                    if (est) {
                        summaryEl.innerHTML = `<div class="rounded-2xl bg-rail-50 border border-rail-200 p-3">
                            <div class="flex items-center gap-2"><span class="text-lg leading-none">🚆</span><span class="font-extrabold text-rail-800">${esc(est.text)}</span></div>
                            <p class="text-xs text-rail-700/70 mt-1">تقدير حسب الجدول — مش بلاغ فعلي${est.sub ? ' · ' + esc(est.sub) : ''}</p>
                        </div>`;
                    } else {
                        summaryEl.innerHTML = '<div class="rounded-2xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-500">لسه مفيش بلاغات في آخر ٣ ساعات — كن أول واحد يبلّغ.</div>';
                    }
                    return;
                }
                const c = STYLE[s.status] || STYLE.on_time;
                const notes = (s.recent || []).filter(r => r.note).slice(0, 3)
                    .map(r => `<li class="text-xs text-slate-500">• ${esc(r.note)} <span class="text-slate-400">(${esc(r.ago)})</span></li>`).join('');
                summaryEl.innerHTML = `<div class="rounded-2xl ${c[0]} border ${c[2]} p-3">
                    <div class="flex items-center gap-2"><span class="text-lg leading-none">${c[3]}</span><span class="font-extrabold ${c[1]}">${esc(s.headline)}</span></div>
                    <p class="text-xs ${c[1]} opacity-80 mt-1">بناءً على ${s.count} بلاغ · آخر بلاغ ${esc(s.last_ago)}</p>
                    ${notes ? `<ul class="mt-2 space-y-0.5">${notes}</ul>` : ''}
                </div>`;
            }

            function showMsg(text, ok = true) {
                msgEl.textContent = text;
                msgEl.className = 'text-sm mt-2 ' + (ok ? 'text-emerald-700' : 'text-red-600');
                msgEl.hidden = false;
            }

            async function send(status, extra = {}) {
                try {
                    const res = await fetch(STORE_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify({ status, ...extra }),
                    });
                    if (res.status === 429) { showMsg('بلّغت كتير في وقت قصير — استنى شوية.', false); return; }
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    render(await res.json());
                    delayRow.hidden = true;
                    document.getElementById('delay-min').value = '';
                    document.getElementById('status-note').value = '';
                    showMsg('شكرًا! بلاغك اتسجّل ✓');
                } catch (e) { showMsg('تعذّر إرسال البلاغ، حاول تاني.', false); }
            }

            document.querySelectorAll('.report-btn').forEach(b => b.addEventListener('click', () => {
                const status = b.dataset.report;
                msgEl.hidden = true;
                if (status === 'delayed') {
                    delayRow.hidden = !delayRow.hidden;
                    if (!delayRow.hidden) document.getElementById('delay-min').focus();
                    return;
                }
                delayRow.hidden = true;
                send(status);
            }));

            document.getElementById('delay-send').addEventListener('click', () => {
                const min = document.getElementById('delay-min').value;
                const note = document.getElementById('status-note').value.trim();
                send('delayed', {
                    delay_minutes: min !== '' ? Number(min) : null,
                    note: note || null,
                });
            });

            // تحميل الملخّص الحالي (لايف، من غير كاش)
            fetch(SHOW_URL, { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : null).then(render).catch(() => render(null));
        })();
    </script>

    {{-- الدرجات والأسعار الرسمية --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <div class="flex items-baseline justify-between gap-2 mb-1">
            <h2 class="font-bold flex items-center gap-2"><x-icon name="ticket" class="w-5 h-5 text-rail-600"/> الأسعار الرسمية</h2>
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
                    <div class="relative rounded-2xl border p-3 {{ $isCheapest ? 'border-rail-300 bg-rail-50 ring-1 ring-rail-200' : 'border-slate-200' }}">
                        @if ($isCheapest && $fares->count() > 1)
                            <span class="absolute -top-2 end-2 text-[10px] font-bold bg-rail-600 text-white rounded-full px-2 py-0.5">الأرخص</span>
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
                <p class="text-sm text-slate-500">الأسعار الرسمية للمسار ده لسه مش محمّلة — هتلاقيها تحت في «المقاعد المتاحة».</p>
            </div>
        @endif

        <a href="{{ route('report', ['type' => 'price', 'train' => $train->number]) }}"
            class="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5"/>
            السعر غلط؟ بلّغنا
        </a>
    </section>

    {{-- التوافر اللحظي الرسمي — يُجلب تلقائيًا عند فتح الصفحة --}}
    @if ($origin?->enr_id && $terminal?->enr_id)
        <section id="live" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5 scroll-mt-20">
            <h2 class="font-bold flex items-center gap-2">
                <span class="relative flex w-2.5 h-2.5">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-rail-400 opacity-75 animate-ping"></span>
                    <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-rail-600"></span>
                </span>
                المقاعد المتاحة دلوقتي
            </h2>
            <p class="text-xs text-slate-400 mt-1 mb-3">مباشر من نظام الهيئة — مواعيد دقيقة، عربات، درجات، أسعار، ومقاعد فاضية.</p>

            <div class="flex items-center gap-2 flex-wrap mb-3">
                <div class="relative flex-1 min-w-40">
                    <x-icon name="calendar" class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none"/>
                    <input type="date" id="live-date" value="{{ now()->toDateString() }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-2 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                </div>
                <button id="live-btn"
                    class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold rounded-xl px-4 py-2 transition">
                    <x-icon name="refresh" class="w-4 h-4"/> تحديث
                </button>
            </div>
            <div id="live-result"></div>
        </section>

        <script>
            (() => {
                const btn = document.getElementById('live-btn');
                const out = document.getElementById('live-result');
                const dateInput = document.getElementById('live-date');
                // لو جاي من إشعار/رابط بتاريخ محدد، نستخدمه.
                const _qDate = new URLSearchParams(location.search).get('date');
                if (_qDate && /^\d{4}-\d{2}-\d{2}$/.test(_qDate)) dateInput.value = _qDate;
                const SEARCH_URL = @json(config('enr.search_url'));
                const TO = @json($terminal->enr_id);
                const NUMBER = @json($train->number);
                const ORIGIN_ENR = @json($origin->enr_id);
                const ORIGIN_NAME = @json($origin->name_ar);
                const SKELETON = '<div class="animate-pulse space-y-3">' +
                    '<div class="bg-white rounded-lg border border-slate-200 p-3">' +
                    '<div class="flex gap-2 mb-3"><div class="h-5 w-14 bg-slate-200 rounded"></div><div class="h-5 w-14 bg-slate-200 rounded"></div><div class="h-5 w-20 bg-slate-200 rounded"></div></div>' +
                    '<div class="flex flex-wrap gap-1.5">' + Array(16).fill('<div class="w-7 h-9 bg-slate-200 rounded-md"></div>').join('') + '</div>' +
                    '</div></div>';
                // محطات قيام أبعد على نفس القطار (الأقرب فالأبعد).
                const ALTS = @json($boardingAlternatives);

                const errBox = (msg) => `<p class="text-sm text-red-600">${msg} جرّب زر «تحديث» أو الحجز بالأعلى.</p>`;
                const CSRF = document.querySelector('meta[name=csrf-token]')?.content;

                // إرسال رد الهيئة للموقع لتحديث المواعيد/الأسعار — مرّة كل ساعة لكل (مسار+تاريخ).
                function snapshot(data, from) {
                    const key = `${NUMBER}:${from}:${dateInput.value}`;
                    let map = {};
                    try { map = JSON.parse(localStorage.getItem('qm:snap') || '{}'); } catch (e) {}
                    if (map[key] && Date.now() - map[key] < 3600000) return;
                    fetch('{{ route('enr.snapshot') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(data),
                    }).then(() => {
                        map[key] = Date.now();
                        try { localStorage.setItem('qm:snap', JSON.stringify(map)); } catch (e) {}
                    }).catch(() => {});
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

                async function loadLive(fromEnr, fromName) {
                    if (typeof EnrLive === 'undefined') {
                        out.innerHTML = errBox('تعذّر تحميل أداة العرض.');
                        return;
                    }

                    const from = fromEnr || ORIGIN_ENR;
                    const name = fromName || ORIGIN_NAME;

                    btn.disabled = true;
                    btn.textContent = 'جاري الجلب…';
                    out.innerHTML = SKELETON;

                    // مهلة زمنية حتى لا تظل الصفحة معلّقة لو تأخّر نظام الهيئة.
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 25000);

                    try {
                        const url = EnrLive.buildUrl(SEARCH_URL, { from, to: TO, number: NUMBER, date: dateInput.value });
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, signal: controller.signal });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();

                        const heading = fromEnr
                            ? `<p class="text-sm text-rail-700 font-bold mb-2">التوافر من ${name} (محطة أبعد)</p>`
                            : '';
                        let html = heading + EnrLive.render(data);

                        // نقترح محطات أبعد فقط لو فيه رحلات فعلاً لكن كلها بدون مقاعد —
                        // مش لما القطار يكون معاده عدّى أو مش شغّال في اليوم ده (لا رحلات أصلًا).
                        const hasTrips = Array.isArray(data) && data.some(i => i.steps && i.steps[0]);
                        if (hasTrips && EnrLive.totalSeats(data) === 0) {
                            html += suggestFarther(name, from);
                        }
                        out.innerHTML = html;

                        // نلتقط البيانات (مواعيد + أسعار) لتحديث الموقع تلقائيًا — مرة كل ساعة لكل مسار.
                        if (hasTrips) snapshot(data, from);

                        out.querySelectorAll('.alt-board').forEach(b =>
                            b.addEventListener('click', () => loadLive(b.dataset.altEnr, b.dataset.altName)));
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

                btn.addEventListener('click', () => loadLive());
                dateInput.addEventListener('change', () => loadLive());

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
            <x-icon name="station" class="w-5 h-5 text-rail-600"/>
            جدول المحطات
            <span class="text-xs font-normal text-slate-400">({{ $scheduleStops->count() }} محطة)</span>
            @if ($validSegment)
                <span class="text-xs font-normal text-rail-600">— رحلتك: {{ $origin->name_ar }} ← {{ $terminal->name_ar }}</span>
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
                            <span class="mt-1 text-amber-500 shrink-0"><x-icon name="pin" class="w-5 h-5"/></span>
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
                                <span class="shrink-0 text-xs font-bold bg-rail-50 text-rail-700 rounded-lg px-2 py-1 whitespace-nowrap">{{ number_format($stationFare) }} ج.م</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-x-4 mt-1 text-xs text-slate-500">
                            @if ($arr)
                                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="w-3.5 h-3.5 text-slate-400"/> وصول <b class="text-slate-700">{{ $arr }}</b></span>
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
            <x-icon name="flag" class="w-3.5 h-3.5"/>
            ميعاد غلط؟ بلّغنا
        </a>
    </section>
@endsection
