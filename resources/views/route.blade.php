@extends('layouts.app')

@section('dark', '1')
@section('hideHeader', '1')
@section('title', "قطارات {$from->name_ar} ← {$to->name_ar}")
@section('og_title', "مواعيد وأسعار قطارات {$from->name_ar} إلى {$to->name_ar}")
@section('og_desc', $summary['count']
    ? "{$summary['count']} قطار يوميًا من {$from->name_ar} إلى {$to->name_ar} — المواعيد والأسعار والمقاعد المتاحة."
    : "مواعيد وأسعار القطارات بين {$from->name_ar} و{$to->name_ar}.")

@push('head')
    <script type="application/ld+json">
    @php
        $ld = [
            ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $from->name_ar, 'item' => route('stations.show', $from)],
                ['@type' => 'ListItem', 'position' => 3, 'name' => "قطارات {$from->name_ar} إلى {$to->name_ar}", 'item' => url()->current()],
            ]],
        ];
        if (! empty($faqs)) {
            $ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($faqs)->map(fn ($f) => [
                '@type' => 'Question', 'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ])->all()];
        }
    @endphp
    {!! json_encode(count($ld) === 1 ? $ld[0] : $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    {{-- هيرو المسار --}}
    <section class="relative overflow-hidden bg-linear-to-br from-rail-800 via-rail-700 to-rail-600 text-white rounded-3xl p-5 mb-4 shadow-xl shadow-rail-800/25">
        <svg class="absolute -top-8 -start-10 w-44 h-44 text-white/10" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100"/>
            <path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8"/>
        </svg>
        <h1 class="sr-only">قطارات {{ $from->name_ar }} إلى {{ $to->name_ar }} — {{ $date->translatedFormat('j F Y') }}</h1>
        <div class="relative">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs font-bold bg-white/15 ring-1 ring-white/20 rounded-full px-3 py-1">{{ $results->count() }} قطار</span>
                <span class="text-xs text-rail-50/80">{{ $date->translatedFormat('l j F') }}</span>
                <div class="ms-auto flex items-center gap-1.5">
                    <button type="button" data-share data-share-title="قطارات {{ $from->name_ar }} ← {{ $to->name_ar }}" aria-label="مشاركة"
                        class="w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 active:scale-90 transition">
                        <x-icon name="share" class="w-4 h-4"/>
                    </button>
                    <a href="{{ route('home') }}" aria-label="بحث جديد"
                        class="w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 active:scale-90 transition">
                        <x-icon name="search" class="w-4 h-4"/>
                    </a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex-1 min-w-0 text-center">
                    <div class="text-lg font-extrabold truncate">{{ $from->name_ar }}</div>
                    <div class="text-[11px] text-rail-50/70 mt-0.5">قيام</div>
                </div>
                <div class="flex items-center gap-1 w-24 sm:w-32 shrink-0">
                    <x-icon name="dot" class="w-2.5 h-2.5 shrink-0 text-white"/>
                    <span class="flex-1 border-t-2 border-dashed border-white/40"></span>
                    <x-icon name="train" class="w-4 h-4 shrink-0 text-white"/>
                    <span class="flex-1 border-t-2 border-dashed border-white/40"></span>
                    <x-icon name="pin" class="w-3.5 h-3.5 shrink-0 text-amber-300"/>
                </div>
                <div class="flex-1 min-w-0 text-center">
                    <div class="text-lg font-extrabold truncate">{{ $to->name_ar }}</div>
                    <div class="text-[11px] text-rail-50/70 mt-0.5">وصول</div>
                </div>
            </div>
        </div>
    </section>

    {{-- مقدمة وصفية (SEO) --}}
    @if ($summary['count'])
        <p class="text-sm text-slate-600 bg-white rounded-2xl ring-1 ring-slate-100 px-4 py-3 mb-4 leading-relaxed">
            يوجد <b>{{ $summary['count'] }}</b> قطار يوميًا من <b>{{ $from->name_ar }}</b> إلى <b>{{ $to->name_ar }}</b>،
            أول قطار الساعة {{ \App\Support\Format::time($summary['first']) }} وآخر قطار {{ \App\Support\Format::time($summary['last']) }}.
            @if ($summary['min_price'])
                تبدأ أسعار التذاكر الرسمية من <b>{{ number_format($summary['min_price']) }} ج.م</b>.
            @endif
            @if ($summary['distance']) المسافة حوالي {{ number_format($summary['distance']) }} كم. @endif
        </p>
    @endif

    {{-- التنقّل بين الأيام --}}
    <div class="flex items-center justify-between gap-2 mb-4 text-sm">
        <a rel="nofollow" href="{{ route('route', ['from' => $from->slug, 'to' => $to->slug, 'date' => $date->copy()->subDay()->toDateString()]) }}"
            class="inline-flex items-center gap-1 bg-white ring-1 ring-slate-200 rounded-full px-3 py-1.5 hover:ring-rail-300 transition">
            <x-icon name="chevron-right" class="w-4 h-4"/> أمس
        </a>
        <span class="text-white font-bold">{{ $date->translatedFormat('l j F') }}</span>
        <a rel="nofollow" href="{{ route('route', ['from' => $from->slug, 'to' => $to->slug, 'date' => $date->copy()->addDay()->toDateString()]) }}"
            class="inline-flex items-center gap-1 bg-white ring-1 ring-slate-200 rounded-full px-3 py-1.5 hover:ring-rail-300 transition">
            بكرة <x-icon name="arrow-left" class="w-4 h-4"/>
        </a>
    </div>


    @if ($results->isNotEmpty())
        @php $types = $results->map(fn ($r) => $r['train']->type_label)->filter()->unique()->values(); @endphp

        <div class="flex items-center gap-2 mb-3 text-sm">
            <span class="text-rail-200/70 text-xs">ترتيب:</span>
            <button data-sort="depart" class="sort-btn px-3 py-1.5 rounded-full bg-rail-600 text-white font-bold text-xs transition">بالوقت</button>
            <button data-sort="price" class="sort-btn px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200 text-slate-600 font-bold text-xs transition">الأرخص</button>
            <button data-sort="duration" class="sort-btn px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200 text-slate-600 font-bold text-xs transition">الأسرع</button>
        </div>

        <div id="results" class="space-y-3">
        @foreach ($results as $r)
            <a href="{{ route('trains.show', ['train' => $r['train'], 'from' => $from->id, 'to' => $to->id]) }}"
                data-depart="{{ $r['depart'] }}" data-price="{{ ! empty($r['fares']) ? $r['fares'][0]['price'] : 999999 }}" data-duration="{{ $r['duration'] }}"
                class="result-card block bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="bg-rail-50 text-rail-700 text-xs font-bold px-2.5 py-1 rounded-full">قطار {{ $r['train']->number }}</span>
                        <span class="text-xs text-slate-500">{{ $r['train']->type_label }}</span>
                    </div>
                    @if (! empty($r['fares']))
                        <div class="text-left"><span class="text-xs text-slate-400">يبدأ من</span> <span class="font-bold text-rail-700">{{ number_format($r['fares'][0]['price']) }} ج.م</span></div>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-4 mt-3">
                    <div class="text-center"><div class="text-2xl font-bold whitespace-nowrap">{{ \App\Support\Format::time($r['depart']) }}</div><div class="text-xs text-slate-500">{{ $from->name_ar }}</div></div>
                    <div class="flex-1 flex flex-col items-center text-slate-400">
                        <div class="text-xs">{{ $r['duration'] }}</div>
                        <div class="w-full flex items-center gap-1 my-1.5">
                            <x-icon name="dot" class="w-2.5 h-2.5 shrink-0 text-rail-500"/>
                            <span class="flex-1 border-t border-dashed border-slate-300"></span>
                            <x-icon name="train" class="w-4 h-4 shrink-0 text-slate-400"/>
                            <span class="flex-1 border-t border-dashed border-slate-300"></span>
                            <x-icon name="pin" class="w-3.5 h-3.5 shrink-0 text-amber-500"/>
                        </div>
                        <div class="text-xs">{{ $r['distance'] !== null ? $r['distance'].' كم' : '' }}</div>
                    </div>
                    <div class="text-center"><div class="text-2xl font-bold whitespace-nowrap">{{ \App\Support\Format::time($r['arrive']) }}</div><div class="text-xs text-slate-500">{{ $to->name_ar }}</div></div>
                </div>
            </a>
        @endforeach
        </div>

        {{-- محطات على الطريق (روابط داخلية) --}}
        @if ($onRoute->isNotEmpty())
            <div class="mt-5">
                <h2 class="text-sm font-bold text-rail-100 mb-2">محطات على الطريق</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($onRoute as $st)
                        <a href="{{ route('stations.show', $st) }}" class="text-sm bg-white ring-1 ring-slate-200 hover:ring-rail-300 rounded-full px-3 py-1.5 transition">{{ $st->name_ar }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <x-empty icon="station" class="mb-4">
            لا توجد قطارات مباشرة بين <b>{{ $from->name_ar }}</b> و<b>{{ $to->name_ar }}</b> في هذا اليوم.
        </x-empty>
        @include('trains.partials.route-suggestions', ['suggestions' => $suggestions, 'from' => $from, 'to' => $to, 'date' => $date])
    @endif

    {{-- روابط داخلية --}}
    <div class="mt-6 space-y-4">
        <a href="{{ route('route', ['from' => $related['reverse']->slug, 'to' => $from->slug]) }}"
            class="flex items-center justify-between gap-3 bg-white rounded-2xl ring-1 ring-slate-100 px-4 py-3 hover:ring-rail-300 transition">
            <span class="font-bold text-sm">الاتجاه العكسي: {{ $to->name_ar }} ← {{ $from->name_ar }}</span>
            <x-icon name="chevron-right" class="w-5 h-5 text-slate-300"/>
        </a>

        @if ($related['destinations']->isNotEmpty())
            <div>
                <h2 class="text-sm font-bold text-rail-100 mb-2">وجهات أخرى من {{ $from->name_ar }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($related['destinations'] as $dest)
                        <a href="{{ route('route', ['from' => $from->slug, 'to' => $dest->slug]) }}"
                            class="inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 hover:ring-rail-300 rounded-full px-3 py-1.5 text-sm transition">
                            {{ $from->name_ar }} <x-icon name="arrow-left" class="w-3.5 h-3.5 text-slate-400"/> {{ $dest->name_ar }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- الأسئلة الشائعة --}}
    @if (! empty($faqs))
        <div class="mt-6">
            <h2 class="font-bold mb-2 text-white">أسئلة شائعة</h2>
            <div class="space-y-2">
                @foreach ($faqs as $f)
                    <details class="bg-white rounded-2xl ring-1 ring-slate-100 px-4 py-3">
                        <summary class="font-bold text-sm cursor-pointer">{{ $f['q'] }}</summary>
                        <p class="text-sm text-slate-600 mt-2 leading-relaxed">{{ $f['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    @endif

    <script>
        // ترتيب النتائج
        (() => {
            const box = document.getElementById('results');
            if (!box) return;
            const cards = [...box.children];
            const toMin = (d) => { const h = /(\d+)\s*س/.exec(d), m = /(\d+)\s*د/.exec(d); return (h ? +h[1] : 0) * 60 + (m ? +m[1] : 0); };
            const keys = { depart: c => c.dataset.depart || '', price: c => +c.dataset.price, duration: c => toMin(c.dataset.duration || '') };
            document.querySelectorAll('.sort-btn').forEach(btn => btn.addEventListener('click', () => {
                const k = btn.dataset.sort;
                document.querySelectorAll('.sort-btn').forEach(b => {
                    const on = b === btn;
                    b.classList.toggle('bg-rail-600', on); b.classList.toggle('text-white', on);
                    b.classList.toggle('bg-white', !on); b.classList.toggle('ring-1', !on);
                    b.classList.toggle('ring-slate-200', !on); b.classList.toggle('text-slate-600', !on);
                });
                cards.slice().sort((a, b) => keys[k](a) > keys[k](b) ? 1 : keys[k](a) < keys[k](b) ? -1 : 0).forEach(c => box.appendChild(c));
            }));
        })();

        // حفظ آخر بحث
        (() => {
            try {
                const KEY = 'qm:recent';
                const item = { from: {{ $from->id }}, to: {{ $to->id }}, fromName: @json($from->name_ar), toName: @json($to->name_ar), date: @json($date->toDateString()) };
                let list = JSON.parse(localStorage.getItem(KEY) || '[]').filter(r => !(r.from === item.from && r.to === item.to));
                list.unshift(item);
                localStorage.setItem(KEY, JSON.stringify(list.slice(0, 6)));
            } catch (e) {}
        })();
    </script>
@endsection
