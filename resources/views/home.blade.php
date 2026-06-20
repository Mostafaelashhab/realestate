@extends('layouts.app')

@section('title', 'مواعيد وأسعار القطارات')

@section('content')
    {{-- ترحيب --}}
    <section class="bg-linear-to-l from-rail-700 to-rail-600 text-white rounded-3xl p-5 mb-4 shadow-lg shadow-rail-700/20">
        <h1 class="text-2xl font-extrabold mb-1">رايح فين؟</h1>
        <p class="text-rail-50/90 text-sm">مواعيد وأسعار قطارات مصر، وتتبع مكان القطر لحظيًا.</p>
        <div class="mt-3 inline-flex items-center gap-2 bg-white/15 rounded-full px-3 py-1 text-xs font-bold">
            🚆 {{ number_format($trainCount) }} قطار على الشبكة
        </div>
    </section>

    @error('number')
        <div class="bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ $message }}</div>
    @enderror

    {{-- بحث المحطتين --}}
    <section class="bg-white rounded-3xl shadow-sm p-4 mb-4">
        <form id="search-form" action="{{ route('search') }}" method="GET" class="space-y-3">
            <datalist id="stations-list">
                @foreach ($stations as $station)
                    <option value="{{ $station->name_ar }}"></option>
                @endforeach
            </datalist>

            <div class="relative">
                <span class="absolute top-1/2 -translate-y-1/2 start-3 text-rail-600">●</span>
                <input list="stations-list" id="from_name" autocomplete="off" placeholder="محطة القيام" required
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">
                <input type="hidden" name="from" id="from">
            </div>

            <div class="relative">
                <span class="absolute top-1/2 -translate-y-1/2 start-3 text-amber-500">📍</span>
                <input list="stations-list" id="to_name" autocomplete="off" placeholder="محطة الوصول" required
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">
                <input type="hidden" name="to" id="to">
            </div>

            <div class="flex gap-3">
                <input type="date" name="date" value="{{ now()->toDateString() }}"
                    class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">
            </div>

            <button type="submit"
                class="w-full bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-3.5 transition shadow-lg shadow-rail-600/25">
                ابحث عن القطارات
            </button>
        </form>

        <script>
            const stationIds = @json($stations->pluck('id', 'name_ar'));
            function bind(nameField, idField) {
                const input = document.getElementById(nameField);
                const hidden = document.getElementById(idField);
                input.addEventListener('input', () => { hidden.value = stationIds[input.value.trim()] ?? ''; });
            }
            bind('from_name', 'from');
            bind('to_name', 'to');
        </script>

        <div class="flex items-center gap-3 my-3 text-xs text-slate-400">
            <span class="flex-1 border-t border-slate-100"></span>
            أو برقم القطار
            <span class="flex-1 border-t border-slate-100"></span>
        </div>

        <form action="{{ route('search') }}" method="GET" class="flex gap-2">
            <input type="text" name="number" inputmode="numeric" placeholder="رقم القطار (مثال: 936)" required
                class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 active:scale-95 text-white font-bold rounded-2xl px-5 transition whitespace-nowrap">
                اعرض
            </button>
        </form>
    </section>

    {{-- اختصارات --}}
    <section class="grid grid-cols-2 gap-3">
        <a href="{{ route('live') }}" class="bg-white rounded-3xl shadow-sm p-4 active:scale-95 transition">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-emerald-50 text-2xl mb-2">📍</div>
            <h3 class="font-bold text-sm">القطر فين دلوقتي</h3>
            <p class="text-xs text-slate-500 mt-0.5">موقع تقديري لحظي لكل قطار</p>
        </a>
        <a href="{{ route('fines') }}" class="bg-white rounded-3xl shadow-sm p-4 active:scale-95 transition">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-amber-50 text-2xl mb-2">⚖️</div>
            <h3 class="font-bold text-sm">الغرامات</h3>
            <p class="text-xs text-slate-500 mt-0.5">المخالفات وقيمة كل غرامة</p>
        </a>
    </section>
@endsection
