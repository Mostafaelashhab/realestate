@extends('layouts.app')

@section('title', "محطة {$station->name_ar}")
@section('og_title', "مواعيد القطارات من محطة {$station->name_ar}")
@section('og_desc', "كل القطارات القايمة من محطة {$station->name_ar} اليوم — المواعيد والوجهات.")

@section('content')
    <div class="flex items-center gap-2 mb-1">
        <x-icon name="station" class="w-6 h-6 text-rail-700"/>
        <h1 class="text-xl font-bold">محطة {{ $station->name_ar }}</h1>
    </div>
    <p class="text-sm text-slate-500 mb-4">القطارات القايمة من المحطة اليوم ({{ $departures->count() }}) — بترتيب الوقت.</p>

    @php
        $nowT = now()->format('H:i:s');
        $upcoming = $departures->filter(fn ($d) => $d['departure'] >= $nowT)->values();
        $earlier = $departures->filter(fn ($d) => $d['departure'] < $nowT)->values();
    @endphp

    @if ($departures->isEmpty())
        <x-empty icon="station">مفيش قطارات قايمة من المحطة دي اليوم ضمن البيانات المتاحة.</x-empty>
    @else
        @if ($upcoming->isNotEmpty())
            <h2 class="text-xs font-bold text-rail-700 mb-2">القادمة</h2>
            <div class="space-y-3 mb-5">
                @foreach ($upcoming as $i => $d)
                    @include('stations.partials.departure', ['d' => $d, 'station' => $station, 'next' => $i === 0])
                @endforeach
            </div>
        @endif

        @if ($earlier->isNotEmpty())
            <details class="group">
                <summary class="text-xs font-bold text-slate-400 mb-2 cursor-pointer list-none flex items-center gap-1">
                    <x-icon name="chevron-right" class="w-4 h-4 group-open:rotate-90 transition"/>
                    رحلات فاتت اليوم ({{ $earlier->count() }})
                </summary>
                <div class="space-y-3 mt-2 opacity-60">
                    @foreach ($earlier as $d)
                        @include('stations.partials.departure', ['d' => $d, 'station' => $station, 'next' => false])
                    @endforeach
                </div>
            </details>
        @endif
    @endif
@endsection
