@extends('layouts.app')

@section('title', "قطار {$train->number}")
@section('og_title', "قطار {$train->number}" . ($origin && $terminal ? " — {$origin->name_ar} ← {$terminal->name_ar}" : ''))
@section('og_desc', trim((\App\Support\Format::time($depart) ? \App\Support\Format::time($depart).' ← '.\App\Support\Format::time($arrive).' · ' : '') . ($duration ? $duration.' · ' : '') . 'مواعيد وأسعار رحلتك على قطارات مصر.'))

@section('content')
    {{-- هوية القطار --}}
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <span class="bg-rail-700 text-white text-sm font-bold px-3 py-1 rounded-lg">قطار {{ $train->number }}</span>
        <span class="text-slate-600 text-sm">{{ $train->type_label }}</span>
        @if ($train->active)
            <span class="inline-flex items-center gap-1 text-xs bg-emerald-50 text-emerald-700 px-2 py-1 rounded">
                <x-icon name="check" class="w-3.5 h-3.5"/> مؤكد التشغيل
            </span>
        @endif

        <div class="ms-auto flex items-center gap-1.5">
            <button type="button" data-share
                data-share-title="قطار {{ $train->number }}@if ($origin && $terminal) — {{ $origin->name_ar }} ← {{ $terminal->name_ar }}@endif"
                aria-label="مشاركة"
                class="w-9 h-9 grid place-items-center rounded-full ring-1 ring-slate-200 text-slate-400 hover:bg-rail-50 hover:text-rail-600 transition">
                <x-icon name="share" class="w-5 h-5"/>
            </button>
            <button id="fav-btn" type="button" aria-label="إضافة للمفضلة"
                class="w-9 h-9 grid place-items-center rounded-full ring-1 ring-slate-200 text-slate-300 hover:bg-amber-50 transition">
                <x-icon name="star" class="w-5 h-5"/>
            </button>
        </div>
    </div>

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
                btn.classList.toggle('text-amber-500', on);
                btn.classList.toggle('ring-amber-200', on);
                btn.classList.toggle('text-slate-300', !on);
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

    {{-- ملخّص الرحلة --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <div class="flex items-center justify-between gap-4">
            <div class="text-center min-w-0">
                <div class="text-3xl font-extrabold whitespace-nowrap">{{ \App\Support\Format::time($depart) ?? '—' }}</div>
                <div class="text-sm text-slate-500 truncate">{{ $origin?->name_ar }}</div>
            </div>

            <div class="flex-1 flex flex-col items-center text-slate-400 px-1">
                @if ($duration)
                    <div class="text-xs mb-1">{{ $duration }}</div>
                @endif
                <div class="w-full flex items-center gap-1 text-rail-500">
                    <x-icon name="dot" class="w-2.5 h-2.5 shrink-0"/>
                    <span class="flex-1 border-t border-dashed border-slate-300"></span>
                    <x-icon name="train" class="w-4 h-4 shrink-0 text-slate-400"/>
                    <span class="flex-1 border-t border-dashed border-slate-300"></span>
                    <x-icon name="pin" class="w-3.5 h-3.5 shrink-0 text-amber-500"/>
                </div>
                @if ($validSegment)
                    <div class="text-[11px] mt-1 text-rail-600">رحلتك</div>
                @endif
            </div>

            <div class="text-center min-w-0">
                <div class="text-3xl font-extrabold whitespace-nowrap">{{ \App\Support\Format::time($arrive) ?? '—' }}</div>
                <div class="text-sm text-slate-500 truncate">{{ $terminal?->name_ar }}</div>
            </div>
        </div>

        @if (config('push.vapid_public'))
            @guest
                <a href="{{ route('login') }}" class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-rail-700 bg-rail-50 hover:bg-rail-100 rounded-2xl px-4 py-2.5 transition">
                    <x-icon name="user" class="w-4 h-4"/> سجّل دخول لتفعيل التنبيه قبل الميعاد
                </a>
            @else
            <button id="notify-btn" type="button"
                class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-rail-700 bg-rail-50 hover:bg-rail-100 rounded-2xl px-4 py-2.5 transition">
                <x-icon name="alert" class="w-4 h-4"/> نبّهني قبل ميعاد القطار
            </button>
            <script>
                (() => {
                    const btn = document.getElementById('notify-btn');
                    const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
                    btn.addEventListener('click', async () => {
                        btn.disabled = true; btn.textContent = 'جاري التفعيل…';
                        const endpoint = window.QMPush && await window.QMPush.subscribe(@json($train->number));
                        if (!endpoint) { btn.disabled = false; btn.textContent = 'لازم تسمح بالإشعارات'; return; }
                        try {
                            const res = await fetch(@json(route('trains.reminder', $train)), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                                body: JSON.stringify({ endpoint, from_station_id: @json($origin?->id) }),
                            });
                            btn.textContent = res.ok ? 'هنبّهك قبل الميعاد ✓' : 'تعذّر تفعيل التنبيه';
                        } catch (e) { btn.disabled = false; btn.textContent = 'تعذّر تفعيل التنبيه'; }
                    });
                })();
            </script>
            @endguest
        @endif
    </section>

    

    {{-- تنبيه الراكب الواقف: المقاعد المتاحة قبل القيام --}}
    @if (config('push.vapid_public'))
        <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
            <h2 class="font-bold mb-1 flex items-center gap-2"><x-icon name="alert" class="w-5 h-5 text-amber-500"/> واقف ومعندكش مقعد؟</h2>
            <p class="text-xs text-slate-400 mb-3">هنبّهك بالمقاعد اللي لسه متباعتش قبل قيام القطار من محطتك بـ ٥ دقائق — يمكن تلاقي مكان.</p>

            @guest
                <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 text-sm font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-2xl px-4 py-2.5 transition">
                    <x-icon name="user" class="w-4 h-4"/> سجّل دخول لتفعيل تنبيه المقاعد
                </a>
            @else
            <div id="sa-form" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <label class="text-xs text-slate-500">محطة ركوبك
                        <select id="sa-from" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rail-500/30">
                            @foreach ($scheduleStops as $s)
                                <option value="{{ $s->station_id }}" @selected($origin && $s->station_id === $origin->id)>{{ $s->station->name_ar }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs text-slate-500">وجهتك
                        <select id="sa-to" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rail-500/30">
                            @foreach ($scheduleStops as $s)
                                <option value="{{ $s->station_id }}" @selected($terminal && $s->station_id === $terminal->id)>{{ $s->station->name_ar }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <button id="sa-activate" type="button"
                    class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 active:scale-[.99] text-white font-bold rounded-2xl px-4 py-3 transition">
                    <x-icon name="alert" class="w-5 h-5"/> فعّل تنبيه المقاعد
                </button>
            </div>
            <p id="sa-msg" hidden class="text-sm font-bold mt-2"></p>
            <a href="{{ route('alerts.mine') }}" class="mt-2 inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
                <x-icon name="chevron-right" class="w-3.5 h-3.5"/> شوف طلباتي
            </a>
            @endguest
        </section>

        @auth
        <script>
            (() => {
                const CSRF = document.querySelector('meta[name=csrf-token]')?.content;
                const URL = @json(route('trains.standing', $train));
                const btn = document.getElementById('sa-activate');
                const msg = document.getElementById('sa-msg');
                const fromSel = document.getElementById('sa-from');
                const toSel = document.getElementById('sa-to');

                btn.addEventListener('click', async () => {
                    msg.hidden = true;
                    if (fromSel.value === toSel.value) { msg.hidden = false; msg.className = 'text-sm font-bold mt-2 text-red-600'; msg.textContent = 'اختار محطتين مختلفتين.'; return; }
                    btn.disabled = true; btn.textContent = 'جاري التفعيل…';
                    const endpoint = window.QMPush && await window.QMPush.subscribe(@json($train->number));
                    if (!endpoint) { msg.hidden = false; msg.className = 'text-sm font-bold mt-2 text-red-600'; msg.textContent = 'لازم تسمح بالإشعارات.'; btn.disabled = false; btn.textContent = 'فعّل تنبيه المقاعد'; return; }
                    try {
                        const res = await fetch(URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                            body: JSON.stringify({ from_station_id: +fromSel.value, to_station_id: +toSel.value, endpoint }),
                        });
                        const j = await res.json();
                        msg.hidden = false;
                        msg.className = 'text-sm font-bold mt-2 ' + (res.ok ? 'text-rail-700' : 'text-red-600');
                        msg.textContent = res.ok ? (j.message || 'تم تفعيل التنبيه ✓') : (j.error || 'تعذّر التفعيل.');
                        if (res.ok) document.getElementById('sa-form').hidden = true;
                    } catch (e) { msg.hidden = false; msg.className = 'text-sm font-bold mt-2 text-red-600'; msg.textContent = 'تعذّر التفعيل، جرّب تاني.'; }
                    btn.disabled = false; btn.textContent = 'فعّل تنبيه المقاعد';
                });
            })();
        </script>
        @endauth
    @endif

    {{-- الدرجات والأسعار الرسمية --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <h2 class="font-bold mb-1">الأسعار الرسمية
            @if ($origin && $terminal)
                <span class="text-xs font-normal text-slate-400">({{ $origin->name_ar }} ← {{ $terminal->name_ar }})</span>
            @endif
        </h2>

        @if ($fares->isNotEmpty())
            <p class="text-xs text-slate-400 mb-3">من نظام الحجز الرسمي لهيئة السكة الحديد.</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($fares as $fare)
                    <div class="border border-emerald-200 bg-emerald-50 rounded-lg px-3 py-2 text-sm">
                        <div class="font-medium text-emerald-900">{{ $fare->class_ar }}</div>
                        <div class="text-xs text-emerald-700">{{ number_format($fare->price) }} ج.م</div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-500 mb-3">الأسعار الرسمية لهذا المسار غير محمّلة بعد — اعرفها من زر الحجز.</p>
        @endif

            

        <a href="{{ route('report', ['type' => 'price', 'train' => $train->number]) }}"
            class="mt-2 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5"/>
            السعر غلط؟ بلّغنا
        </a>
    </section>

    {{-- جدول المحطات --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
        <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
            <h2 class="font-bold">
                جدول المحطات ({{ $scheduleStops->count() }} محطة)
                @if ($validSegment)
                    <span class="text-xs font-normal text-rail-600">— رحلتك: {{ $origin->name_ar }} ← {{ $terminal->name_ar }}</span>
                @endif
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200 text-right">
                        <th class="py-2 font-medium">المحطة</th>
                        <th class="py-2 font-medium">
                            <span class="inline-flex items-center gap-1"><x-icon name="clock" class="w-3.5 h-3.5"/> الوصول</span>
                        </th>
                        <th class="py-2 font-medium">القيام</th>
                        <th class="py-2 font-medium whitespace-nowrap text-rail-700">السعر حتى {{ $terminal?->name_ar }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scheduleStops as $stop)
                        @php
                            $edge = $loop->first || $loop->last;
                            $stationFare = $loop->last ? null : $stationFares->get($stop->station_id);
                        @endphp
                        <tr class="border-b border-slate-50 {{ $edge ? 'text-rail-800' : '' }}">
                            <td class="py-2.5 font-medium">
                                <a href="{{ route('stations.show', $stop->station) }}" class="inline-flex items-center gap-1.5 hover:text-rail-600 transition">
                                    @if ($loop->first)
                                        <x-icon name="dot" class="w-2 h-2 text-rail-600"/>
                                    @elseif ($loop->last)
                                        <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/>
                                    @endif
                                    {{ $stop->station->name_ar }}
                                </a>
                            </td>
                            <td class="py-2.5 whitespace-nowrap">{{ \App\Support\Format::time($stop->arrival_time) ?? '—' }}</td>
                            <td class="py-2.5 whitespace-nowrap">{{ \App\Support\Format::time($stop->departure_time) ?? '—' }}</td>
                            <td class="py-2.5 whitespace-nowrap">
                                @if ($stationFare !== null)
                                    <span class="font-bold text-rail-700">{{ number_format($stationFare) }}</span> <span class="text-xs text-slate-400">ج.م</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <a href="{{ route('report', ['type' => 'schedule', 'train' => $train->number]) }}"
            class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5"/>
            ميعاد غلط؟ بلّغنا
        </a>
    </section>

    {{-- التوافر اللحظي الرسمي — يُجلب تلقائيًا عند فتح الصفحة --}}
    @if ($origin?->enr_id && $terminal?->enr_id)
        <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5">
            <h2 class="font-bold mb-1">المواعيد والمقاعد المتاحة (لحظي)</h2>
            <p class="text-xs text-slate-400 mb-3">مباشرة - مواعيد دقيقة، عربات، درجات، أسعار، ومقاعد متاحة.</p>

            <div class="flex items-center gap-3 flex-wrap mb-3">
                <input type="date" id="live-date" value="{{ now()->toDateString() }}"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <button id="live-btn"
                    class="bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold rounded-lg px-4 py-1.5 transition">
                    تحديث
                </button>
            </div>
            <div id="live-result"></div>
        </section>

        <script>
            (() => {
                const btn = document.getElementById('live-btn');
                const out = document.getElementById('live-result');
                const dateInput = document.getElementById('live-date');
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

                // app.js (EnrLive) يُحمّل كموديول مؤجّل، فننتظر اكتمال تحميله قبل الجلب التلقائي.
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => loadLive());
                } else {
                    loadLive();
                }
            })();
        </script>
    @endif
@endsection
