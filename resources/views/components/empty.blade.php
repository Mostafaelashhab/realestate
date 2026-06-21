@props(['icon' => 'station'])

<div {{ $attributes->merge(['class' => 'bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center text-slate-500']) }}>
    <x-icon :name="$icon" class="w-10 h-10 mx-auto mb-2 text-slate-300"/>
    <div class="text-sm leading-relaxed">{{ $slot }}</div>
</div>
