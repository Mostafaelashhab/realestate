@extends('layouts.app')

@section('title', 'القطر فين دلوقتي')

@section('content')
    <h1 class="text-xl font-bold mb-1">القطر فين دلوقتي؟</h1>
    <p class="text-sm text-slate-500 mb-4">
        القطارات المتحركة أو الواقفة في محطة دلوقتي ({{ $trains->count() }} من {{ number_format($total) }}) —
        موقع تقديري محسوب من جدول المواعيد، مش تتبع فعلي.
    </p>

    @php
        $statusStyles = [
            'running' => ['bg-emerald-100 text-emerald-800', '🟢 في الطريق'],
            'at_station' => ['bg-sky-100 text-sky-800', '🔵 في المحطة'],
            'before' => ['bg-amber-100 text-amber-800', '🟡 لم يتحرك بعد'],
            'idle' => ['bg-slate-100 text-slate-600', '⚪ خارج الخدمة'],
            'unknown' => ['bg-slate-100 text-slate-600', '⚪ غير معروف'],
        ];
    @endphp

    @if ($trains->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-slate-500">
            مفيش قطارات محسوبة كمتحركة في الوقت ده. جرّب تاني بعد شوية أو افتح صفحة أي قطار لمتابعته.
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach ($trains as $row)
            @php [$cls, $txt] = $statusStyles[$row['position']['status']] ?? $statusStyles['unknown']; @endphp
            <a href="{{ route('trains.show', $row['train']) }}" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="bg-rail-50 text-rail-700 text-xs font-bold px-2 py-1 rounded">قطار {{ $row['train']->number }}</span>
                        <span class="text-xs text-slate-500">{{ $row['train']->type_label }}</span>
                    </div>
                    <span class="text-xs font-bold px-2 py-1 rounded-full {{ $cls }}">{{ $txt }}</span>
                </div>
                <p class="text-sm text-slate-700">{{ $row['position']['message'] }}</p>
                @if ($row['position']['overall_progress'] !== null)
                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-3 overflow-hidden">
                        <div class="bg-rail-600 h-1.5 rounded-full" style="width: {{ $row['position']['overall_progress'] }}%"></div>
                    </div>
                @endif
            </a>
        @endforeach
    </div>
@endsection
