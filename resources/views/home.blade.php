@extends('layouts.app')

@section('title', 'مواعيد وأسعار القطارات')
@section('og_desc', 'مواعيد وأسعار قطارات مصر، والمحطات — في تطبيق واحد سريع.')
@section('hideHeader', '1')

@section('content')
    @include('partials.home-promos')

    {{-- ترحيب: أفاتار + اسم + شارة --}}
    @php $uname = auth()->user()->name ?? 'مسافر'; @endphp
    <div class="pt-[max(0.5rem,env(safe-area-inset-top))] mb-5 flex items-center gap-3">
        <span class="w-12 h-12 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold text-lg">
            {{ mb_substr($uname, 0, 1) }}
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-slate-400 text-sm">أهلاً بيك</p>
            <p class="font-extrabold text-lg text-slate-800 truncate leading-tight">{{ $uname }}</p>
        </div>
        <a href="{{ route('trains.top') }}" aria-label="أعلى القطارات"
            class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-rail-600">
            <x-icon name="star" class="w-6 h-6" />
        </a>
    </div>

    {{-- بحث inline: من → إلى --}}
    @include('partials.home-search-inline')

    {{-- مسارات شائعة (كروت ملوّنة أفقية) --}}
    @if ($popular->isNotEmpty())
        @php $tints = ['bg-rail-50 text-rail-700', 'bg-amber-50 text-amber-700', 'bg-sky-50 text-sky-700', 'bg-rose-50 text-rose-700', 'bg-teal-50 text-teal-700', 'bg-violet-50 text-violet-700']; @endphp
        <div class="flex items-center justify-between gap-2 mt-6 mb-3">
            <h2 class="font-extrabold text-slate-800">مسارات شائعة</h2>
            <span class="text-xs font-bold text-slate-400">{{ $popular->count() }}</span>
        </div>
        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-1 -mx-4 px-4">
            @foreach ($popular as $i => $p)
                <a href="{{ route('route', ['from' => $p['from']->slug, 'to' => $p['to']->slug]) }}"
                    class="shrink-0 w-44 rounded-3xl p-4 ring-1 ring-slate-100 shadow-sm bg-white active:scale-95 transition">
                    <span class="w-10 h-10 grid place-items-center rounded-2xl {{ $tints[$i % count($tints)] }} mb-3">
                        <x-icon name="train" class="w-5 h-5" />
                    </span>
                    <div class="font-bold text-slate-800 text-sm truncate">{{ $p['from']->name_ar }}</div>
                    <div class="flex items-center gap-1 text-xs text-slate-400 mt-0.5">
                        <x-icon name="arrow-left" class="w-3.5 h-3.5" />
                        <span class="truncate">{{ $p['to']->name_ar }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- خدمات سريعة (قائمة بستايل Task Groups) --}}
    @php
        $discover = config('enr.show_seats')
            ? ['icon' => 'seat', 't' => 'المقاعد المتاحة', 's' => 'شوف الكراسي الفاضية', 'act' => 'search', 'color' => 'bg-violet-100 text-violet-700']
            : ['icon' => 'star', 't' => 'الأعلى تقييمًا', 's' => 'ترتيب القطارات برأي الركّاب', 'href' => route('trains.top'), 'color' => 'bg-violet-100 text-violet-700'];
        $rows = [
            ['icon' => 'station', 't' => 'جدول المحطات', 's' => 'كل المواعيد والمحطات', 'act' => 'search', 'color' => 'bg-sky-100 text-sky-700'],
            ['icon' => 'mic', 't' => 'البحث بصوتك', 's' => 'قول رحلتك وسيبها علينا', 'href' => route('voice'), 'color' => 'bg-rail-100 text-rail-700'],
            $discover,
            ['icon' => 'pin', 't' => 'محطات قريبة منك', 's' => 'اعرف أقرب محطة', 'act' => 'near', 'color' => 'bg-rose-100 text-rose-700'],
            ['icon' => 'star', 't' => 'المفضلة', 's' => 'قطاراتك المحفوظة', 'href' => route('favorites'), 'color' => 'bg-amber-100 text-amber-700'],
            ['icon' => 'ticket', 't' => 'الغرامات', 's' => 'احسب غرامة التأخير', 'href' => route('fines'), 'color' => 'bg-teal-100 text-teal-700'],
        ];
    @endphp
    <div class="flex items-center justify-between gap-2 mt-6 mb-3">
        <h2 class="font-extrabold text-slate-800">خدمات سريعة</h2>
        <span class="text-xs font-bold text-slate-400">{{ count($rows) }}</span>
    </div>
    <div class="space-y-2.5">
        @foreach ($rows as $s)
            <{{ isset($s['href']) ? 'a' : 'button' }}
                @if (isset($s['href'])) href="{{ $s['href'] }}" @else type="button" data-quick="{{ $s['act'] }}" @endif
                class="w-full flex items-center gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-3 text-start hover:ring-rail-200 active:scale-[.99] transition">
                <span class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl {{ $s['color'] }}">
                    <x-icon :name="$s['icon']" class="w-5 h-5" />
                </span>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-slate-800 text-sm truncate">{{ $s['t'] }}</div>
                    <div class="text-xs text-slate-400 truncate">{{ $s['s'] }}</div>
                </div>
                <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
            </{{ isset($s['href']) ? 'a' : 'button' }}>
        @endforeach
    </div>
@endsection
