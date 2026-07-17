@extends('layouts.app')

@section('title', 'أعلى القطارات تقييمًا')
@section('og_title', 'أعلى القطارات تقييمًا — تقييمات الركّاب')
@section('og_desc', 'ترتيب قطارات مصر حسب تقييمات الركّاب الحقيقية: الأعلى تقييمًا الأول.')

@php
    $starRow = function ($n) {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $on = $i <= round($n);
            $out .= '<svg viewBox="0 0 24 24" class="w-4 h-4 ' . ($on ? 'text-amber-400' : 'text-slate-300') . '" fill="' . ($on ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3.5l2.6 5.3 5.8.9-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.2-4.1 5.8-.9z"/></svg>';
        }
        return $out;
    };
    // ميداليات المراكز الثلاثة الأولى.
    $medal = ['from-amber-400 to-amber-600', 'from-slate-300 to-slate-500', 'from-orange-400 to-orange-600'];
@endphp

@section('content')
    {{-- هيدر الصفحة --}}
    <section
        class="relative overflow-hidden bg-linear-to-br from-rail-800 via-rail-700 to-rail-600 text-white rounded-3xl p-6 mb-4 shadow-xl shadow-rail-800/25">
        <svg class="absolute -top-10 -start-10 w-52 h-52 text-white/10" viewBox="0 0 100 100" fill="none"
            stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100" />
            <path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8" />
        </svg>
        <div class="relative">
            <h1 class="text-2xl font-extrabold flex items-center gap-2">
                <x-icon name="star" class="w-7 h-7 text-amber-300" /> أعلى القطارات تقييمًا
            </h1>
            <p class="text-rail-50/90 text-sm mt-1.5">الترتيب حسب آراء الركّاب الحقيقية على القطر.</p>
        </div>
    </section>

    @forelse ($trains as $i => $t)
        <a href="{{ $t['url'] }}"
            class="flex items-center gap-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 hover:ring-rail-200 active:scale-[.99] p-3.5 mb-2.5 transition">
            {{-- المركز --}}
            @if ($i < 3)
                <span class="shrink-0 w-9 h-9 grid place-items-center rounded-full bg-linear-to-br {{ $medal[$i] }} text-white font-extrabold shadow-sm">{{ $i + 1 }}</span>
            @else
                <span class="shrink-0 w-9 h-9 grid place-items-center rounded-full bg-slate-100 text-slate-500 font-extrabold">{{ $i + 1 }}</span>
            @endif

            {{-- بيانات القطر --}}
            <div class="flex-1 min-w-0">
                <div class="font-extrabold text-slate-800">قطار {{ $t['number'] }}</div>
                <div class="text-xs text-slate-500 truncate">{{ $t['type'] }}</div>
            </div>

            {{-- التقييم --}}
            <div class="shrink-0 text-end">
                <div class="inline-flex items-center gap-1">
                    <span class="text-sm font-bold text-slate-700">{{ $t['avg'] }}</span>
                    <x-icon name="star" class="w-4 h-4 text-amber-400" />
                </div>
                <div class="text-[11px] text-slate-400">{{ $t['count'] }} رأي</div>
            </div>
        </a>
    @empty
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center">
            <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100">
                <x-icon name="star" class="w-7 h-7" />
            </div>
            <p class="font-bold text-slate-700">لسه مفيش قطارات متقيّمة</p>
            <p class="text-sm text-slate-500 mt-1">كن أول من يقيّم قطر وتشوفه هنا في الترتيب.</p>
        </div>
    @endforelse
@endsection
