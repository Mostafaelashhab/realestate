@extends('layouts.app')

@section('title', "قطارات {$from->name_ar} ← {$to->name_ar}")

@section('content')
    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="text-xl font-bold">{{ $from->name_ar }} <span class="text-slate-400">←</span> {{ $to->name_ar }}</h1>
            <p class="text-sm text-slate-500">{{ $date->translatedFormat('l j F Y') }} — {{ $results->count() }} قطار</p>
        </div>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm text-rail-700 hover:underline">
            <x-icon name="refresh" class="w-4 h-4"/> بحث جديد
        </a>
    </div>

    <a href="{{ \App\Support\EgyptRailReference::bookingUrl($from->booking_name, $to->booking_name, $date->toDateString()) }}"
        target="_blank" rel="noopener"
        class="flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 active:scale-[.99] text-white font-bold rounded-2xl px-4 py-3 mb-4 transition shadow-lg shadow-amber-500/25">
        <x-icon name="ticket" class="w-5 h-5"/>
        احجز على الموقع الرسمي لهيئة السكة الحديد
    </a>

    @forelse ($results as $r)
        <a href="{{ route('trains.show', ['train' => $r['train'], 'from' => $from->id, 'to' => $to->id]) }}" class="block bg-white rounded-3xl shadow-sm active:scale-[.99] transition p-4 mb-3">
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
    @empty
        <div class="bg-white rounded-3xl shadow-sm p-8 text-center text-slate-500 mb-4">
            <x-icon name="station" class="w-10 h-10 mx-auto mb-2 text-slate-300"/>
            لا توجد قطارات مباشرة بين <b>{{ $from->name_ar }}</b> و<b>{{ $to->name_ar }}</b> في هذا اليوم ضمن البيانات المتاحة.
        </div>

        @php
            $altDest = $suggestions['destinations'] ?? [];
            $altOrigin = $suggestions['origins'] ?? [];
        @endphp

        @if (count($altDest) || count($altOrigin))
            <div class="bg-white rounded-3xl shadow-sm p-5">
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
    @endforelse
@endsection
