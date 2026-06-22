@extends('layouts.app')

@section('title', 'تسجيل الدخول')

@section('content')
    <div class="max-w-sm mx-auto">
        <h1 class="text-xl font-bold mb-1">تسجيل الدخول</h1>
        <p class="text-sm text-slate-500 mb-4">ادخل عشان تفعّل التنبيهات وتشوف طلباتك على أي جهاز.</p>

        @if ($errors->any())
            <div class="bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 space-y-3">
            @csrf
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="البريد الإلكتروني" autocomplete="email"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-rail-500/30 focus:border-rail-500 focus:bg-white transition">
            <input type="password" name="password" required placeholder="كلمة المرور" autocomplete="current-password"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-rail-500/30 focus:border-rail-500 focus:bg-white transition">
            <label class="flex items-center gap-2 text-sm text-slate-500"><input type="checkbox" name="remember" class="accent-rail-600"> فضّلني مسجّل</label>
            <button type="submit" class="w-full bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-3.5 transition shadow-lg shadow-rail-600/25">دخول</button>
        </form>

        <p class="text-sm text-slate-500 text-center mt-4">معندكش حساب؟ <a href="{{ route('register') }}" class="text-rail-700 font-bold hover:underline">سجّل دلوقتي</a></p>
    </div>
@endsection
