@php
    $styles = [
        'on_time' => ['bg-emerald-50', 'text-emerald-700', 'ring-emerald-200'],
        'delayed' => ['bg-amber-50', 'text-amber-700', 'ring-amber-200'],
        'cancelled' => ['bg-red-50', 'text-red-700', 'ring-red-200'],
    ];
@endphp

@if (! $s || ($s['count'] ?? 0) === 0)
    <p class="text-sm text-slate-500">مفيش بلاغات لسه — كن أول من يبلّغ حالة القطار.</p>
@else
    @php [$bg, $tx, $rg] = $styles[$s['status']] ?? $styles['on_time']; @endphp
    <div class="rounded-2xl ring-1 {{ $bg }} {{ $rg }} p-3">
        <div class="flex items-center justify-between gap-2">
            <span class="font-bold {{ $tx }}">{{ $s['headline'] }}</span>
            <span class="text-xs text-slate-500">{{ $s['count'] }} بلاغ · {{ $s['last_ago'] }}</span>
        </div>
        @if (! empty($s['recent']))
            <div class="mt-2 flex flex-col gap-1">
                @foreach ($s['recent'] as $r)
                    <div class="text-xs text-slate-500 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $tx }} bg-current"></span>
                        @if ($r['status'] === 'delayed') متأخر{{ $r['delay'] ? ' ~'.$r['delay'].' د' : '' }}
                        @elseif ($r['status'] === 'cancelled') ملغي/واقف
                        @else في الموعد @endif
                        @if (! empty($r['note'])) — {{ $r['note'] }} @endif
                        <span class="text-slate-300">· {{ $r['ago'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
