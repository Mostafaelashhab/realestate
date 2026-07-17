@extends('layouts.app')

@section('hideHeader', '1')
@section('title', 'شكاوى الركّاب')
@section('og_desc', 'شارك شكواك أو تجربتك مع قطارات مصر، وشوف آراء وشكاوى باقي الركّاب.')

@section('content')
    {{-- شريط علوي --}}
    <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
        <a href="{{ route('home') }}" aria-label="رجوع" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="font-extrabold text-slate-800 leading-tight">شكاوى الركّاب</h1>
            <p class="text-[11px] text-slate-400">شارك تجربتك وساعد غيرك</p>
        </div>
    </div>

    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ session('ok') }}</div>
    @endif

    {{-- نموذج النشر --}}
    @auth
        <form action="{{ route('complaints.store') }}" method="POST" class="bg-white rounded-3xl shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 p-4 mb-5">
            @csrf
            <div class="flex items-center gap-2.5 mb-3">
                <span class="w-9 h-9 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="font-bold text-slate-700 text-sm">اكتب شكوتك أو تجربتك</span>
            </div>
            <textarea name="body" rows="3" maxlength="1000" required placeholder="إيه اللي حصل؟ (القطر، الميعاد، النظافة، الزحام…)"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">{{ old('body') }}</textarea>
            @error('body')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            <div class="flex items-center gap-2 mt-3 flex-wrap">
                <div class="relative">
                    <select name="category" class="appearance-none rounded-xl bg-slate-100 text-slate-700 text-xs font-bold ps-3 pe-8 py-2 focus:outline-none">
                        @foreach (\App\Models\Complaint::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg viewBox="0 0 24 24" class="absolute top-1/2 -translate-y-1/2 end-2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </div>
                <input type="text" name="train_number" inputmode="numeric" placeholder="رقم القطر (اختياري)"
                    class="flex-1 min-w-32 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold px-3 py-2 focus:outline-none focus:bg-white focus:ring-1 focus:ring-rail-300">
                <button type="submit" class="ms-auto bg-rail-600 hover:bg-rail-700 active:scale-95 text-white font-bold text-sm rounded-xl px-5 py-2 transition">انشر</button>
            </div>
        </form>
    @else
        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 bg-white rounded-3xl ring-1 ring-slate-100 shadow-sm text-rail-700 font-bold text-sm px-4 py-4 mb-5 hover:ring-rail-200 transition">
            سجّل دخول عشان تنشر شكوتك
        </a>
    @endauth

    {{-- الشكاوى --}}
    @php
        $catColors = ['delay' => 'bg-amber-100 text-amber-700', 'cleanliness' => 'bg-sky-100 text-sky-700', 'crowding' => 'bg-rose-100 text-rose-700', 'staff' => 'bg-violet-100 text-violet-700', 'other' => 'bg-slate-100 text-slate-600', 'general' => 'bg-rail-100 text-rail-700'];
    @endphp
    <div class="space-y-3">
        @forelse ($complaints as $c)
            @php $liked = in_array($c->id, $likedIds); @endphp
            <article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-50 text-rail-700 font-bold">{{ mb_substr($c->user->name ?? 'راكب', 0, 1) }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-800 text-sm truncate">{{ $c->user->name ?? 'راكب' }}</div>
                        <div class="text-[11px] text-slate-400">{{ $c->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $catColors[$c->category] ?? $catColors['general'] }}">{{ \App\Models\Complaint::CATEGORIES[$c->category] ?? 'عامة' }}</span>
                </div>

                <p class="text-sm text-slate-700 leading-relaxed mt-3 whitespace-pre-line">{{ $c->body }}</p>

                <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-50">
                    @if ($c->train)
                        <a href="{{ route('trains.show', $c->train) }}" class="inline-flex items-center gap-1 text-xs font-bold text-rail-700 bg-rail-50 rounded-full px-2.5 py-1">
                            <x-icon name="train" class="w-3.5 h-3.5"/> قطار {{ $c->train->number }}
                        </a>
                    @endif
                    <button type="button" data-like data-url="{{ route('complaints.like', $c) }}" data-liked="{{ $liked ? '1' : '0' }}"
                        class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold {{ $liked ? 'text-rose-600' : 'text-slate-400' }} active:scale-90 transition"
                        @guest onclick="location.href='{{ route('login') }}';return false;" @endguest>
                        <x-icon name="heart" class="w-5 h-5"/>
                        <span data-like-count>{{ $c->likers_count }}</span>
                    </button>
                </div>
            </article>
        @empty
            <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100"><x-icon name="flag" class="w-7 h-7"/></div>
                <p class="font-bold text-slate-700">لسه مفيش شكاوى</p>
                <p class="text-sm text-slate-500 mt-1">كن أول من يشارك تجربته.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $complaints->links() }}</div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name=csrf-token]')?.content;
            document.querySelectorAll('[data-like]').forEach(btn => {
                if (btn.getAttribute('onclick')) return; // ضيف → لوجين
                btn.addEventListener('click', async () => {
                    const countEl = btn.querySelector('[data-like-count]');
                    try {
                        const r = await fetch(btn.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
                        if (r.status === 419 || r.status === 401) { location.href = '{{ route('login') }}'; return; }
                        const d = await r.json();
                        if (!d.ok) return;
                        countEl.textContent = d.count;
                        btn.classList.toggle('text-rose-600', d.liked);
                        btn.classList.toggle('text-slate-400', !d.liked);
                        try { navigator.vibrate?.(8); } catch (e) {}
                    } catch (e) {}
                });
            });
        })();
    </script>
@endsection
