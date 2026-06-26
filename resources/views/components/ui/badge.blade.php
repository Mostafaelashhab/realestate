@props(['color' => 'rail'])

@php
    $map = [
        'rail' => 'bg-rail-50 text-rail-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'red' => 'bg-red-50 text-red-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs font-bold rounded-full px-2.5 py-1 ' . ($map[$color] ?? $map['rail'])]) }}>
    {{ $slot }}
</span>
