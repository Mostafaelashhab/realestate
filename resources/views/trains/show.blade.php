@extends('layouts.app')

@section('title', "قطار {$train->number}")

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
    
    </div>

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
    </section>

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

        <a href="{{ \App\Support\EgyptRailReference::bookingUrl($origin?->booking_name, $terminal?->booking_name, now()->toDateString()) }}"
            target="_blank" rel="noopener"
            class="mt-4 flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl px-4 py-3 transition">
            <x-icon name="ticket" class="w-5 h-5"/>
            احجز على الموقع الرسمي لهيئة السكة الحديد
        </a>

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
                                <span class="inline-flex items-center gap-1.5">
                                    @if ($loop->first)
                                        <x-icon name="dot" class="w-2 h-2 text-rail-600"/>
                                    @elseif ($loop->last)
                                        <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/>
                                    @endif
                                    {{ $stop->station->name_ar }}
                                </span>
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
                // محطات قيام أبعد على نفس القطار (الأقرب فالأبعد).
                const ALTS = @json($boardingAlternatives);

                const errBox = (msg) => `<p class="text-sm text-red-600">${msg} جرّب زر «تحديث» أو الحجز بالأعلى.</p>`;

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
                    out.innerHTML = '<p class="text-sm text-slate-400">جاري جلب البيانات</p>';

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
