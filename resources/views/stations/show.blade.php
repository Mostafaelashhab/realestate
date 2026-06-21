@extends('layouts.app')

@section('title', "محطة {$station->name_ar}")

@section('content')
    <div class="flex items-center gap-2 mb-1">
        <x-icon name="station" class="w-6 h-6 text-rail-700"/>
        <h1 class="text-xl font-bold">محطة {{ $station->name_ar }}</h1>
    </div>
    <p class="text-sm text-slate-500 mb-4">القطارات القايمة من المحطة اليوم ({{ $departures->count() }}) — بترتيب الوقت.</p>

    @forelse ($departures as $d)
        <a href="{{ route('trains.show', ['train' => $d['train'], 'from' => $station->id, 'to' => $d['destination']->id]) }}"
            class="flex items-center gap-4 bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4 mb-3">
            <div class="text-center shrink-0">
                <div class="text-2xl font-extrabold whitespace-nowrap">{{ \App\Support\Format::time($d['departure']) }}</div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-rail-50 text-rail-700 text-xs font-bold px-2.5 py-1 rounded-full">قطار {{ $d['train']->number }}</span>
                    <span class="text-xs text-slate-500 truncate">{{ $d['train']->type_label }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-sm text-slate-600">
                    <x-icon name="pin" class="w-4 h-4 text-amber-500 shrink-0"/>
                    <span class="truncate">{{ $d['destination']->name_ar }}</span>
                </div>
            </div>
            <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 shrink-0"/>
        </a>
    @empty
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center text-slate-500">
            <x-icon name="station" class="w-10 h-10 mx-auto mb-2 text-slate-300"/>
            مفيش قطارات قايمة من المحطة دي اليوم ضمن البيانات المتاحة.
        </div>
    @endforelse
@endsection
