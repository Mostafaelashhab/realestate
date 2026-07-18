@props(['code' => '', 'title' => '', 'message' => '', 'icon' => 'alert', 'color' => 'from-rail-500 to-rail-700'])

<div class="min-h-screen flex flex-col items-center justify-center text-center px-6 py-16">
    <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl shadow-slate-300/40 ring-1 ring-slate-100 p-8">
        <div class="relative w-24 h-24 mx-auto mb-5">
            <span class="absolute inset-0 rounded-full bg-slate-100"></span>
            <span class="relative w-24 h-24 grid place-items-center rounded-full bg-linear-to-br {{ $color }} text-white shadow-lg">
                <x-icon :name="$icon" class="w-11 h-11" />
            </span>
        </div>
        @if ($code)
            <div class="text-4xl font-extrabold text-slate-300 mb-1 tabular-nums">{{ $code }}</div>
        @endif
        <h1 class="text-xl font-extrabold text-slate-800">{{ $title }}</h1>
        <p class="text-slate-500 mt-2 leading-relaxed">{{ $message }}</p>

        <a href="{{ url('/') }}" class="mt-6 w-full inline-flex items-center justify-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-bold rounded-2xl px-4 py-3 transition">
            <x-icon name="home" class="w-5 h-5" /> الرئيسية
        </a>
        <button type="button" onclick="history.back()" class="mt-3 text-sm font-bold text-slate-500 hover:text-rail-600 transition">رجوع</button>

        {{ $slot }}
    </div>
</div>
