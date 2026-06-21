@extends('layouts.app')

@section('title', 'لوحة البلاغات')

@section('content')
    <h1 class="text-xl font-bold mb-1">البلاغات الواردة</h1>
    <p class="text-sm text-slate-500 mb-4">إجمالي {{ number_format($total) }} بلاغ.</p>

    @if (session('status'))
        <div class="bg-emerald-50 text-emerald-800 text-sm rounded-2xl px-4 py-3 mb-4 flex items-center gap-2">
            <x-icon name="check" class="w-5 h-5 shrink-0"/> {{ session('status') }}
        </div>
    @endif

    @php
        $typeStyles = [
            'schedule' => 'bg-sky-50 text-sky-700',
            'price' => 'bg-amber-50 text-amber-700',
            'other' => 'bg-slate-100 text-slate-600',
        ];
        $statusStyles = [
            'new' => 'bg-rail-50 text-rail-700',
            'reviewed' => 'bg-amber-50 text-amber-700',
            'resolved' => 'bg-emerald-50 text-emerald-700',
        ];
    @endphp

    {{-- فلترة بالحالة --}}
    <div class="flex flex-wrap gap-2 mb-4 text-sm">
        <a href="{{ route('reports.admin', $token) }}"
            class="px-3 py-1.5 rounded-full {{ $activeStatus ? 'bg-white ring-1 ring-slate-200 text-slate-600' : 'bg-rail-600 text-white' }}">
            الكل ({{ number_format($total) }})
        </a>
        @foreach (\App\Models\Report::STATUSES as $key => $label)
            <a href="{{ route('reports.admin', ['token' => $token, 'status' => $key]) }}"
                class="px-3 py-1.5 rounded-full {{ $activeStatus === $key ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">
                {{ $label }} ({{ number_format($counts[$key] ?? 0) }})
            </a>
        @endforeach
    </div>

    @forelse ($reports as $report)
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 mb-3">
            <div class="flex items-center justify-between gap-2 flex-wrap mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $typeStyles[$report->type] ?? 'bg-slate-100' }}">
                        {{ \App\Models\Report::TYPES[$report->type] ?? $report->type }}
                    </span>
                    @if ($report->train_number)
                        <span class="text-xs bg-rail-50 text-rail-700 font-bold px-2.5 py-1 rounded-full">قطار {{ $report->train_number }}</span>
                    @endif
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $statusStyles[$report->status] ?? 'bg-slate-100' }}">
                        {{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}
                    </span>
                </div>
                <span class="text-xs text-slate-400">{{ $report->created_at->diffForHumans() }}</span>
            </div>

            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $report->message }}</p>

            @if ($report->contact)
                <p class="text-xs text-slate-500 mt-2">تواصل: <span class="font-medium">{{ $report->contact }}</span></p>
            @endif

            {{-- تغيير الحالة --}}
            <form action="{{ route('reports.status', ['token' => $token, 'report' => $report->id]) }}" method="POST"
                class="flex items-center gap-1.5 mt-3 pt-3 border-t border-slate-100">
                @csrf
                @foreach (\App\Models\Report::STATUSES as $key => $label)
                    <button name="status" value="{{ $key }}"
                        class="text-xs font-bold px-3 py-1.5 rounded-lg transition {{ $report->status === $key ? 'bg-rail-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </form>
        </div>
    @empty
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center text-slate-500">
            <x-icon name="flag" class="w-10 h-10 mx-auto mb-2 text-slate-300"/>
            مفيش بلاغات{{ $activeStatus ? ' بالحالة دي' : '' }}.
        </div>
    @endforelse
@endsection
