@extends('layouts.app')

@section('hideHeader', '1')
@section('title', 'بوست في مجتمع الركّاب')

@section('content')
    @php
        $catColors = ['question' => 'bg-sky-100 text-sky-700', 'news' => 'bg-emerald-100 text-emerald-700', 'warning' => 'bg-amber-100 text-amber-700', 'delay' => 'bg-orange-100 text-orange-700', 'complaint' => 'bg-rose-100 text-rose-700', 'general' => 'bg-rail-100 text-rail-700'];
    @endphp

    {{-- شريط علوي --}}
    <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
        <a href="{{ route('complaints.index') }}" aria-label="رجوع" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
        </a>
        <h1 class="font-extrabold text-slate-800">البوست والردود</h1>
    </div>

    {{-- البوست --}}
    <article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 mb-4">
        <div class="flex items-center gap-2.5">
            <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-50 text-rail-700 font-bold">{{ mb_substr($complaint->user->name ?? 'راكب', 0, 1) }}</span>
            <div class="flex-1 min-w-0">
                @if ($complaint->user)
                    <a href="{{ route('profile', $complaint->user) }}" class="font-bold text-slate-800 text-sm truncate block hover:text-rail-600 transition">{{ $complaint->user->name }}</a>
                @else
                    <div class="font-bold text-slate-800 text-sm truncate">راكب</div>
                @endif
                <div class="text-[11px] text-slate-400">{{ $complaint->created_at->diffForHumans() }}</div>
            </div>
            <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $catColors[$complaint->category] ?? $catColors['general'] }}">{{ \App\Models\Complaint::CATEGORIES[$complaint->category] ?? 'عام' }}</span>
        </div>

        <p class="text-[15px] text-slate-700 leading-relaxed mt-3 whitespace-pre-line">{{ $complaint->body }}</p>

        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-50">
            @if ($complaint->train)
                <a href="{{ route('complaints.index', ['train' => $complaint->train->number]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-rail-700 bg-rail-50 rounded-full px-2.5 py-1">
                    <x-icon name="train" class="w-3.5 h-3.5"/> قطار {{ $complaint->train->number }}
                </a>
            @endif
            <button type="button" data-like data-url="{{ route('complaints.like', $complaint) }}"
                class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold {{ $liked ? 'text-rose-600' : 'text-slate-400' }} active:scale-90 transition"
                @guest onclick="location.href='{{ route('login') }}';return false;" @endguest>
                <x-icon name="heart" class="w-5 h-5"/>
                <span data-like-count>{{ $complaint->likers_count }}</span>
            </button>
        </div>
    </article>

    {{-- الردود --}}
    <h2 id="comments" class="font-extrabold text-slate-800 mb-3 scroll-mt-4">الردود ({{ $complaint->comments->count() }})</h2>

    @auth
        <form action="{{ route('complaints.comment', $complaint) }}" method="POST" class="flex items-end gap-2 mb-4">
            @csrf
            <div class="flex-1">
                <textarea name="body" rows="1" maxlength="600" required placeholder="اكتب ردّك…"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm focus:border-rail-500 focus:outline-none">{{ old('body') }}</textarea>
            </div>
            <button type="submit" class="shrink-0 bg-rail-600 hover:bg-rail-700 active:scale-95 text-white font-bold rounded-2xl px-5 py-3 transition">ردّ</button>
        </form>
        @error('body')<p class="text-xs text-red-600 -mt-2 mb-3">{{ $message }}</p>@enderror
    @else
        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm text-rail-700 font-bold text-sm px-4 py-3 mb-4 hover:ring-rail-200 transition">
            سجّل دخول عشان تردّ
        </a>
    @endauth

    <div class="space-y-3">
        @forelse ($complaint->comments as $cm)
            <div class="flex items-start gap-2.5">
                <span class="w-9 h-9 shrink-0 grid place-items-center rounded-full bg-slate-100 text-slate-600 font-bold text-sm">{{ mb_substr($cm->user->name ?? 'راكب', 0, 1) }}</span>
                <div class="flex-1 min-w-0 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm px-3.5 py-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-slate-800 text-sm truncate">{{ $cm->user->name ?? 'راكب' }}</span>
                        <span class="text-[11px] text-slate-400 shrink-0">{{ $cm->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-700 leading-relaxed mt-1 whitespace-pre-line">{{ $cm->body }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-400 text-center py-4">لسه مفيش ردود — كن أول من يردّ.</p>
        @endforelse
    </div>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name=csrf-token]')?.content;
            const btn = document.querySelector('[data-like]');
            if (!btn || btn.getAttribute('onclick')) return;
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
        })();
    </script>
@endsection
