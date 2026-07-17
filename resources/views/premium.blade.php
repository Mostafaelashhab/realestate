@extends('layouts.app')

@section('title', 'قطارات مصر Premium')

@section('content')
    @php $premium = (bool) auth()->user()?->isPremium(); @endphp

    {{-- هيرو --}}
    <section class="relative overflow-hidden bg-linear-to-br from-rail-600 via-rail-700 to-rail-900 text-white rounded-3xl p-6 mb-5 shadow-xl shadow-rail-900/25">
        <svg class="absolute -top-8 -start-10 w-44 h-44 text-white/10" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100"/><path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8"/>
        </svg>
        <div class="relative">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 bg-white/20 ring-1 ring-white/25 rounded-full px-3 py-1 text-xs font-bold"><x-icon name="star" class="w-3.5 h-3.5"/> Premium</span>
                <span class="inline-flex items-center gap-1 bg-emerald-400 text-emerald-950 rounded-full px-3 py-1 text-xs font-extrabold">مجانًا بالكامل</span>
            </div>
            <h1 class="text-2xl font-extrabold mt-3">تجربة أنضف وأسرع</h1>
            <p class="text-rail-50/90 text-sm mt-1.5 leading-relaxed">تنبيهات ذكية قبل ميعاد قطرك، مزامنة مفضلتك على كل أجهزتك، وتصفّح بدون إعلانات — <b class="text-white">كل ده مجانًا من غير أي رسوم</b>.</p>
        </div>
    </section>

    @if ($premium)
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 text-center">
            <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-emerald-50 text-emerald-600"><x-icon name="check" class="w-8 h-8"/></div>
            <p class="font-extrabold text-lg">إنت مشترك Premium</p>
            <p class="text-sm text-slate-500 mt-1">اشتراكك ساري حتى {{ auth()->user()->premium_until->translatedFormat('j F Y') }}.</p>
            <a href="{{ route('home') }}" class="inline-block mt-4 text-rail-700 font-bold hover:underline">ابحث عن قطار</a>
        </div>
    @else
        @php
            $perks = [];
            if (config('enr.show_seats')) {
                $perks[] = ['icon' => 'seat', 'color' => 'bg-violet-100 text-violet-700', 't' => 'نبّهني أول ما يفضى كرسي', 's' => 'مراقبة تلقائية للقطر المكتمل وإشعار فوري'];
            }
            $perks = array_merge($perks, [
                ['icon' => 'bell', 'color' => 'bg-rail-100 text-rail-700', 't' => 'تنبيهات قبل الميعاد', 's' => 'ذكّرني قبل قيام أي قطر بدون حد'],
                ['icon' => 'heart', 'color' => 'bg-rose-100 text-rose-700', 't' => 'مزامنة المفضلة', 's' => 'قطاراتك المحفوظة على كل أجهزتك'],
                ['icon' => 'star', 'color' => 'bg-amber-100 text-amber-700', 't' => 'بدون إعلانات', 's' => 'تصفّح أنضف وأسرع'],
            ]);
        @endphp
        <div class="flex items-center justify-between gap-2 mb-3">
            <h2 class="font-extrabold text-slate-800">مميزات Premium</h2>
            <span class="text-xs font-bold text-slate-400">{{ count($perks) }}</span>
        </div>
        <div class="space-y-2.5 mb-4">
            @foreach ($perks as $p)
                <div class="flex items-center gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-3">
                    <span class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl {{ $p['color'] }}"><x-icon :name="$p['icon']" class="w-5 h-5"/></span>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-800 text-sm">{{ $p['t'] }}</div>
                        <div class="text-xs text-slate-400">{{ $p['s'] }}</div>
                    </div>
                    <x-icon name="check" class="w-5 h-5 text-emerald-500 shrink-0"/>
                </div>
            @endforeach
        </div>

        @guest
            <a href="{{ route('login') }}" class="block w-full text-center bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-4 transition shadow-lg shadow-rail-600/25">
                سجّل دخول وفعّلها مجانًا
            </a>
            <p class="text-center text-xs text-slate-400 mt-2">مجانًا بالكامل — من غير أي رسوم ولا اشتراك مدفوع.</p>
        @else
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center">
                <div class="w-11 h-11 mx-auto mb-2 grid place-items-center rounded-2xl bg-emerald-100 text-emerald-600"><x-icon name="check" class="w-6 h-6"/></div>
                <p class="text-sm font-extrabold text-emerald-800">المزايا دي كلها مجانية ومفعّلة ليك</p>
                <p class="text-xs text-emerald-700/80 mt-1">من غير أي رسوم — استمتع بيها على طول.</p>
            </div>
        @endguest
    @endif
@endsection
