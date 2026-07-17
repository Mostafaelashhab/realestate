@extends('layouts.app')

@section('hideHeader', '1')
@section('title', 'التنبيهات')

@section('content')
    {{-- شريط علوي --}}
    <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
        <a href="{{ route('home') }}" aria-label="رجوع" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
        </a>
        <h1 class="font-extrabold text-slate-800">التنبيهات</h1>
    </div>

    <div class="space-y-2.5">
        @forelse ($notifications as $n)
            <a href="{{ $n->url ?? '#' }}" class="flex items-start gap-3 rounded-2xl p-3.5 ring-1 shadow-sm transition {{ $n->read_at ? 'bg-white ring-slate-100' : 'bg-rail-50 ring-rail-100' }}">
                <span class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-rail-100 text-rail-700"><x-icon :name="$n->icon ?: 'bell'" class="w-5 h-5"/></span>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-slate-800 text-sm">{{ $n->title }}</div>
                    @if ($n->body)<div class="text-xs text-slate-500 mt-0.5 leading-relaxed line-clamp-2">{{ $n->body }}</div>@endif
                    <div class="text-[11px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</div>
                </div>
                @unless ($n->read_at)<span class="w-2.5 h-2.5 rounded-full bg-rail-500 shrink-0 mt-1"></span>@endunless
            </a>
        @empty
            <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100"><x-icon name="bell" class="w-7 h-7"/></div>
                <p class="font-bold text-slate-700">لسه مفيش تنبيهات</p>
                <p class="text-sm text-slate-500 mt-1">تابع قطارك وهتوصلك تنبيهات بأي جديد عنه.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $notifications->links() }}</div>
@endsection
