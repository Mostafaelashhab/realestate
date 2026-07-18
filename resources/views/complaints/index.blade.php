@extends('layouts.app')

@section('hideHeader', '1')
@section('title', $train ? "نقاش قطار {$train->number}" : 'مجتمع الركّاب')
@section('og_desc', 'مجتمع ركّاب قطارات مصر — اسأل، بلّغ، شارك خبر، وساعد غيرك.')

@php
    $catColors = ['question' => 'bg-sky-100 text-sky-700', 'poll' => 'bg-indigo-100 text-indigo-700', 'news' => 'bg-emerald-100 text-emerald-700', 'warning' => 'bg-amber-100 text-amber-700', 'experience' => 'bg-teal-100 text-teal-700', 'complaint' => 'bg-rose-100 text-rose-700', 'ticket' => 'bg-emerald-100 text-emerald-700', 'general' => 'bg-rail-100 text-rail-700'];
    $stationsJs = ($stations ?? collect())->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar])->values();

    // بيانات استطلاع لبوست (للعرض المبدئي).
    $pollOf = function ($c) {
        if ($c->category !== 'poll' || ! is_array($c->poll_options)) {
            return null;
        }
        $opts = [];
        foreach ($c->poll_options as $i => $t) {
            $opts[] = ['text' => $t, 'votes' => $c->pollVotes->where('choice', $i)->count()];
        }
        return [
            'options' => $opts,
            'total' => $c->pollVotes->count(),
            'my_vote' => auth()->id() ? optional($c->pollVotes->firstWhere('user_id', auth()->id()))->choice : null,
            'vote_url' => route('complaints.vote', $c),
        ];
    };
@endphp

