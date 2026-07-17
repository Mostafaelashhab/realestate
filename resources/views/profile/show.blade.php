@extends('layouts.app')

@section('hideHeader', '1')
@section('title', "بروفايل {$user->name}")

@section('content')
    @php
        $catColors = ['question' => 'bg-sky-100 text-sky-700', 'news' => 'bg-emerald-100 text-emerald-700', 'warning' => 'bg-amber-100 text-amber-700', 'delay' => 'bg-orange-100 text-orange-700', 'complaint' => 'bg-rose-100 text-rose-700', 'general' => 'bg-rail-100 text-rail-700'];
        $starRow = function ($n) {
            $out = '';
            for ($i = 1; $i <= 5; $i++) {
                $on = $i <= round($n);
                $out .= '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 ' . ($on ? 'text-amber-400' : 'text-slate-300') . '" fill="' . ($on ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3.5l2.6 5.3 5.8.9-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.2-4.1 5.8-.9z"/></svg>';
            }
            return $out;
        };
    @endphp

    {{-- شريط علوي --}}
    <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
        <a href="{{ url()->previous() }}" aria-label="رجوع" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
        </a>
        <h1 class="font-extrabold text-slate-800">البروفايل</h1>
    </div>

    {{-- كارت الهوية + السمعة --}}
    <section class="relative overflow-hidden bg-linear-to-br from-rail-600 via-rail-700 to-rail-900 text-white rounded-3xl p-5 mb-4 shadow-xl shadow-rail-900/25">
        <div class="pointer-events-none absolute -top-10 -start-8 w-40 h-40 rounded-full bg-rail-400/20 blur-2xl"></div>
        <div class="relative flex items-center gap-4">
            <span class="w-16 h-16 shrink-0 grid place-items-center rounded-full bg-white/15 ring-1 ring-white/25 text-2xl font-extrabold">{{ mb_substr($user->name, 0, 1) }}</span>
            <div class="min-w-0">
                <h2 class="text-xl font-extrabold truncate">{{ $user->name }}</h2>
                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $badge[2] }}">{{ $badge[1] }}</span>
                    <span class="inline-flex items-center gap-1 text-xs font-bold bg-white/15 ring-1 ring-white/20 rounded-full px-2.5 py-1">
                        <x-icon name="star" class="w-3.5 h-3.5 text-amber-300"/> {{ number_format($points) }} نقطة
                    </span>
                </div>
                <p class="text-rail-50/70 text-[11px] mt-1.5">عضو منذ {{ $user->created_at->translatedFormat('F Y') }} · {{ $likesReceived }} إعجاب على مشاركاته</p>
            </div>
        </div>

        <div class="relative mt-4 grid grid-cols-4 gap-2 text-center">
            @foreach ($stats as $s)
                <div class="rounded-2xl bg-white/10 ring-1 ring-white/10 py-2.5">
                    <div class="text-lg font-extrabold leading-none">{{ $s['value'] }}</div>
                    <div class="text-[11px] text-rail-50/70 mt-1">{{ $s['label'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- بوستاته --}}
    <h3 class="font-extrabold text-slate-800 mb-3">مشاركاته في المجتمع</h3>
    <div class="space-y-3 mb-6">
        @forelse ($recentPosts as $c)
            <a href="{{ route('complaints.show', $c) }}" class="block bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 hover:ring-rail-200 transition">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-[11px] font-bold rounded-full px-2.5 py-1 {{ $catColors[$c->category] ?? $catColors['general'] }}">{{ \App\Models\Complaint::CATEGORIES[$c->category] ?? 'عام' }}</span>
                    <span class="text-[11px] text-slate-400">{{ $c->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed line-clamp-3">{{ $c->body }}</p>
                <div class="flex items-center gap-3 text-xs text-slate-400 mt-2">
                    <span class="inline-flex items-center gap-1"><x-icon name="heart" class="w-4 h-4"/> {{ $c->likers_count }}</span>
                    <span>{{ $c->comments_count }} ردّ</span>
                    @if ($c->train)<span class="text-rail-600 font-bold">قطار {{ $c->train->number }}</span>@endif
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-400 text-center py-3">لسه مفيش مشاركات.</p>
        @endforelse
    </div>

    {{-- تقييماته --}}
    @if ($recentReviews->isNotEmpty())
        <h3 class="font-extrabold text-slate-800 mb-3">تقييماته للقطارات</h3>
        <div class="space-y-2.5">
            @foreach ($recentReviews as $rev)
                <a href="{{ $rev->train ? route('trains.show', $rev->train) : '#' }}" class="block bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-3.5 hover:ring-rail-200 transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-slate-800 text-sm">قطار {{ $rev->train->number ?? '—' }}</span>
                        <span class="inline-flex gap-0.5">{!! $starRow($rev->rating) !!}</span>
                    </div>
                    @if ($rev->comment)<p class="text-sm text-slate-600 mt-1.5 leading-relaxed line-clamp-2">{{ $rev->comment }}</p>@endif
                </a>
            @endforeach
        </div>
    @endif
@endsection
