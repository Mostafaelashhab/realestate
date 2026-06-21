@php
    $altDest = $suggestions['destinations'] ?? [];
    $altOrigin = $suggestions['origins'] ?? [];
@endphp

@if (count($altDest) || count($altOrigin))
    <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5">
        <h2 class="font-bold mb-1 flex items-center gap-2">
            <x-icon name="pin" class="w-5 h-5 text-amber-500"/> أقرب البدائل عليها قطار
        </h2>
        <p class="text-xs text-slate-400 mb-4">محطات قريبة عليها خدمة فعلًا — اضغط لإعادة البحث.</p>

        @if (count($altDest))
            <div class="mb-4">
                <h3 class="text-sm font-bold text-slate-600 mb-2">بدّل محطة الوصول (قطار من {{ $from->name_ar }})</h3>
                <div class="flex flex-col gap-2">
                    @foreach ($altDest as $alt)
                        <a href="{{ route('route', ['from' => $from->slug, 'to' => $alt['station']->slug, 'date' => $date->toDateString()]) }}"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 hover:border-rail-400 hover:bg-rail-50 px-4 py-3 transition">
                            <span class="flex items-center gap-2 font-medium">
                                <x-icon name="dot" class="w-2.5 h-2.5 text-rail-600"/>
                                {{ $from->name_ar }} <x-icon name="arrow-left" class="w-4 h-4 text-slate-400"/> {{ $alt['station']->name_ar }}
                            </span>
                            <span class="flex items-center gap-2 text-xs text-slate-400 whitespace-nowrap">
                                @if ($alt['distance'] !== null) على بُعد ~{{ number_format($alt['distance']) }} كم @endif
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
                        <a href="{{ route('route', ['from' => $alt['station']->slug, 'to' => $to->slug, 'date' => $date->toDateString()]) }}"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 hover:border-rail-400 hover:bg-rail-50 px-4 py-3 transition">
                            <span class="flex items-center gap-2 font-medium">
                                <x-icon name="dot" class="w-2.5 h-2.5 text-rail-600"/>
                                {{ $alt['station']->name_ar }} <x-icon name="arrow-left" class="w-4 h-4 text-slate-400"/> {{ $to->name_ar }}
                            </span>
                            <span class="flex items-center gap-2 text-xs text-slate-400 whitespace-nowrap">
                                @if ($alt['distance'] !== null) على بُعد ~{{ number_format($alt['distance']) }} كم @endif
                                <x-icon name="chevron-right" class="w-4 h-4"/>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
