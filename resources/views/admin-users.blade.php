@extends('layouts.app')

@section('title', 'المستخدمين — إظهار المقاعد')

@section('content')
    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('admin') }}" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600"><x-icon name="chevron-right" class="w-5 h-5"/></a>
        <div>
            <h1 class="text-lg font-extrabold text-slate-800">إظهار المقاعد لكل حساب</h1>
            <p class="text-xs text-slate-500">فعّل ميزة المقاعد لحسابات مختارة</p>
        </div>
    </div>

    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ session('ok') }}</div>
    @endif

    <form method="GET" class="relative mb-4">
        <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none"/>
        <input type="text" name="q" value="{{ $q }}" placeholder="ابحث بالاسم أو الإيميل…"
            class="w-full rounded-2xl border border-slate-200 bg-white ps-9 pe-3 py-3 text-sm focus:border-rail-500 focus:outline-none">
    </form>

    <div class="space-y-2.5">
        @forelse ($users as $user)
            <div class="flex items-center gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-3">
                <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-50 text-rail-700 font-bold">{{ mb_substr($user->name, 0, 1) }}</span>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-slate-800 text-sm truncate">{{ $user->name }}</div>
                    <div class="text-[11px] text-slate-400 truncate">{{ $user->email }}</div>
                </div>
                <form method="POST" action="{{ route('admin.users.seats', $user) }}">
                    @csrf
                    <button type="submit" class="text-xs font-bold rounded-full px-3.5 py-2 transition {{ $user->can_see_seats ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $user->can_see_seats ? 'المقاعد مفعّلة ✓' : 'تفعيل المقاعد' }}
                    </button>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-400 text-center py-6">مفيش مستخدمين{{ $q ? ' بالبحث ده' : '' }}.</p>
        @endforelse
    </div>

    <div class="mt-5">{{ $users->links() }}</div>
@endsection
