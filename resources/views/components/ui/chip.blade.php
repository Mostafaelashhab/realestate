@props(['color' => 'emerald'])

@php
    $map = [
        'emerald' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'amber' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        'red' => ['bg-red-50 text-red-600', 'bg-red-500'],
        'slate' => ['bg-slate-100 text-slate-600', 'bg-slate-400'],
        'rail' => ['bg-rail-50 text-rail-700', 'bg-rail-600'],
    ];
    [$wrap, $dot] = $map[$color] ?? $map['emerald'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-bold rounded-full px-3 py-1 {$wrap}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $slot }}
</span>
