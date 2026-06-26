@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-bold rounded-full px-5 py-3 transition active:scale-[.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-rail-500/40 disabled:opacity-50 disabled:pointer-events-none';
    $variants = [
        'primary' => 'bg-rail-600 hover:bg-rail-700 text-white shadow-lg shadow-rail-600/25',
        'secondary' => 'bg-white text-rail-700 ring-1 ring-slate-200 hover:ring-rail-300',
        'ghost' => 'text-rail-700 hover:bg-rail-50',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-600/20',
        'dark' => 'bg-slate-900 hover:bg-slate-800 text-white',
    ];
    $cls = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif
