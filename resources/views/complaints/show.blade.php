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
            <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full {{ $complaint->anonymous ? 'bg-slate-200 text-slate-500' : 'bg-rail-50 text-rail-700' }} font-bold">{{ $complaint->anonymous ? '؟' : mb_substr($complaint->user->name ?? 'راكب', 0, 1) }}</span>
            <div class="flex-1 min-w-0">
                @if (! $complaint->anonymous && $complaint->user)
                    <a href="{{ route('profile', $complaint->user) }}" class="font-bold text-slate-800 text-sm truncate block hover:text-rail-600 transition">{{ $complaint->user->name }}</a>
                @else
                    <div class="font-bold text-slate-800 text-sm truncate">{{ $complaint->anonymous ? 'راكب مجهول' : 'راكب' }}</div>
                @endif
                <div class="text-[11px] text-slate-400">{{ $complaint->created_at->diffForHumans() }}</div>
            </div>
            <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $catColors[$complaint->category] ?? $catColors['general'] }}">{{ \App\Models\Complaint::CATEGORIES[$complaint->category] ?? 'عام' }}</span>
        </div>

        <p class="text-[15px] text-slate-700 leading-relaxed mt-3 whitespace-pre-line">{{ $complaint->body }}</p>

        @if ($complaint->train)
            <a href="{{ route('complaints.index', ['train' => $complaint->train->number]) }}" class="mt-2.5 inline-flex items-center gap-1.5 bg-rail-50 text-rail-700 rounded-full px-3 py-1.5 text-xs font-bold">
                <x-icon name="train" class="w-4 h-4"/> قطار {{ $complaint->train->number }}
            </a>
        @endif

        @if ($poll)
            <div id="poll-mount" class="mt-3" data-poll="{{ json_encode($poll) }}"></div>
        @endif

        <div class="flex items-center gap-1 mt-3 pt-3 border-t border-slate-50" data-react-url="{{ route('complaints.like', $complaint) }}">
            <button type="button" data-react data-value="1" @guest onclick="location.href='{{ route('login') }}';return false;" @endguest
                class="inline-flex items-center gap-1.5 text-sm font-bold rounded-full px-3 py-1.5 transition active:scale-90 {{ $myReact === 1 ? 'text-rail-600 bg-rail-50' : 'text-slate-400 hover:bg-slate-50' }}">
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v11M2 13v6a2 2 0 0 0 2 2h13.3a2 2 0 0 0 2-1.7l1.3-8a2 2 0 0 0-2-2.3H14V4a2 2 0 0 0-4 0c0 3-3 6-3 6H4a2 2 0 0 0-2 2z"/></svg>
                <span data-likes>{{ $likes }}</span>
            </button>
            <button type="button" data-react data-value="-1" @guest onclick="location.href='{{ route('login') }}';return false;" @endguest
                class="inline-flex items-center gap-1.5 text-sm font-bold rounded-full px-3 py-1.5 transition active:scale-90 {{ $myReact === -1 ? 'text-rose-600 bg-rose-50' : 'text-slate-400 hover:bg-slate-50' }}">
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V3M22 11V5a2 2 0 0 0-2-2H6.7a2 2 0 0 0-2 1.7l-1.3 8a2 2 0 0 0 2 2.3H10v6a2 2 0 0 0 4 0c0-3 3-6 3-6h3a2 2 0 0 0 2-2z"/></svg>
                <span data-dislikes>{{ $dislikes }}</span>
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
            const LOGIN = @json(route('login'));
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            function pollHtml(poll) {
                if (!poll) return '';
                const total = poll.total || 0;
                const voted = poll.my_vote !== null && poll.my_vote !== undefined;
                return poll.options.map((o, i) => {
                    if (voted) {
                        const pct = total ? Math.round(o.votes / total * 100) : 0; const mine = i === poll.my_vote;
                        return `<div class="relative rounded-xl overflow-hidden bg-slate-100 mb-1.5"><div class="absolute inset-y-0 start-0 ${mine ? 'bg-rail-200' : 'bg-slate-200'}" style="width:${pct}%"></div><div class="relative flex items-center justify-between px-3 py-2 text-sm"><span class="font-bold ${mine ? 'text-rail-800' : 'text-slate-700'}">${esc(o.text)}${mine ? ' ✓' : ''}</span><span class="text-xs text-slate-500">${pct}%</span></div></div>`;
                    }
                    return `<button type="button" data-choice="${i}" class="poll-opt w-full text-start rounded-xl bg-slate-50 ring-1 ring-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-rail-50 hover:ring-rail-300 mb-1.5 transition">${esc(o.text)}</button>`;
                }).join('') + `<div class="text-[11px] text-slate-400 mt-0.5">${total} صوت</div>`;
            }

            // استطلاع
            const mount = document.getElementById('poll-mount');
            let poll = mount ? JSON.parse(mount.dataset.poll) : null;
            const paintPoll = () => { if (mount) mount.innerHTML = pollHtml(poll); };
            paintPoll();
            mount?.addEventListener('click', async (e) => {
                const opt = e.target.closest('[data-choice]'); if (!opt) return;
                @guest location.href = LOGIN; return; @endguest
                try { const r = await fetch(poll.vote_url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ choice: +opt.dataset.choice }) }); if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; } const d = await r.json(); if (d.ok) { poll = d.poll; paintPoll(); } } catch (e) {}
            });

            // تفاعل like/dislike
            const wrap = document.querySelector('[data-react-url]');
            wrap?.addEventListener('click', async (e) => {
                const react = e.target.closest('[data-react]'); if (!react || react.getAttribute('onclick')) return;
                try {
                    const r = await fetch(wrap.dataset.reactUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ value: +react.dataset.value }) });
                    if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; }
                    const d = await r.json(); if (!d.ok) return;
                    wrap.querySelector('[data-likes]').textContent = d.likes; wrap.querySelector('[data-dislikes]').textContent = d.dislikes;
                    const up = wrap.querySelector('[data-value="1"]'), down = wrap.querySelector('[data-value="-1"]');
                    up.classList.toggle('text-rail-600', d.my === 1); up.classList.toggle('bg-rail-50', d.my === 1); up.classList.toggle('text-slate-400', d.my !== 1);
                    down.classList.toggle('text-rose-600', d.my === -1); down.classList.toggle('bg-rose-50', d.my === -1); down.classList.toggle('text-slate-400', d.my !== -1);
                    try { navigator.vibrate?.(8); } catch (e) {}
                } catch (e) {}
            });
        })();
    </script>
@endsection
