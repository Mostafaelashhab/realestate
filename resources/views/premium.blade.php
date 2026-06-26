@extends('layouts.app')

@section('title', 'اشتراك Premium')

@section('content')
    @php $premium = (bool) auth()->user()?->isPremium(); @endphp

    <section class="relative overflow-hidden bg-linear-to-br from-amber-500 via-amber-500 to-amber-600 text-white rounded-3xl p-6 mb-5 shadow-xl shadow-amber-500/25">
        <svg class="absolute -top-8 -start-10 w-44 h-44 text-white/10" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M20 0v100M40 0v100M60 0v100M80 0v100"/><path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8"/>
        </svg>
        <div class="relative">
            <span class="inline-flex items-center gap-1.5 bg-white/20 ring-1 ring-white/25 rounded-full px-3 py-1 text-xs font-bold"><x-icon name="star" class="w-3.5 h-3.5"/> Premium</span>
            <h1 class="text-2xl font-extrabold mt-3">اعرف المقاعد أول واحد</h1>
            <p class="text-amber-50/90 text-sm mt-1.5 leading-relaxed">القطر مكتمل؟ سيبنا نراقبه، وأول ما يفضى كرسي يوصلك إشعار فورًا — من غير ما تفضل تفتح الصفحة.</p>
        </div>
    </section>

    @if ($premium)
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 text-center">
            <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-emerald-50 text-emerald-600"><x-icon name="check" class="w-8 h-8"/></div>
            <p class="font-extrabold text-lg">إنت مشترك Premium ✓</p>
            <p class="text-sm text-slate-500 mt-1">اشتراكك ساري حتى {{ auth()->user()->premium_until->translatedFormat('j F Y') }}.</p>
            <a href="{{ route('home') }}" class="inline-block mt-4 text-rail-700 font-bold hover:underline">ابحث عن قطار</a>
        </div>
    @else
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4">
            <h2 class="font-bold mb-3">مميزات Premium</h2>
            <ul class="space-y-3 text-sm">
                <li class="flex items-start gap-2.5"><x-icon name="check" class="w-5 h-5 text-emerald-600 shrink-0"/> <span><b>نبّهني أول ما يفضى كرسي</b> — مراقبة تلقائية للقطر المكتمل وإشعار فوري.</span></li>
                <li class="flex items-start gap-2.5"><x-icon name="check" class="w-5 h-5 text-emerald-600 shrink-0"/> <span>تنبيهات قبل ميعاد القطر بدون حد.</span></li>
                <li class="flex items-start gap-2.5"><x-icon name="check" class="w-5 h-5 text-emerald-600 shrink-0"/> <span>مزامنة مفضلتك على كل أجهزتك.</span></li>
                <li class="flex items-start gap-2.5"><x-icon name="check" class="w-5 h-5 text-emerald-600 shrink-0"/> <span>تجربة بدون إعلانات.</span></li>
            </ul>
        </div>

        @guest
            <a href="{{ route('login') }}" class="block w-full text-center bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-4 transition shadow-lg shadow-rail-600/25">
                سجّل دخول للاشتراك
            </a>
        @else
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-center">
                <p class="text-sm font-bold text-amber-800">الاشتراك هيتفعّل قريب</p>
                <p class="text-xs text-amber-700 mt-1">بنجهّز طريقة الدفع. سيب إيميلك مسجّل وهنبلّغك أول ما يفتح.</p>
            </div>
        @endguest
    @endif
@endsection
