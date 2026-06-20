@extends('layouts.app')

@section('title', "قطار {$train->number}")

@section('content')
    @php
        $points = $train->stops
            ->filter(fn ($s) => $s->map_x !== null && $s->map_y !== null)
            ->map(fn ($s) => ['x' => $s->map_x, 'y' => $s->map_y, 'name' => $s->station->name_ar])
            ->values();

        $hasMap = $points->count() > 1;
        if ($hasMap) {
            $pad = 8;
            $minX = $points->min('x') - $pad; $maxX = $points->max('x') + $pad;
            $minY = $points->min('y') - $pad; $maxY = $points->max('y') + $pad;
            $vbW = max(1, $maxX - $minX); $vbH = max(1, $maxY - $minY);
        }

        $statusStyles = [
            'running' => ['bg-emerald-100 text-emerald-800', 'في الطريق'],
            'at_station' => ['bg-sky-100 text-sky-800', 'في المحطة'],
            'before' => ['bg-amber-100 text-amber-800', 'لم يتحرك بعد'],
            'idle' => ['bg-slate-100 text-slate-600', 'خارج الخدمة الآن'],
            'unknown' => ['bg-slate-100 text-slate-600', 'غير معروف'],
        ];
        [$badgeClass, $badgeText] = $statusStyles[$position['status']] ?? $statusStyles['unknown'];
    @endphp

    <div class="flex items-center gap-3 mb-4 flex-wrap">
        <span class="bg-rail-700 text-white text-sm font-bold px-3 py-1 rounded-lg">قطار {{ $train->number }}</span>
        <span class="text-slate-600">{{ $train->type_label }}</span>
        @if ($train->active)
            <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-1 rounded">✅ مؤكد التشغيل</span>
        @endif
        @if ($train->source)
            <span class="text-xs text-slate-400 ms-auto">المصدر: {{ $train->source }}@if ($train->source_updated_at) — تحديث {{ $train->source_updated_at->format('Y/m/d') }}@endif</span>
        @endif
    </div>

    {{-- بطاقة الموقع التقديري --}}
    <section class="bg-white rounded-3xl shadow-sm p-5 mb-5">
        <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
            <h2 class="font-bold flex items-center gap-2">📍 القطر فين دلوقتي؟
                <span class="text-[11px] font-normal text-slate-400">(تقدير محسوب من الجدول)</span>
            </h2>
            <span id="status-badge" class="text-xs font-bold px-2.5 py-1 rounded-full {{ $badgeClass }}">{{ $badgeText }}</span>
        </div>

        <p id="position-message" class="text-slate-700 mb-3">{{ $position['message'] }}</p>

        @if ($position['overall_progress'] !== null)
            <div class="mb-3">
                <div class="flex justify-between text-xs text-slate-500 mb-1">
                    <span>{{ $train->stops->first()->station->name_ar }}</span>
                    <span id="progress-label">{{ $position['overall_progress'] }}%</span>
                    <span>{{ $train->stops->last()->station->name_ar }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div id="progress-bar" class="bg-rail-600 h-2.5 rounded-full transition-all" style="width: {{ $position['overall_progress'] }}%"></div>
                </div>
            </div>
        @endif

        @if ($hasMap)
            <div class="mt-3 bg-slate-50 rounded-xl border border-slate-200 p-2">
                <svg viewBox="{{ $minX }} {{ $minY }} {{ $vbW }} {{ $vbH }}" class="w-full" style="max-height: 360px" preserveAspectRatio="xMidYMid meet">
                    <polyline points="@foreach ($points as $p){{ $p['x'] }},{{ $p['y'] }} @endforeach"
                        fill="none" stroke="#0b6340" stroke-width="1.2" stroke-linejoin="round" />
                    @foreach ($points as $p)
                        <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="1.6" fill="#fff" stroke="#0b6340" stroke-width="0.8">
                            <title>{{ $p['name'] }}</title>
                        </circle>
                    @endforeach
                    <circle id="train-dot" r="2.6" fill="#dc2626" stroke="#fff" stroke-width="0.8"
                        style="display:none"></circle>
                </svg>
                <p class="text-[11px] text-slate-400 text-center mt-1">مخطط الخط (تقريبي) — النقطة الحمراء موقع القطار التقديري</p>
            </div>
        @endif
    </section>

    {{-- جدول المحطات --}}
    <section class="bg-white rounded-3xl shadow-sm p-5 mb-5">
        <h2 class="font-bold mb-3">
            جدول المحطات والمواعيد ({{ $scheduleStops->count() }} محطة)
            @if ($validSegment)
                <span class="text-xs font-normal text-rail-600">— رحلتك: {{ $origin->name_ar }} ← {{ $terminal->name_ar }}</span>
            @endif
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-slate-500 border-b border-slate-200 text-right">
                        <th class="py-2 font-medium">المحطة</th>
                        <th class="py-2 font-medium">الوصول</th>
                        <th class="py-2 font-medium">القيام</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scheduleStops as $stop)
                        <tr class="border-b border-slate-50">
                            <td class="py-2.5 font-medium">{{ $stop->station->name_ar }}</td>
                            <td class="py-2.5 whitespace-nowrap">{{ \App\Support\Format::time($stop->arrival_time) ?? '—' }}</td>
                            <td class="py-2.5 whitespace-nowrap">{{ \App\Support\Format::time($stop->departure_time) ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- الدرجات والأسعار الرسمية --}}
    <section class="bg-white rounded-3xl shadow-sm p-5">
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
            🎫 احجز على الموقع الرسمي لهيئة السكة الحديد
        </a>
    </section>

    {{-- التوافر اللحظي الرسمي (اختياري، بضغطة المستخدم) --}}
    @if ($origin?->enr_id && $terminal?->enr_id)
        <section class="bg-white rounded-3xl shadow-sm p-5 mt-5">
            <h2 class="font-bold mb-1">المواعيد والمقاعد المتاحة (لحظي)</h2>
            <p class="text-xs text-slate-400 mb-3">يُجلب مباشرة من نظام الهيئة عند الطلب — مواعيد دقيقة، عربات، أسعار، ومقاعد متاحة.</p>

            <div class="flex items-center gap-3 flex-wrap mb-3">
                <input type="date" id="live-date" value="{{ now()->addDay()->toDateString() }}"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <button id="live-btn"
                    class="bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold rounded-lg px-4 py-1.5 transition">
                    اعرض التوافر الرسمي
                </button>
            </div>
            <div id="live-result"></div>
        </section>

        <script>
            document.getElementById('live-btn').addEventListener('click', async function () {
                const out = document.getElementById('live-result');
                const date = document.getElementById('live-date').value;
                this.disabled = true;
                this.textContent = 'جاري الجلب…';
                out.innerHTML = '';

                const url = EnrLive.buildUrl(@json(config('enr.search_url')), {
                    from: @json($origin->enr_id), to: @json($terminal->enr_id),
                    number: @json($train->number), date,
                });

                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    out.innerHTML = EnrLive.render(await res.json());
                } catch (e) {
                    out.innerHTML = `<p class="text-sm text-red-600">تعذّر الجلب من نظام الهيئة (${e.message}). جرّب زر الحجز بالأعلى.</p>`;
                }
                this.disabled = false;
                this.textContent = 'تحديث';
            });
        </script>
    @endif

    @if ($hasMap)
        <script>
            const positionUrl = "{{ route('trains.position', $train) }}";
            const dot = document.getElementById('train-dot');

            function render(pos) {
                document.getElementById('position-message').textContent = pos.message;
                const bar = document.getElementById('progress-bar');
                if (pos.overall_progress !== null && bar) {
                    bar.style.width = pos.overall_progress + '%';
                    document.getElementById('progress-label').textContent = pos.overall_progress + '%';
                }
                if (pos.map_x !== null && pos.map_y !== null) {
                    dot.setAttribute('cx', pos.map_x);
                    dot.setAttribute('cy', pos.map_y);
                    dot.style.display = '';
                } else {
                    dot.style.display = 'none';
                }
            }

            render(@json($position));
            setInterval(() => {
                fetch(positionUrl).then(r => r.json()).then(render).catch(() => {});
            }, 30000);
        </script>
    @endif
@endsection
