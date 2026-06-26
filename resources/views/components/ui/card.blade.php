@props(['padding' => 'p-5'])

<div {{ $attributes->merge(['class' => "bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 {$padding}"]) }}>
    {{ $slot }}
</div>