@section('content')
    @if ($train)
        <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
            <a href="{{ route('trains.show', $train) }}" aria-label="رجوع" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="font-extrabold text-slate-800 leading-tight">نقاش قطار {{ $train->number }}</h1>
                <p class="text-[11px] text-slate-400">كل اللي بيتقال عن القطر ده</p>
            </div>
        </div>
    @else
        {{-- هيدر خفيف --}}
        <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
            <h1 class="flex-1 min-w-0 font-extrabold text-xl text-slate-800 truncate">المجتمع</h1>
            <a href="{{ route('trains.hub') }}" aria-label="بحث" class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-white ring-1 ring-slate-100 shadow-sm text-slate-600"><x-icon name="search" class="w-5 h-5"/></a>
            @auth
                <a href="{{ route('notifications.index') }}" aria-label="التنبيهات" class="relative w-10 h-10 shrink-0 grid place-items-center rounded-full bg-white ring-1 ring-slate-100 shadow-sm text-slate-600">
                    <x-icon name="bell" class="w-5 h-5"/>
                    <span id="notif-badge" hidden class="absolute -top-1 -end-1 min-w-[18px] h-[18px] px-1 grid place-items-center rounded-full bg-rose-500 text-white text-[10px] font-extrabold"></span>
                </a>
                <a href="{{ route('profile', auth()->user()) }}" aria-label="بروفايلي" class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
                <script>
                    (() => {
                        const badge = document.getElementById('notif-badge'); if (!badge) return;
                        const url = @json(route('notifications.unread'));
                        async function tick() { if (document.hidden) return; try { const r = await fetch(url, { headers: { Accept: 'application/json' } }); const d = await r.json(); if (d.count > 0) { badge.textContent = d.count > 99 ? '99+' : d.count; badge.hidden = false; } else badge.hidden = true; } catch (e) {} }
                        tick(); setInterval(tick, 25000);
                    })();
                </script>
            @else
                <a href="{{ route('login') }}" aria-label="دخول" class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-white ring-1 ring-slate-100 shadow-sm text-rail-600"><x-icon name="user" class="w-5 h-5"/></a>
            @endauth
        </div>
    @endif

    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ session('ok') }}</div>
    @endif

    {{-- زر إنشاء بوست (بسيط — يفتح النافذة) --}}
    @auth
        <button type="button" data-open-composer class="w-full flex items-center gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-3 mb-3 text-start active:scale-[.99] transition">
            <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
            <span class="flex-1 text-slate-400 text-sm">اكتب حاجة للركّاب…</span>
            <span class="shrink-0 inline-flex items-center gap-1 text-rail-600"><x-icon name="flag" class="w-5 h-5"/></span>
        </button>
    @else
        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm text-rail-700 font-bold text-sm px-4 py-4 mb-3 hover:ring-rail-200 transition">سجّل دخول وشارك مع المجتمع</a>
    @endauth

    {{-- فلتر الأنواع --}}
    @php $cat = request('category'); @endphp
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 mb-2">
        <a href="{{ request()->fullUrlWithQuery(['category' => null]) }}" class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ ! $cat ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">الكل</a>
        @foreach (\App\Models\Complaint::CATEGORIES as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['category' => $key]) }}" class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $cat === $key ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="flex items-center gap-2 mb-3">
        <button type="button" data-order="new" class="order-btn px-3.5 py-1.5 rounded-full text-xs font-bold bg-rail-600 text-white transition">الأحدث</button>
        <button type="button" data-order="top" class="order-btn px-3.5 py-1.5 rounded-full text-xs font-bold bg-white ring-1 ring-slate-200 text-slate-600 transition">الأكثر تفاعلًا</button>
        <span class="ms-auto inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600"><span class="relative flex w-2 h-2"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex rounded-full w-2 h-2 bg-emerald-500"></span></span> مباشر</span>
    </div>

    <button type="button" id="new-pill" hidden class="mx-auto mb-3 flex items-center gap-1.5 bg-rail-600 text-white text-xs font-bold rounded-full px-4 py-2 shadow-lg shadow-rail-600/30 active:scale-95 transition">
        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
        <span id="new-count">0</span> بوست جديد
    </button>

    {{-- الفيد --}}
    <div id="feed" class="space-y-3">
        @forelse ($complaints as $c)
            @php $myReact = $myReactions[$c->id] ?? 0; $pd = $pollOf($c); @endphp
            <article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4" data-id="{{ $c->id }}" data-ts="{{ $c->created_at->timestamp }}" data-comments-url="{{ route('complaints.comments', $c) }}" data-comment-url="{{ route('complaints.comment', $c) }}">
                <div class="flex items-center gap-2.5">
                    <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full {{ $c->anonymous ? 'bg-slate-200 text-slate-500' : 'bg-rail-50 text-rail-700' }} font-bold">{{ $c->anonymous ? '؟' : mb_substr($c->user->name ?? 'راكب', 0, 1) }}</span>
                    <div class="flex-1 min-w-0">
                        @if (! $c->anonymous && $c->user)
                            <a href="{{ route('profile', $c->user) }}" class="font-bold text-slate-800 text-sm truncate block hover:text-rail-600 transition">{{ $c->user->name }}</a>
                        @else
                            <div class="font-bold text-slate-800 text-sm truncate">{{ $c->anonymous ? 'راكب مجهول' : 'راكب' }}</div>
                        @endif
                        <div class="text-[11px] text-slate-400" data-ago>{{ $c->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $catColors[$c->category] ?? $catColors['general'] }}">{{ \App\Models\Complaint::CATEGORIES[$c->category] ?? 'عام' }}</span>
                </div>

                <a href="{{ route('complaints.show', $c) }}" class="block"><p class="text-sm text-slate-700 leading-relaxed mt-3 whitespace-pre-line line-clamp-4">{{ $c->body }}</p></a>

                @if ($c->train)
                    <a href="{{ route('complaints.index', ['train' => $c->train->number]) }}" class="mt-2.5 inline-flex items-center gap-1.5 bg-rail-50 text-rail-700 rounded-full px-3 py-1.5 text-xs font-bold">
                        <x-icon name="train" class="w-4 h-4"/> قطار {{ $c->train->number }}
                    </a>
                @endif

                @if ($pd)
                    <div class="poll-mount" data-post-id="{{ $c->id }}"><script type="application/json">@json($pd)</script></div>
                @endif

                @if ($c->category === 'ticket')
                    <div class="mt-2 rounded-2xl bg-emerald-50 ring-1 ring-emerald-100 p-3">
                        <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <span class="flex-1 min-w-0 truncate text-end" title="{{ $c->fromStation->name_ar ?? '' }}">{{ $c->fromStation->name_ar ?? '—' }}</span>
                            <x-icon name="arrow-left" class="w-3.5 h-3.5 text-emerald-600 shrink-0"/>
                            <span class="flex-1 min-w-0 truncate" title="{{ $c->toStation->name_ar ?? '' }}">{{ $c->toStation->name_ar ?? '—' }}</span>
                        </div>
                        @if ($c->travel_date || $c->price_egp)
                            <div class="flex items-center justify-between gap-2 mt-2.5">
                                @if ($c->travel_date)
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 bg-white rounded-full px-2.5 py-1 ring-1 ring-emerald-100"><x-icon name="calendar" class="w-3.5 h-3.5"/>{{ $c->travel_date->translatedFormat('l j F') }}</span>
                                @else <span></span> @endif
                                @if ($c->price_egp)<span class="flex items-baseline gap-1"><span class="text-lg font-extrabold text-emerald-700 leading-none">{{ number_format($c->price_egp) }}</span><span class="text-xs font-bold text-emerald-600">ج.م</span></span>@endif
                            </div>
                        @endif
                        @if ($c->contact)
                            <a href="https://wa.me/2{{ preg_replace('/\D/', '', $c->contact) }}" target="_blank" rel="noopener" class="mt-2.5 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[.99] text-white text-sm font-bold rounded-xl px-3 py-2.5 transition"><x-icon name="whatsapp" class="w-4 h-4"/> تواصل على واتساب</a>
                        @endif
                    </div>
                @endif

                <div class="flex items-center gap-1 mt-3 pt-3 border-t border-slate-50" data-react-url="{{ route('complaints.like', $c) }}">
                    <button type="button" data-react data-value="1" class="inline-flex items-center gap-1.5 text-sm font-bold rounded-full px-3 py-1.5 transition {{ $myReact === 1 ? 'text-rail-600 bg-rail-50' : 'text-slate-400 hover:bg-slate-50' }} active:scale-90">
                        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v11M2 13v6a2 2 0 0 0 2 2h13.3a2 2 0 0 0 2-1.7l1.3-8a2 2 0 0 0-2-2.3H14V4a2 2 0 0 0-4 0c0 3-3 6-3 6H4a2 2 0 0 0-2 2z"/></svg>
                        <span data-likes>{{ $c->likes_count }}</span>
                    </button>
                    <button type="button" data-react data-value="-1" class="inline-flex items-center gap-1.5 text-sm font-bold rounded-full px-3 py-1.5 transition {{ $myReact === -1 ? 'text-rose-600 bg-rose-50' : 'text-slate-400 hover:bg-slate-50' }} active:scale-90">
                        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V3M22 11V5a2 2 0 0 0-2-2H6.7a2 2 0 0 0-2 1.7l-1.3 8a2 2 0 0 0 2 2.3H10v6a2 2 0 0 0 4 0c0-3 3-6 3-6h3a2 2 0 0 0 2-2z"/></svg>
                        <span data-dislikes>{{ $c->dislikes_count }}</span>
                    </button>
                    <button type="button" data-toggle-comments class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold text-slate-400 hover:text-rail-600 rounded-full px-3 py-1.5 transition">
                        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8A8.5 8.5 0 0 1 21 11.5z"/></svg>
                        <span data-comments>{{ $c->comments_count }}</span>
                    </button>
                    <button type="button" data-report-post aria-label="إبلاغ" class="inline-flex items-center text-slate-300 hover:text-rose-500 rounded-full px-2 py-1.5 transition">
                        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                    </button>
                </div>
                <div data-comments-section hidden class="mt-3 pt-3 border-t border-slate-50"></div>
            </article>
        @empty
            @if (! request('category'))
                <div id="feed-empty" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center">
                    <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100"><x-icon name="flag" class="w-7 h-7"/></div>
                    <p class="font-bold text-slate-700">لسه مفيش بوستات{{ $train ? ' عن القطر ده' : '' }}</p>
                    <p class="text-sm text-slate-500 mt-1">كن أول من يشارك.</p>
                </div>
            @endif
        @endforelse
    </div>

    <div id="feed-sentinel" class="h-8"></div>
    <div id="feed-loading" hidden class="flex justify-center py-3"><span class="w-6 h-6 border-2 border-slate-200 border-t-rail-500 rounded-full animate-spin"></span></div>

    {{-- ===== نافذة إنشاء بوست ===== --}}
    @auth
        <div id="composer" hidden class="fixed inset-0 z-50 bg-white">
            <form id="post-form" action="{{ route('complaints.store') }}" method="POST" class="mx-auto max-w-xl h-full flex flex-col">
                @csrf
                <input type="hidden" name="category" id="post-category" value="general">
                {{-- رأس --}}
                <div class="shrink-0 flex items-center gap-2 px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3 border-b border-slate-100">
                    <button type="button" data-composer-close aria-label="إغلاق" class="w-9 h-9 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100"><svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
                    <h3 class="font-extrabold text-slate-800 flex-1">بوست جديد</h3>
                    <button type="submit" id="post-submit" class="bg-rail-600 hover:bg-rail-700 active:scale-95 text-white font-bold text-sm rounded-full px-5 py-2 transition">انشر</button>
                </div>

                <div class="flex-1 min-h-0 overflow-y-auto px-4 py-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <div class="min-w-0"><div class="font-bold text-slate-800 text-sm truncate">{{ auth()->user()->name }}</div></div>
                        {{-- نشر كمجهول --}}
                        <label class="ms-auto flex items-center gap-1.5 cursor-pointer select-none">
                            <span class="text-xs font-bold text-slate-500">مجهول</span>
                            <input type="checkbox" name="anonymous" value="1" id="post-anon" class="peer sr-only">
                            <span class="w-9 h-5 rounded-full bg-slate-200 peer-checked:bg-rail-600 relative transition after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4"></span>
                        </label>
                    </div>

                    <textarea name="body" id="post-body" rows="4" maxlength="1000" required placeholder="شارك حاجة مع الركّاب…"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none"></textarea>

                    {{-- نوع البوست --}}
                    <div id="type-chips" class="flex gap-1.5 overflow-x-auto no-scrollbar mt-3">
                        @foreach (\App\Models\Complaint::CATEGORIES as $key => $label)
                            <button type="button" data-type="{{ $key }}" class="type-chip shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition {{ $key === 'general' ? 'bg-rail-600 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    {{-- خيارات الاستطلاع --}}
                    <div id="poll-fields" hidden class="mt-3">
                        <div id="poll-options" class="space-y-2">
                            <input type="text" name="poll_options[]" maxlength="80" placeholder="الخيار 1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                            <input type="text" name="poll_options[]" maxlength="80" placeholder="الخيار 2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                        </div>
                        <button type="button" id="poll-add" class="mt-2 text-xs font-bold text-rail-700">+ أضف خيار</button>
                    </div>

                    {{-- حقول سوق التذاكر --}}
                    <div id="ticket-fields" hidden class="mt-3 grid grid-cols-2 gap-2">
                        <input type="hidden" name="from_station_id" id="tk-from">
                        <input type="hidden" name="to_station_id" id="tk-to">
                        <button type="button" data-tpicker="from" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-start"><span id="tk-from-disp" class="text-slate-400 truncate">من محطة</span><svg viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>
                        <button type="button" data-tpicker="to" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-start"><span id="tk-to-disp" class="text-slate-400 truncate">إلى محطة</span><svg viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>
                        <input type="date" name="travel_date" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                        <input type="number" name="price_egp" min="0" placeholder="السعر (ج.م)" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                        <input type="text" name="contact" inputmode="tel" placeholder="رقم للتواصل (واتساب)" class="col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                    </div>

                    <input type="text" name="train_number" inputmode="numeric" value="{{ $train->number ?? '' }}" placeholder="رقم القطر (اختياري)" class="mt-3 w-full rounded-xl bg-slate-100 text-slate-700 text-sm px-3 py-2.5 focus:outline-none focus:bg-white focus:ring-1 focus:ring-rail-300">
                    <p id="post-err" hidden class="text-xs text-red-600 mt-2"></p>
                </div>
            </form>
        </div>

        {{-- منتقي محطات التذكرة --}}
        <div id="tpicker" hidden class="fixed inset-0 z-[60] bg-white">
            <div class="mx-auto max-w-xl h-full flex flex-col">
                <div class="shrink-0 px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 mb-3">
                        <button type="button" data-tpicker-close aria-label="رجوع" class="w-9 h-9 shrink-0 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100"><svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg></button>
                        <h3 id="tpicker-title" class="font-extrabold text-slate-800 flex-1">اختار المحطة</h3>
                    </div>
                    <div class="relative">
                        <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none"/>
                        <input type="text" id="tpicker-search" placeholder="اكتب اسم المحطة…" autocomplete="off" class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                    </div>
                </div>
                <ul id="tpicker-list" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-3 py-2 pb-[max(1rem,env(safe-area-inset-bottom))]"></ul>
            </div>
        </div>
    @endauth

    <script>
        (() => {
            const IS_AUTH = @json(auth()->check());
            const MY_INITIAL = @json(auth()->user() ? mb_substr(auth()->user()->name, 0, 1) : '؟');
            const REPORT_URL = @json(route('community.report'));
            const LOGIN = @json(route('login'));
            const FEED_URL = @json(route('complaints.feed'));
            const STORE_URL = @json(route('complaints.store'));
            const CATEGORY = @json(request('category'));
            const TRAIN = @json($train->number ?? null);
            const csrf = () => document.querySelector('meta[name=csrf-token]')?.content;
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const CAT = @json($catColors);
            const feed = document.getElementById('feed');

            const heartSvg = '<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
            const chatSvg = '<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8A8.5 8.5 0 0 1 21 11.5z"/></svg>';
            const arrowSvg = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7-7-7 7 7 7"/></svg>';

            const timeAgo = (ts) => { const s = Math.max(0, Math.floor(Date.now() / 1000 - ts)); if (s < 60) return 'دلوقتي'; const m = Math.floor(s / 60); if (m < 60) return `منذ ${m} د`; const h = Math.floor(m / 60); if (h < 24) return `منذ ${h} س`; const d = Math.floor(h / 24); if (d < 30) return `منذ ${d} يوم`; try { return new Date(ts * 1000).toLocaleDateString('ar-EG'); } catch (e) { return ''; } };

            function pollHtml(poll, id) {
                if (!poll) return '';
                const total = poll.total || 0;
                const voted = poll.my_vote !== null && poll.my_vote !== undefined;
                const rows = poll.options.map((o, i) => {
                    if (voted) {
                        const pct = total ? Math.round(o.votes / total * 100) : 0;
                        const mine = i === poll.my_vote;
                        return `<div class="relative rounded-xl overflow-hidden bg-slate-100 mb-1.5"><div class="absolute inset-y-0 start-0 ${mine ? 'bg-rail-200' : 'bg-slate-200'}" style="width:${pct}%"></div><div class="relative flex items-center justify-between px-3 py-2 text-sm"><span class="font-bold ${mine ? 'text-rail-800' : 'text-slate-700'}">${esc(o.text)}${mine ? ' ✓' : ''}</span><span class="text-xs text-slate-500">${pct}%</span></div></div>`;
                    }
                    return `<button type="button" data-choice="${i}" data-vote-url="${poll.vote_url}" class="poll-opt w-full text-start rounded-xl bg-slate-50 ring-1 ring-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-rail-50 hover:ring-rail-300 mb-1.5 transition">${esc(o.text)}</button>`;
                }).join('');
                return `${rows}<div class="text-[11px] text-slate-400 mt-0.5">${total} صوت</div>`;
            }

            const calSvg = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
            const waSvg = '<svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5.1-4.5-.1-.2-1.2-1.5-1.2-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.6-.3.3c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.2.5.3.1.3.1.8-.1 1.2z"/></svg>';
            function ticketBox(p) {
                if (p.category !== 'ticket') return '';
                const from = esc(p.from || '—'), to = esc(p.to || '—');
                const price = p.price ? `<div class="flex items-baseline gap-1"><span class="text-lg font-extrabold text-emerald-700 leading-none">${Number(p.price).toLocaleString('ar-EG')}</span><span class="text-xs font-bold text-emerald-600">ج.م</span></div>` : '';
                const date = p.travel_date ? `<span class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 bg-white rounded-full px-2.5 py-1 ring-1 ring-emerald-100">${calSvg}${esc(p.travel_date)}</span>` : '';
                const wa = p.wa ? `<a href="https://wa.me/2${p.wa}" target="_blank" rel="noopener" class="mt-2.5 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[.99] text-white text-sm font-bold rounded-xl px-3 py-2.5 transition">${waSvg} تواصل على واتساب</a>` : '';
                return `<div class="mt-2 rounded-2xl bg-emerald-50 ring-1 ring-emerald-100 p-3">
                    <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <span class="flex-1 min-w-0 truncate text-end" title="${from}">${from}</span>
                        ${arrowSvg}
                        <span class="flex-1 min-w-0 truncate" title="${to}">${to}</span>
                    </div>
                    ${(date || price) ? `<div class="flex items-center justify-between gap-2 mt-2.5">${date}${price}</div>` : ''}
                    ${wa}
                </div>`;
            }

            const upSvg = '<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v11M2 13v6a2 2 0 0 0 2 2h13.3a2 2 0 0 0 2-1.7l1.3-8a2 2 0 0 0-2-2.3H14V4a2 2 0 0 0-4 0c0 3-3 6-3 6H4a2 2 0 0 0-2 2z"/></svg>';
            const downSvg = '<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V3M22 11V5a2 2 0 0 0-2-2H6.7a2 2 0 0 0-2 1.7l-1.3 8a2 2 0 0 0 2 2.3H10v6a2 2 0 0 0 4 0c0-3 3-6 3-6h3a2 2 0 0 0 2-2z"/></svg>';
            const trainSvg = '<svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8M7 16l-2 4m12-4l2 4"/></svg>';
            function card(p) {
                const name = (!p.anonymous && p.user_url) ? `<a href="${p.user_url}" class="font-bold text-slate-800 text-sm truncate block hover:text-rail-600 transition">${esc(p.user)}</a>` : `<div class="font-bold text-slate-800 text-sm truncate">${esc(p.user)}</div>`;
                const av = p.anonymous ? 'bg-slate-200 text-slate-500' : 'bg-rail-50 text-rail-700';
                const train = p.train_number ? `<a href="${p.train_url}" class="mt-2.5 inline-flex items-center gap-1.5 bg-rail-50 text-rail-700 rounded-full px-3 py-1.5 text-xs font-bold">${trainSvg} قطار ${esc(p.train_number)}</a>` : '';
                const poll = p.poll ? `<div class="poll-mount mt-2" data-post-id="${p.id}">${pollHtml(p.poll, p.id)}</div>` : '';
                const upCls = p.my_reaction === 1 ? 'text-rail-600 bg-rail-50' : 'text-slate-400 hover:bg-slate-50';
                const downCls = p.my_reaction === -1 ? 'text-rose-600 bg-rose-50' : 'text-slate-400 hover:bg-slate-50';
                return `<article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4" data-id="${p.id}" data-ts="${p.ts}" data-comments-url="${p.comments_url}" data-comment-url="${p.comment_url}">
                    <div class="flex items-center gap-2.5">
                        <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full ${av} font-bold">${esc(p.initial)}</span>
                        <div class="flex-1 min-w-0">${name}<div class="text-[11px] text-slate-400" data-ago>${timeAgo(p.ts)}</div></div>
                        <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 ${CAT[p.category] || CAT.general}">${esc(p.category_label)}</span>
                    </div>
                    <a href="${p.url}" class="block"><p class="text-sm text-slate-700 leading-relaxed mt-3 whitespace-pre-line line-clamp-4">${esc(p.body)}</p></a>
                    ${train}${poll}${ticketBox(p)}
                    <div class="flex items-center gap-1 mt-3 pt-3 border-t border-slate-50" data-react-url="${p.like_url}">
                        <button type="button" data-react data-value="1" class="inline-flex items-center gap-1.5 text-sm font-bold rounded-full px-3 py-1.5 transition active:scale-90 ${upCls}">${upSvg}<span data-likes>${p.likes}</span></button>
                        <button type="button" data-react data-value="-1" class="inline-flex items-center gap-1.5 text-sm font-bold rounded-full px-3 py-1.5 transition active:scale-90 ${downCls}">${downSvg}<span data-dislikes>${p.dislikes}</span></button>
                        <button type="button" data-toggle-comments class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold text-slate-400 hover:text-rail-600 rounded-full px-3 py-1.5 transition">${chatSvg}<span data-comments>${p.comments}</span></button>
                        <button type="button" data-report-post aria-label="إبلاغ" class="inline-flex items-center text-slate-300 hover:text-rose-500 rounded-full px-2 py-1.5 transition"><svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg></button>
                    </div>
                    <div data-comments-section hidden class="mt-3 pt-3 border-t border-slate-50"></div>
                </article>`;
            }

            // تركيب استطلاعات البوستات المعروضة من السيرفر
            function mountPolls(scope) {
                (scope || document).querySelectorAll('.poll-mount > script[type="application/json"]').forEach(sc => {
                    try { const data = JSON.parse(sc.textContent); const m = sc.parentElement; m.innerHTML = pollHtml(data, m.dataset.postId); } catch (e) {}
                });
            }
            mountPolls();

            const ids = () => [...feed.querySelectorAll('[data-id]')].map(a => +a.dataset.id);
            const maxId = () => ids().length ? Math.max(...ids()) : 0;
            const minId = () => ids().length ? Math.min(...ids()) : 0;
            let order = 'new';
            const qs = (extra) => new URLSearchParams({ ...(CATEGORY ? { category: CATEGORY } : {}), ...(TRAIN ? { train: TRAIN } : {}), sort: order, ...extra }).toString();

            const refreshTimes = () => feed.querySelectorAll('[data-ts]').forEach(el => { const a = el.querySelector('[data-ago]'); if (a) a.textContent = timeAgo(+el.dataset.ts); });
            refreshTimes(); setInterval(refreshTimes, 30000);

            // تفويض: إعجاب + تصويت
            feed.addEventListener('click', async (e) => {
                const react = e.target.closest('[data-react]');
                if (react) {
                    if (!IS_AUTH) { location.href = LOGIN; return; }
                    const wrap = react.closest('[data-react-url]');
                    try {
                        const r = await fetch(wrap.dataset.reactUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ value: +react.dataset.value }) });
                        if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; }
                        const d = await r.json(); if (!d.ok) return;
                        wrap.querySelector('[data-likes]').textContent = d.likes;
                        wrap.querySelector('[data-dislikes]').textContent = d.dislikes;
                        const up = wrap.querySelector('[data-value="1"]'), down = wrap.querySelector('[data-value="-1"]');
                        up.classList.toggle('text-rail-600', d.my === 1); up.classList.toggle('bg-rail-50', d.my === 1); up.classList.toggle('text-slate-400', d.my !== 1);
                        down.classList.toggle('text-rose-600', d.my === -1); down.classList.toggle('bg-rose-50', d.my === -1); down.classList.toggle('text-slate-400', d.my !== -1);
                        try { navigator.vibrate?.(8); } catch (e) {}
                    } catch (e) {}
                    return;
                }
                const opt = e.target.closest('[data-choice]');
                if (opt) {
                    if (!IS_AUTH) { location.href = LOGIN; return; }
                    const mount = opt.closest('.poll-mount');
                    try { const r = await fetch(opt.dataset.voteUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ choice: +opt.dataset.choice }) }); if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; } const d = await r.json(); if (d.ok && mount) mount.innerHTML = pollHtml(d.poll, mount.dataset.postId); try { navigator.vibrate?.(8); } catch (e) {} } catch (e) {}
                }
            });

            // ===== التعليقات inline (زي فيسبوك) =====
            function commentHtml(c, isReply) {
                const name = c.user_url ? `<a href="${c.user_url}" class="font-bold text-slate-800 text-xs hover:text-rail-600">${esc(c.user)}</a>` : `<span class="font-bold text-slate-800 text-xs">${esc(c.user)}</span>`;
                const replies = (!isReply && c.replies) ? c.replies.map(r => commentHtml(r, true)).join('') : '';
                return `<div class="flex gap-2 ${isReply ? 'ms-9' : ''}" data-comment-id="${c.id}">
                    <span class="w-8 h-8 shrink-0 grid place-items-center rounded-full bg-slate-100 text-slate-600 font-bold text-xs">${esc(c.initial)}</span>
                    <div class="flex-1 min-w-0">
                        <div class="inline-block max-w-full bg-slate-100 rounded-2xl px-3 py-2">${name}<p class="text-sm text-slate-700 whitespace-pre-line break-words">${esc(c.body)}</p></div>
                        <div class="flex items-center gap-3 mt-1 ps-2 text-[11px] font-bold text-slate-400">
                            <span>${esc(c.ago)}</span>
                            ${!isReply ? '<button type="button" data-reply>رد</button>' : ''}
                            <button type="button" data-report-comment>إبلاغ</button>
                        </div>
                        ${!isReply ? `<div data-replies class="mt-2 space-y-2">${replies}</div><div data-reply-box hidden class="flex items-center gap-2 mt-2"><input data-reply-input type="text" maxlength="600" class="flex-1 rounded-full bg-slate-100 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-rail-300" placeholder="اكتب رد…"><button type="button" data-reply-send class="text-rail-600 font-bold text-xs shrink-0">رد</button></div>` : ''}
                    </div>
                </div>`;
            }
            const sectionInner = () => (IS_AUTH
                ? `<div class="flex items-center gap-2 mb-3"><span class="w-8 h-8 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-bold text-xs">${esc(MY_INITIAL)}</span><input data-comment-input type="text" maxlength="600" class="flex-1 rounded-full bg-slate-100 px-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-rail-300" placeholder="اكتب تعليق…"><button type="button" data-comment-send class="text-rail-600 font-bold text-sm shrink-0">إرسال</button></div>`
                : `<a href="${LOGIN}" class="block text-center text-sm font-bold text-rail-700 mb-3">سجّل دخول عشان تعلّق</a>`)
                + '<div data-comments-list class="space-y-3"><div class="text-center py-2 text-xs text-slate-400">جاري التحميل…</div></div>';
            const inc = (article, n) => { const el = article.querySelector('[data-comments]'); if (el) el.textContent = (+el.textContent || 0) + n; };
            function toast(msg) { const t = document.createElement('div'); t.className = 'fixed bottom-24 inset-x-0 mx-auto w-fit max-w-[80%] bg-slate-900 text-white text-sm rounded-full px-4 py-2 z-[70] shadow-lg'; t.textContent = msg; document.body.appendChild(t); setTimeout(() => t.remove(), 2200); }
            async function report(payload) { try { const r = await fetch(REPORT_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) }); if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; } toast('وصلنا بلاغك، شكرًا.'); } catch (e) {} }
            function askReport(onOk) {
                const ov = document.createElement('div');
                ov.className = 'fixed inset-0 z-[80] flex items-end justify-center';
                ov.innerHTML = '<div class="absolute inset-0 bg-black/40" data-x></div><div class="relative w-full max-w-xl bg-white rounded-t-3xl p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl"><div class="w-10 h-1 rounded-full bg-slate-200 mx-auto mb-3"></div><p class="text-center font-extrabold text-slate-800">إبلاغ عن المحتوى</p><p class="text-center text-xs text-slate-400 mt-1 mb-4">هيتراجع من فريق الموقع.</p><button type="button" data-ok class="w-full bg-rose-50 text-rose-600 font-bold rounded-2xl py-3 mb-2 active:scale-[.99] transition">إبلاغ</button><button type="button" data-x class="w-full bg-slate-100 text-slate-600 font-bold rounded-2xl py-3 active:scale-[.99] transition">إلغاء</button></div>';
                document.body.appendChild(ov);
                const close = () => ov.remove();
                ov.querySelectorAll('[data-x]').forEach(b => b.addEventListener('click', close));
                ov.querySelector('[data-ok]').addEventListener('click', () => { close(); onOk(); });
            }

            feed.addEventListener('click', async (e) => {
                const tgl = e.target.closest('[data-toggle-comments]');
                if (tgl) {
                    const article = tgl.closest('article'), sec = article.querySelector('[data-comments-section]');
                    if (!sec.hasAttribute('hidden')) { sec.hidden = true; return; }
                    sec.hidden = false;
                    if (!sec.dataset.loaded) {
                        sec.innerHTML = sectionInner();
                        try { const r = await fetch(article.dataset.commentsUrl, { headers: { Accept: 'application/json' } }); const d = await r.json(); const list = sec.querySelector('[data-comments-list]'); list.innerHTML = (d.comments || []).map(c => commentHtml(c, false)).join('') || '<p class="text-xs text-slate-400 text-center py-2">كن أول من يعلّق.</p>'; sec.dataset.loaded = '1'; } catch (err) {}
                    }
                    return;
                }
                const send = e.target.closest('[data-comment-send]');
                if (send) {
                    if (!IS_AUTH) { location.href = LOGIN; return; }
                    const article = send.closest('article'), sec = article.querySelector('[data-comments-section]'), input = sec.querySelector('[data-comment-input]'), body = input.value.trim();
                    if (!body) return; input.disabled = true;
                    try { const r = await fetch(article.dataset.commentUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ body }) }); if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; } const d = await r.json(); if (d.ok) { const list = sec.querySelector('[data-comments-list]'); list.querySelector('p')?.remove(); list.insertAdjacentHTML('afterbegin', commentHtml(d.comment, false)); input.value = ''; inc(article, 1); } } catch (err) {} finally { input.disabled = false; input.focus(); }
                    return;
                }
                const rep = e.target.closest('[data-reply]');
                if (rep) { if (!IS_AUTH) { location.href = LOGIN; return; } const cmt = rep.closest('[data-comment-id]'), box = cmt.querySelector('[data-reply-box]'); box.hidden = !box.hidden; if (!box.hidden) box.querySelector('[data-reply-input]').focus(); return; }
                const rsend = e.target.closest('[data-reply-send]');
                if (rsend) {
                    const article = rsend.closest('article'), cmt = rsend.closest('[data-comment-id]'), input = cmt.querySelector('[data-reply-input]'), body = input.value.trim();
                    if (!body) return; input.disabled = true;
                    try { const r = await fetch(article.dataset.commentUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ body, parent_id: +cmt.dataset.commentId }) }); if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; } const d = await r.json(); if (d.ok) { cmt.querySelector('[data-replies]').insertAdjacentHTML('beforeend', commentHtml(d.comment, true)); input.value = ''; cmt.querySelector('[data-reply-box]').hidden = true; inc(article, 1); } } catch (err) {} finally { input.disabled = false; }
                    return;
                }
                const rp = e.target.closest('[data-report-post]');
                if (rp) { if (!IS_AUTH) { location.href = LOGIN; return; } const id = +rp.closest('article').dataset.id; askReport(() => report({ complaint_id: id })); return; }
                const rc = e.target.closest('[data-report-comment]');
                if (rc) { if (!IS_AUTH) { location.href = LOGIN; return; } const id = +rc.closest('[data-comment-id]').dataset.commentId; askReport(() => report({ comment_id: id })); return; }
            });

            // بوستات جديدة (polling)
            const pill = document.getElementById('new-pill'), pillCount = document.getElementById('new-count');
            let buffer = [];
            async function poll() {
                if (order !== 'new' || document.hidden) return;
                try { const r = await fetch(`${FEED_URL}?${qs({ after: maxId() })}`, { headers: { Accept: 'application/json' } }); const d = await r.json(); if (d.posts && d.posts.length) { const known = new Set([...buffer.map(p => p.id), ...ids()]); d.posts.forEach(p => { if (!known.has(p.id)) buffer.push(p); }); if (buffer.length) { pillCount.textContent = buffer.length; pill.hidden = false; } } } catch (e) {}
            }
            setInterval(poll, 12000);
            pill.addEventListener('click', () => { buffer.sort((a, b) => a.id - b.id).forEach(p => feed.insertAdjacentHTML('afterbegin', card(p))); buffer = []; pill.hidden = true; window.scrollTo({ top: 0, behavior: 'smooth' }); });

            // تمرير لا نهائي
            const loading = document.getElementById('feed-loading'); let done = false, busy = false;
            async function loadMore() {
                if (busy || done) return; busy = true; loading.hidden = false;
                try { const r = await fetch(`${FEED_URL}?${qs({ before: minId() })}`, { headers: { Accept: 'application/json' } }); const d = await r.json(); if (!d.posts || !d.posts.length) done = true; else { d.posts.forEach(p => feed.insertAdjacentHTML('beforeend', card(p))); if (d.posts.length < 20) done = true; } } catch (e) {} finally { busy = false; loading.hidden = true; }
            }
            if ('IntersectionObserver' in window) new IntersectionObserver(es => { if (es.some(x => x.isIntersecting)) loadMore(); }, { rootMargin: '400px' }).observe(document.getElementById('feed-sentinel'));

            // ترتيب
            document.querySelectorAll('.order-btn').forEach(b => b.addEventListener('click', async () => {
                if (order === b.dataset.order) return; order = b.dataset.order;
                document.querySelectorAll('.order-btn').forEach(x => { const on = x === b; x.classList.toggle('bg-rail-600', on); x.classList.toggle('text-white', on); x.classList.toggle('bg-white', !on); x.classList.toggle('ring-1', !on); x.classList.toggle('ring-slate-200', !on); x.classList.toggle('text-slate-600', !on); });
                buffer = []; pill.hidden = true; done = false;
                feed.innerHTML = '<div class="flex justify-center py-6"><span class="w-6 h-6 border-2 border-slate-200 border-t-rail-500 rounded-full animate-spin"></span></div>';
                try { const r = await fetch(`${FEED_URL}?${qs({})}`, { headers: { Accept: 'application/json' } }); const d = await r.json(); feed.innerHTML = (d.posts || []).map(card).join('') || '<p class="text-sm text-slate-400 text-center py-6">لسه مفيش بوستات.</p>'; } catch (e) { feed.innerHTML = '<p class="text-sm text-red-500 text-center py-6">تعذّر التحميل.</p>'; }
            }));

            // ===== نافذة الإنشاء =====
            const composer = document.getElementById('composer');
            const openC = () => { if (!composer) { location.href = LOGIN; return; } composer.hidden = false; document.body.style.overflow = 'hidden'; setTimeout(() => document.getElementById('post-body')?.focus(), 100); };
            const closeC = () => { if (composer) { composer.hidden = true; document.body.style.overflow = ''; } };
            document.querySelectorAll('[data-open-composer]').forEach(b => b.addEventListener('click', openC));
            composer?.querySelectorAll('[data-composer-close]').forEach(b => b.addEventListener('click', closeC));

            if (composer) {
                const chips = composer.querySelectorAll('.type-chip');
                const catInput = document.getElementById('post-category');
                const ticket = document.getElementById('ticket-fields');
                const pollF = document.getElementById('poll-fields');
                const body = document.getElementById('post-body');
                const PH = { ticket: 'اكتب تفاصيل التذكرة (الدرجة، ملاحظات…)', poll: 'اكتب سؤال الاستطلاع…', question: 'اسأل المجتمع…', complaint: 'إيه الشكوى؟', experience: 'احكِ تجربتك مع القطر…', news: 'شارك خبر…', warning: 'حذّر الركّاب من إيه؟', general: 'شارك حاجة مع الركّاب…' };
                chips.forEach(c => c.addEventListener('click', () => {
                    const t = c.dataset.type; catInput.value = t;
                    chips.forEach(x => { const on = x === c; x.classList.toggle('bg-rail-600', on); x.classList.toggle('text-white', on); x.classList.toggle('bg-slate-100', !on); x.classList.toggle('text-slate-600', !on); });
                    ticket.hidden = t !== 'ticket'; pollF.hidden = t !== 'poll';
                    if (body) body.placeholder = PH[t] || PH.general;
                }));

                // إضافة خيار استطلاع
                document.getElementById('poll-add')?.addEventListener('click', () => {
                    const box = document.getElementById('poll-options');
                    if (box.children.length >= 4) return;
                    const inp = document.createElement('input');
                    inp.type = 'text'; inp.name = 'poll_options[]'; inp.maxLength = 80; inp.placeholder = 'الخيار ' + (box.children.length + 1);
                    inp.className = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none';
                    box.appendChild(inp);
                });

                // منتقي محطات التذكرة
                const STATIONS = @json($stationsJs);
                const norm = (s) => s.replace(/[إأآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/[ً-ْٰ]/g, '').trim();
                const pick = document.getElementById('tpicker'), psearch = document.getElementById('tpicker-search'), plist = document.getElementById('tpicker-list'), ptitle = document.getElementById('tpicker-title');
                let target = 'from';
                const prender = (q) => { const nq = norm(q || ''); const items = STATIONS.filter(s => !nq || norm(s.name).includes(nq)); plist.innerHTML = items.slice(0, 100).map(s => `<li><button type="button" data-id="${s.id}" data-name="${esc(s.name)}" class="w-full text-start px-3 py-3 rounded-xl text-sm font-medium hover:bg-rail-50 active:bg-rail-100 transition">${esc(s.name)}</button></li>`).join('') || '<li class="px-3 py-4 text-sm text-slate-400 text-center">مفيش محطة</li>'; };
                const popen = (t) => { target = t; ptitle.textContent = t === 'from' ? 'محطة القيام' : 'محطة النزول'; psearch.value = ''; prender(''); pick.hidden = false; setTimeout(() => psearch.focus(), 100); };
                const pclose = () => { pick.hidden = true; };
                document.querySelectorAll('[data-tpicker]').forEach(b => b.addEventListener('click', () => popen(b.dataset.tpicker)));
                pick.querySelectorAll('[data-tpicker-close]').forEach(b => b.addEventListener('click', pclose));
                psearch.addEventListener('input', () => prender(psearch.value));
                psearch.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); plist.querySelector('[data-id]')?.click(); } });
                plist.addEventListener('click', e => { const b = e.target.closest('[data-id]'); if (!b) return; document.getElementById('tk-' + target).value = b.dataset.id; const disp = document.getElementById('tk-' + target + '-disp'); disp.textContent = b.dataset.name; disp.classList.remove('text-slate-400'); disp.classList.add('text-slate-800'); pclose(); });

                // إرسال البوست
                const form = document.getElementById('post-form');
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = document.getElementById('post-submit'), err = document.getElementById('post-err');
                    err.hidden = true; btn.disabled = true; const t = btn.textContent; btn.textContent = '…';
                    const fail = (m) => { err.textContent = m; err.hidden = false; btn.disabled = false; btn.textContent = t; };
                    let d = null;
                    try {
                        const r = await fetch(STORE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: new FormData(form) });
                        // انتهت الجلسة / مش مسجّل دخول → رجّعه لصفحة الدخول بدل رسالة خطأ غامضة.
                        if (r.status === 401 || r.status === 419 || r.redirected || !(r.headers.get('content-type') || '').includes('json')) { location.href = LOGIN; return; }
                        if (r.status === 429) return fail('بترسل بسرعة، استنى دقيقة وجرّب.');
                        d = await r.json();
                    } catch (_) { return fail('النت ضعيف، جرّب تاني.'); }

                    if (!d || !d.ok || !d.post) return fail((d && d.message) || 'مش قادر ينشر، جرّب تاني.');

                    // اتنشر بنجاح — أي خطأ في العرض بعد كده منفصل عن خطأ الإرسال.
                    try {
                        document.getElementById('feed-empty')?.remove();
                        feed.insertAdjacentHTML('afterbegin', card(d.post));
                        mountPolls();
                    } catch (_) {}
                    form.reset(); catInput.value = 'general'; ticket.hidden = true; pollF.hidden = true;
                    chips.forEach(x => { const on = x.dataset.type === 'general'; x.classList.toggle('bg-rail-600', on); x.classList.toggle('text-white', on); x.classList.toggle('bg-slate-100', !on); x.classList.toggle('text-slate-600', !on); });
                    document.getElementById('tk-from-disp').textContent = 'من محطة'; document.getElementById('tk-to-disp').textContent = 'إلى محطة';
                    closeC(); window.scrollTo({ top: 0, behavior: 'smooth' });
                    try { navigator.vibrate?.(12); } catch (e) {}
                    btn.disabled = false; btn.textContent = t;
                });
            }
        })();
    </script>
@endsection
