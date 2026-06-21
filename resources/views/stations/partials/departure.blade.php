<a href="{{ route('trains.show', ['train' => $d['train'], 'from' => $station->id, 'to' => $d['destination']->id]) }}"
    class="flex items-center gap-4 bg-white rounded-3xl shadow-sm ring-1 active:scale-[.99] transition p-4 {{ $next ? 'ring-rail-300' : 'ring-slate-100' }}">
    <div class="text-center shrink-0">
        <div class="text-2xl font-extrabold whitespace-nowrap">{{ \App\Support\Format::time($d['departure']) }}</div>
        @if ($next)<div class="text-[10px] font-bold text-rail-600">القادم</div>@endif
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
