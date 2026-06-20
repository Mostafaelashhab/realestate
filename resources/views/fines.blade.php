@extends('layouts.app')

@section('title', 'الغرامات والمخالفات')

@section('content')
    <h1 class="text-xl font-bold mb-1">الغرامات والمخالفات</h1>
    <p class="text-sm text-slate-500 mb-5">قائمة استرشادية بالمخالفات الشائعة وقيمة الغرامة المقررة. راجع الهيئة للقيم الرسمية المحدّثة.</p>

    @foreach ($categories as $key => $label)
        @if (isset($fines[$key]))
            <h2 class="font-bold text-rail-700 mb-2 mt-5">{{ $label }}</h2>
            <div class="space-y-2">
                @foreach ($fines[$key] as $fine)
                    <div class="bg-white rounded-xl shadow-sm p-4 flex items-start justify-between gap-4">
                        <div>
                            <div class="font-medium">{{ $fine->title }}</div>
                            <p class="text-sm text-slate-500 mt-0.5">{{ $fine->description }}</p>
                        </div>
                        <span class="shrink-0 bg-red-50 text-red-700 text-xs font-bold px-3 py-1.5 rounded-lg whitespace-nowrap">
                            {{ $fine->amount_label }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
@endsection
