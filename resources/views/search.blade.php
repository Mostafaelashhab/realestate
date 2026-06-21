@extends('layouts.app')

@section('title', "قطارات {$from->name_ar} ← {$to->name_ar}")
@section('og_title', "قطارات {$from->name_ar} ← {$to->name_ar}")
@section('og_desc', "{$date->translatedFormat('l j F Y')} — {$results->count()} قطار. مواعيد وأسعار القطارات بين المحطتين.")

@section('content')
    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="text-xl font-bold">{{ $from->name_ar }} <span class="text-slate-400">←</span> {{ $to->name_ar }}</h1>
            <p class="text-sm text-slate-500">{{ $date->translatedFormat('l j F Y') }} — {{ $results->count() }} قطار</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" data-share data-share-title="قطارات {{ $from->name_ar }} ← {{ $to->name_ar }}"
                aria-label="مشاركة"
                class="w-9 h-9 grid place-items-center rounded-full ring-1 ring-slate-200 text-slate-400 hover:bg-rail-50 hover:text-rail-600 transition">
                <x-icon name="share" class="w-4 h-4"/>
            </button>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm text-rail-700 hover:underline">
                <x-icon name="refresh" class="w-4 h-4"/> بحث جديد
            </a>
        </div>
    </div>

  
    @if ($results->isNotEmpty())
        @php $types = $results->map(fn ($r) => $r['train']->type_label)->filter()->unique()->values(); @endphp

        {{-- ترتيب النتائج --}}
        <div class="flex items-center gap-2 mb-3 text-sm">
            <span class="text-slate-400 text-xs">ترتيب:</span>
            <button data-sort="depart" class="sort-btn px-3 py-1.5 rounded-full bg-rail-600 text-white font-bold text-xs transition">الأبدري</button>
            <button data-sort="price" class="sort-btn px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200 text-slate-600 font-bold text-xs transition">الأرخص</button>
            <button data-sort="duration" class="sort-btn px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200 text-slate-600 font-bold text-xs transition">الأسرع</button>
        </div>

        {{-- فلترة --}}
        @if ($types->count() > 1 || true)
            <div class="flex items-center gap-2 mb-3 text-sm flex-wrap">
                <span class="text-slate-400 text-xs">فلتر:</span>
                @foreach ($types as $type)
                    <button data-filter-type="{{ $type }}" class="filter-type px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200 text-slate-600 font-bold text-xs transition">{{ $type }}</button>
                @endforeach
                <button id="filter-fare" class="px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200 text-slate-600 font-bold text-xs transition">عليه سعر</button>
            </div>
        @endif

        <p id="no-match" hidden class="text-sm text-slate-500 bg-white rounded-2xl ring-1 ring-slate-100 px-4 py-3 mb-3">مفيش نتائج بالفلتر ده.</p>

        <div id="results" class="space-y-3">
        @foreach ($results as $r)
        <a href="{{ route('trains.show', ['train' => $r['train'], 'from' => $from->id, 'to' => $to->id]) }}"
            data-depart="{{ $r['depart'] }}"
            data-price="{{ ! empty($r['fares']) ? $r['fares'][0]['price'] : 999999 }}"
            data-duration="{{ $r['duration'] }}"
            data-type="{{ $r['train']->type_label }}"
            data-fare="{{ ! empty($r['fares']) ? 1 : 0 }}"
            class="result-card block bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="bg-rail-50 text-rail-700 text-xs font-bold px-2.5 py-1 rounded-full">قطار {{ $r['train']->number }}</span>
                    <span class="text-xs text-slate-500">{{ $r['train']->type_label }}</span>
                </div>
                <div class="text-left">
                    @if (! empty($r['fares']))
                        <span class="text-xs text-slate-400">يبدأ من</span>
                        <span class="font-bold text-rail-700">{{ number_format($r['fares'][0]['price']) }} ج.م</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between gap-4 mt-3">
                <div class="text-center">
                    <div class="text-2xl font-bold whitespace-nowrap">{{ \App\Support\Format::time($r['depart']) }}</div>
                    <div class="text-xs text-slate-500">{{ $from->name_ar }}</div>
                </div>
                <div class="flex-1 flex flex-col items-center text-slate-400">
                    <div class="text-xs">{{ $r['duration'] }}</div>
                    <div class="w-full border-t border-dashed border-slate-300 my-1 relative">
                        <x-icon name="dot" class="absolute -top-1.5 right-0 w-3 h-3 text-rail-500"/>
                        <x-icon name="train" class="absolute -top-2.5 left-0 w-4 h-4 text-slate-400"/>
                    </div>
                    <div class="text-xs">{{ $r['distance'] !== null ? $r['distance'].' كم' : '' }}</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold whitespace-nowrap">{{ \App\Support\Format::time($r['arrive']) }}</div>
                    <div class="text-xs text-slate-500">{{ $to->name_ar }}</div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-100">
                @forelse ($r['fares'] as $fare)
                    <span class="text-xs bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-full px-2.5 py-1">
                        {{ $fare['label'] }}: <b>{{ number_format($fare['price']) }} ج.م</b>
                    </span>
                @empty
                    <span class="text-xs text-slate-400">السعر الرسمي من زر الحجز بالأعلى</span>
                @endforelse
            </div>
        </a>
        @endforeach
        </div>
    @else
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center text-slate-500 mb-4">
            <x-icon name="station" class="w-10 h-10 mx-auto mb-2 text-slate-300"/>
            لا توجد قطارات مباشرة بين <b>{{ $from->name_ar }}</b> و<b>{{ $to->name_ar }}</b> في هذا اليوم ضمن البيانات المتاحة.
        </div>

        @php
            $altDest = $suggestions['destinations'] ?? [];
            $altOrigin = $suggestions['origins'] ?? [];
        @endphp

        @if (count($altDest) || count($altOrigin))
            <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5">
                <h2 class="font-bold mb-1 flex items-center gap-2">
                    <x-icon name="pin" class="w-5 h-5 text-amber-500"/> أقرب البدائل عليها قطار
                </h2>
                <p class="text-xs text-slate-400 mb-4">محطات قريبة عليها خدمة فعلًا — اضغط لإعادة البحث.</p>

                @if (count($altDest))
                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-slate-600 mb-2">بدّل محطة الوصول (قطار من {{ $from->name_ar }})</h3>
                        <div class="flex flex-col gap-2">
                            @foreach ($altDest as $alt)
                                <a href="{{ route('search', ['from' => $from->id, 'to' => $alt['station']->id, 'date' => $date->toDateString()]) }}"
                                    class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 hover:border-rail-400 hover:bg-rail-50 px-4 py-3 transition">
                                    <span class="flex items-center gap-2 font-medium">
                                        <x-icon name="dot" class="w-2.5 h-2.5 text-rail-600"/>
                                        {{ $from->name_ar }} <x-icon name="arrow-left" class="w-4 h-4 text-slate-400"/> {{ $alt['station']->name_ar }}
                                    </span>
                                    <span class="flex items-center gap-2 text-xs text-slate-400 whitespace-nowrap">
                                        @if ($alt['distance'] !== null)
                                            على بُعد ~{{ number_format($alt['distance']) }} كم
                                        @endif
                                        <x-icon name="chevron-right" class="w-4 h-4"/>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (count($altOrigin))
                    <div>
                        <h3 class="text-sm font-bold text-slate-600 mb-2">بدّل محطة القيام (قطار إلى {{ $to->name_ar }})</h3>
                        <div class="flex flex-col gap-2">
                            @foreach ($altOrigin as $alt)
                                <a href="{{ route('search', ['from' => $alt['station']->id, 'to' => $to->id, 'date' => $date->toDateString()]) }}"
                                    class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 hover:border-rail-400 hover:bg-rail-50 px-4 py-3 transition">
                                    <span class="flex items-center gap-2 font-medium">
                                        <x-icon name="dot" class="w-2.5 h-2.5 text-rail-600"/>
                                        {{ $alt['station']->name_ar }} <x-icon name="arrow-left" class="w-4 h-4 text-slate-400"/> {{ $to->name_ar }}
                                    </span>
                                    <span class="flex items-center gap-2 text-xs text-slate-400 whitespace-nowrap">
                                        @if ($alt['distance'] !== null)
                                            على بُعد ~{{ number_format($alt['distance']) }} كم
                                        @endif
                                        <x-icon name="chevron-right" class="w-4 h-4"/>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif

    <script>
        // حفظ آخر بحث محليًا (يظهر في الصفحة الرئيسية).
        (() => {
            try {
                const KEY = 'qm:recent';
                const item = {
                    from: {{ $from->id }}, to: {{ $to->id }},
                    fromName: @json($from->name_ar), toName: @json($to->name_ar),
                    date: @json($date->toDateString()),
                };
                let list = JSON.parse(localStorage.getItem(KEY) || '[]').filter(r => !(r.from === item.from && r.to === item.to));
                list.unshift(item);
                localStorage.setItem(KEY, JSON.stringify(list.slice(0, 6)));
            } catch (e) {}
        })();

        // ترتيب النتائج (بدون إعادة تحميل).
        (() => {
            const box = document.getElementById('results');
            if (!box) return;
            const cards = [...box.children];
            const toMin = (d) => { const h = /(\d+)\s*س/.exec(d), m = /(\d+)\s*د/.exec(d); return (h ? +h[1] : 0) * 60 + (m ? +m[1] : 0); };
            const keys = {
                depart: (c) => c.dataset.depart || '',
                price: (c) => +c.dataset.price,
                duration: (c) => toMin(c.dataset.duration || ''),
            };
            document.querySelectorAll('.sort-btn').forEach(btn => btn.addEventListener('click', () => {
                const k = btn.dataset.sort;
                document.querySelectorAll('.sort-btn').forEach(b => {
                    const on = b === btn;
                    b.classList.toggle('bg-rail-600', on);
                    b.classList.toggle('text-white', on);
                    b.classList.toggle('bg-white', !on);
                    b.classList.toggle('ring-1', !on);
                    b.classList.toggle('ring-slate-200', !on);
                    b.classList.toggle('text-slate-600', !on);
                });
                cards.slice().sort((a, b) => keys[k](a) > keys[k](b) ? 1 : keys[k](a) < keys[k](b) ? -1 : 0)
                    .forEach(c => box.appendChild(c));
            }));
        })();

        // فلترة النتائج (نوع القطار + عليه سعر).
        (() => {
            const box = document.getElementById('results');
            if (!box) return;
            const cards = [...box.querySelectorAll('.result-card')];
            const typeBtns = [...document.querySelectorAll('.filter-type')];
            const fareBtn = document.getElementById('filter-fare');
            const noMatch = document.getElementById('no-match');
            const selectedTypes = new Set();
            let fareOnly = false;

            const styleBtn = (btn, on) => {
                btn.classList.toggle('bg-rail-600', on);
                btn.classList.toggle('text-white', on);
                btn.classList.toggle('bg-white', !on);
                btn.classList.toggle('ring-1', !on);
                btn.classList.toggle('ring-slate-200', !on);
                btn.classList.toggle('text-slate-600', !on);
            };

            function apply() {
                let visible = 0;
                cards.forEach(c => {
                    const okType = selectedTypes.size === 0 || selectedTypes.has(c.dataset.type);
                    const okFare = !fareOnly || c.dataset.fare === '1';
                    const show = okType && okFare;
                    c.hidden = !show;
                    if (show) visible++;
                });
                if (noMatch) noMatch.hidden = visible !== 0;
            }

            typeBtns.forEach(btn => btn.addEventListener('click', () => {
                const t = btn.dataset.filterType;
                if (selectedTypes.has(t)) selectedTypes.delete(t); else selectedTypes.add(t);
                styleBtn(btn, selectedTypes.has(t));
                apply();
            }));
            if (fareBtn) fareBtn.addEventListener('click', () => {
                fareOnly = !fareOnly;
                styleBtn(fareBtn, fareOnly);
                apply();
            });
        })();
    </script>
@endsection
