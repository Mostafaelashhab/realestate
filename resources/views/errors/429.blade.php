@extends('layouts.app')

@section('bare', '1')
@section('title', 'ثواني بس')

@php
    // مدة الانتظار من ترويسة Retry-After لو متاحة.
    $retry = 0;
    try {
        $retry = (int) (($exception?->getHeaders()['Retry-After'] ?? 0));
    } catch (\Throwable $e) {
        $retry = 0;
    }
@endphp

@section('content')
    <div class="min-h-screen flex flex-col items-center justify-center text-center px-6 py-16">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl shadow-slate-300/40 ring-1 ring-slate-100 p-8">
            {{-- أيقونة --}}
            <div class="relative w-24 h-24 mx-auto mb-5">
                <span class="absolute inset-0 rounded-full bg-rail-100 animate-ping opacity-60"></span>
                <span class="relative w-24 h-24 grid place-items-center rounded-full bg-linear-to-br from-rail-500 to-rail-700 text-white shadow-lg">
                    <x-icon name="clock" class="w-11 h-11" />
                </span>
            </div>

            <h1 class="text-2xl font-extrabold text-slate-800">هدّي شوية</h1>
            <p class="text-slate-500 mt-2 leading-relaxed">
                بعت طلبات كتير في وقت قصير. استنى شوية صغيّرة وحاول تاني.
            </p>

            {{-- عدّاد تنازلي (لو معروف وقت الانتظار) --}}
            <div id="retry-wrap" @if ($retry <= 0) hidden @endif class="mt-5">
                <div class="text-4xl font-extrabold text-rail-700 tabular-nums" id="retry-count">{{ $retry }}</div>
                <div class="text-xs text-slate-400 mt-1">ثانية على المحاولة</div>
            </div>

            <button type="button" id="retry-btn" onclick="location.reload()"
                class="mt-6 w-full inline-flex items-center justify-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-bold rounded-2xl px-4 py-3 transition disabled:opacity-50">
                <x-icon name="refresh" class="w-5 h-5" /> حاول تاني
            </button>

            <a href="{{ route('home') }}" class="mt-3 inline-flex items-center justify-center gap-1.5 text-sm font-bold text-slate-500 hover:text-rail-600 transition">
                <x-icon name="home" class="w-4 h-4" /> الرئيسية
            </a>
        </div>
    </div>

    <script>
        (() => {
            let n = {{ $retry }};
            if (n <= 0) return;
            const wrap = document.getElementById('retry-wrap');
            const count = document.getElementById('retry-count');
            const btn = document.getElementById('retry-btn');
            btn.disabled = true;
            const tick = () => {
                n -= 1;
                if (n <= 0) { btn.disabled = false; wrap.hidden = true; return; }
                count.textContent = n;
                setTimeout(tick, 1000);
            };
            setTimeout(tick, 1000);
        })();
    </script>
@endsection
