@extends('layouts.app')

@section('title', "قطارات {$from->name_ar} ← {$to->name_ar}")

@section('content')
    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="text-xl font-bold">{{ $from->name_ar }} <span class="text-slate-400">←</span> {{ $to->name_ar }}</h1>
            <p class="text-sm text-slate-500">{{ $date->translatedFormat('l j F Y') }} — {{ $results->count() }} قطار</p>
        </div>
        <a href="{{ route('home') }}" class="text-sm text-rail-700 hover:underline">↻ بحث جديد</a>
    </div>

    <a href="{{ \App\Support\EgyptRailReference::bookingUrl($from->booking_name, $to->booking_name, $date->toDateString()) }}"
        target="_blank" rel="noopener"
        class="flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl px-4 py-3 mb-4 transition">
        🎫 احجز على الموقع الرسمي لهيئة السكة الحديد
    </a>

    @forelse ($results as $r)
        <a href="{{ route('trains.show', $r['train']) }}" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-4 mb-3">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <span class="bg-rail-50 text-rail-700 text-xs font-bold px-2 py-1 rounded">قطار {{ $r['train']->number }}</span>
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
                        <span class="absolute -top-2 right-0">●</span>
                        <span class="absolute -top-2 left-0">🚆</span>
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
                    <span class="text-xs bg-emerald-50 border border-emerald-200 text-emerald-800 rounded px-2 py-1">
                        {{ $fare['label'] }}: <b>{{ number_format($fare['price']) }} ج.م</b>
                    </span>
                @empty
                    <span class="text-xs text-slate-400">السعر الرسمي من زر الحجز بالأعلى</span>
                @endforelse
            </div>
        </a>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-slate-500">
            لا توجد قطارات مباشرة بين هاتين المحطتين في هذا اليوم ضمن البيانات المتاحة.
        </div>
    @endforelse
@endsection
