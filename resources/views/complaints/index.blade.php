@extends('layouts.app')

@section('hideHeader', '1')
@section('title', $train ? "نقاش قطار {$train->number}" : 'مجتمع الركّاب')
@section('og_desc', 'مجتمع ركّاب قطارات مصر — اسأل، بلّغ، شارك خبر، وساعد غيرك.')

@section('content')
    @if ($train)
        {{-- نقاش قطر (صفحة فرعية) --}}
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
        {{-- واجهة المجتمع (الرئيسية) --}}
        <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
            <div class="flex-1 min-w-0">
                <p class="text-slate-400 text-sm font-medium">مجتمع الركّاب</p>
                <h1 class="text-2xl font-extrabold text-slate-800 leading-tight">قطارات مصر</h1>
            </div>
            @auth
                <a href="{{ route('notifications.index') }}" aria-label="التنبيهات" class="relative w-11 h-11 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600">
                    <x-icon name="bell" class="w-5 h-5"/>
                    <span id="notif-badge" hidden class="absolute -top-1 -end-1 min-w-[18px] h-[18px] px-1 grid place-items-center rounded-full bg-rose-500 text-white text-[10px] font-extrabold"></span>
                </a>
                <a href="{{ route('profile', auth()->user()) }}" aria-label="بروفايلي" class="w-11 h-11 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
            @else
                <a href="{{ route('login') }}" aria-label="تسجيل الدخول" class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-rail-600"><x-icon name="user" class="w-6 h-6"/></a>
            @endauth
        </div>
        @auth
            <script>
                (() => {
                    const badge = document.getElementById('notif-badge');
                    if (!badge) return;
                    const url = @json(route('notifications.unread'));
                    async function tick() {
                        if (document.hidden) return;
                        try {
                            const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            const d = await r.json();
                            if (d.count > 0) { badge.textContent = d.count > 99 ? '99+' : d.count; badge.hidden = false; }
                            else badge.hidden = true;
                        } catch (e) {}
                    }
                    tick(); setInterval(tick, 25000);
                })();
            </script>
        @endauth

        {{-- زر البحث عن رحلة (يودّي لصفحة القطارات) --}}
        <a href="{{ route('trains.hub') }}" class="flex items-center gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm px-4 py-3.5 mb-4 active:scale-[.99] transition">
            <span class="w-9 h-9 shrink-0 grid place-items-center rounded-xl bg-rail-100 text-rail-700"><x-icon name="search" class="w-5 h-5"/></span>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-slate-800 text-sm">دوّر على رحلتك</div>
                <div class="text-[11px] text-slate-400">مواعيد · أسعار · محطات</div>
            </div>
            <x-icon name="arrow-left" class="w-4 h-4 text-slate-300"/>
        </a>

        {{-- اختصار الأعلى تقييمًا --}}
        <a href="{{ route('trains.top') }}" class="flex items-center gap-2.5 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-3 mb-4 active:scale-[.99] transition">
            <span class="w-10 h-10 shrink-0 grid place-items-center rounded-xl bg-violet-100 text-violet-700"><x-icon name="star" class="w-5 h-5"/></span>
            <div class="flex-1 min-w-0"><div class="font-bold text-slate-800 text-sm truncate">أعلى القطارات تقييمًا</div><div class="text-[11px] text-slate-400 truncate">ترتيب القطارات برأي الركّاب</div></div>
            <x-icon name="arrow-left" class="w-4 h-4 text-slate-300"/>
        </a>
    @endif

    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ session('ok') }}</div>
    @endif

    {{-- نموذج النشر --}}
    @auth
        <form id="post-form" action="{{ route('complaints.store') }}" method="POST" class="bg-white rounded-3xl shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 p-4 mb-5">
            @csrf
            <div class="flex items-center gap-2.5 mb-3">
                <span class="w-9 h-9 shrink-0 grid place-items-center rounded-full bg-rail-100 text-rail-700 font-extrabold">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="font-bold text-slate-700 text-sm">اكتب حاجة للركّاب</span>
            </div>
            <textarea name="body" id="post-body" rows="3" maxlength="1000" required placeholder="اسأل، بلّغ عن حالة قطر, شارك خبر أو تنبيه…"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">{{ old('body') }}</textarea>
            @error('body')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            {{-- نوع البوست --}}
            <input type="hidden" name="category" id="post-category" value="general">
            <div id="type-chips" class="flex gap-1.5 overflow-x-auto no-scrollbar mt-3">
                @foreach (\App\Models\Complaint::CATEGORIES as $key => $label)
                    <button type="button" data-type="{{ $key }}" class="type-chip shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition {{ $key === 'general' ? 'bg-rail-600 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $label }}</button>
                @endforeach
            </div>

            {{-- حقول سوق التذاكر (تظهر عند اختيار «سوق تذاكر») --}}
            <div id="ticket-fields" hidden class="mt-3 grid grid-cols-2 gap-2">
                <input type="hidden" name="from_station_id" id="tk-from">
                <input type="hidden" name="to_station_id" id="tk-to">
                <button type="button" data-tpicker="from" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-start">
                    <span id="tk-from-disp" class="text-slate-400 truncate">من محطة</span>
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <button type="button" data-tpicker="to" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-start">
                    <span id="tk-to-disp" class="text-slate-400 truncate">إلى محطة</span>
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <input type="date" name="travel_date" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                <input type="number" name="price_egp" min="0" placeholder="السعر (ج.م)" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                <input type="text" name="contact" inputmode="tel" placeholder="رقم للتواصل (واتساب)" class="col-span-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
            </div>

            <div class="flex items-center gap-2 mt-3 flex-wrap">
                <input type="text" name="train_number" inputmode="numeric" value="{{ $train->number ?? '' }}" placeholder="رقم القطر (اختياري)"
                    class="flex-1 min-w-32 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold px-3 py-2 focus:outline-none focus:bg-white focus:ring-1 focus:ring-rail-300">
                <button type="submit" id="post-submit" class="ms-auto bg-rail-600 hover:bg-rail-700 active:scale-95 text-white font-bold text-sm rounded-xl px-5 py-2 transition">انشر</button>
            </div>
            <p id="post-err" hidden class="text-xs text-red-600 mt-2"></p>
        </form>
        <script>
            (() => {
                const chips = document.querySelectorAll('#type-chips .type-chip');
                const catInput = document.getElementById('post-category');
                const ticket = document.getElementById('ticket-fields');
                const body = document.getElementById('post-body');
                const PH = { ticket: 'اكتب تفاصيل التذكرة (الدرجة، ملاحظات…)', question: 'اسأل المجتمع…', complaint: 'إيه الشكوى؟', experience: 'احكِ تجربتك مع القطر…', news: 'شارك خبر…', warning: 'حذّر الركّاب من إيه؟', general: 'شارك حاجة مع الركّاب…' };
                if (!chips.length) return;
                chips.forEach(c => c.addEventListener('click', () => {
                    const t = c.dataset.type;
                    catInput.value = t;
                    chips.forEach(x => {
                        const on = x === c;
                        x.classList.toggle('bg-rail-600', on); x.classList.toggle('text-white', on);
                        x.classList.toggle('bg-slate-100', !on); x.classList.toggle('text-slate-600', !on);
                    });
                    ticket.hidden = t !== 'ticket';
                    if (body) body.placeholder = PH[t] || PH.general;
                }));
            })();
        </script>

        {{-- منتقي محطات التذكرة (بحث بالكتابة) --}}
        <div id="tpicker" hidden class="fixed inset-0 z-50 bg-white">
            <div class="mx-auto max-w-xl h-full flex flex-col">
                <div class="shrink-0 px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 mb-3">
                        <button type="button" data-tpicker-close aria-label="رجوع" class="w-9 h-9 shrink-0 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100 active:scale-90 transition">
                            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                        </button>
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
        <script>
            (() => {
                const STATIONS = @json($stations->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar])->values());
                const norm = (s) => s.replace(/[إأآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/[ً-ْٰ]/g, '').trim();
                const pick = document.getElementById('tpicker');
                const search = document.getElementById('tpicker-search');
                const list = document.getElementById('tpicker-list');
                const title = document.getElementById('tpicker-title');
                let target = 'from';
                function render(q) {
                    const nq = norm(q || '');
                    const items = STATIONS.filter(s => !nq || norm(s.name).includes(nq));
                    list.innerHTML = items.slice(0, 100).map(s => `<li><button type="button" data-id="${s.id}" data-name="${s.name}" class="w-full text-start px-3 py-3 rounded-xl text-sm font-medium hover:bg-rail-50 active:bg-rail-100 transition">${s.name}</button></li>`).join('') || '<li class="px-3 py-4 text-sm text-slate-400 text-center">مفيش محطة بالاسم ده</li>';
                }
                function open(t) { target = t; title.textContent = t === 'from' ? 'محطة القيام' : 'محطة النزول'; search.value = ''; render(''); pick.hidden = false; document.body.style.overflow = 'hidden'; setTimeout(() => search.focus(), 100); }
                function close() { pick.hidden = true; document.body.style.overflow = ''; }
                document.querySelectorAll('[data-tpicker]').forEach(b => b.addEventListener('click', () => open(b.dataset.tpicker)));
                pick.querySelectorAll('[data-tpicker-close]').forEach(b => b.addEventListener('click', close));
                search.addEventListener('input', () => render(search.value));
                search.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); list.querySelector('[data-id]')?.click(); } });
                list.addEventListener('click', (e) => {
                    const b = e.target.closest('[data-id]'); if (!b) return;
                    document.getElementById('tk-' + target).value = b.dataset.id;
                    const disp = document.getElementById('tk-' + target + '-disp');
                    disp.textContent = b.dataset.name; disp.classList.remove('text-slate-400'); disp.classList.add('text-slate-800');
                    close();
                });
            })();
        </script>
    @else
        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 bg-white rounded-3xl ring-1 ring-slate-100 shadow-sm text-rail-700 font-bold text-sm px-4 py-4 mb-5 hover:ring-rail-200 transition">
            سجّل دخول وشارك مع المجتمع
        </a>
    @endauth

    {{-- فلتر النوع --}}
    @php $cat = request('category'); @endphp
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 mb-3">
        <a href="{{ request()->fullUrlWithQuery(['category' => null]) }}" class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ ! $cat ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">الكل</a>
        @foreach (\App\Models\Complaint::CATEGORIES as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['category' => $key]) }}" class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $cat === $key ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- ترتيب --}}
    <div class="flex items-center gap-2 mb-3">
        <button type="button" data-order="new" class="order-btn px-4 py-1.5 rounded-full text-xs font-bold bg-rail-600 text-white transition">الأحدث</button>
        <button type="button" data-order="top" class="order-btn px-4 py-1.5 rounded-full text-xs font-bold bg-white ring-1 ring-slate-200 text-slate-600 transition">الأكثر تفاعلًا</button>
        <span class="ms-auto inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600"><span class="relative flex w-2 h-2"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex rounded-full w-2 h-2 bg-emerald-500"></span></span> مباشر</span>
    </div>

    {{-- زر بوستات جديدة --}}
    <button type="button" id="new-pill" hidden class="mx-auto mb-3 flex items-center gap-1.5 bg-rail-600 text-white text-xs font-bold rounded-full px-4 py-2 shadow-lg shadow-rail-600/30 active:scale-95 transition">
        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
        <span id="new-count">0</span> بوست جديد
    </button>

    {{-- البوستات --}}
    @php
        $catColors = ['question' => 'bg-sky-100 text-sky-700', 'news' => 'bg-emerald-100 text-emerald-700', 'warning' => 'bg-amber-100 text-amber-700', 'delay' => 'bg-orange-100 text-orange-700', 'complaint' => 'bg-rose-100 text-rose-700', 'general' => 'bg-rail-100 text-rail-700'];
    @endphp
    <div id="feed" class="space-y-3">
        @forelse ($complaints as $c)
            @php $liked = in_array($c->id, $likedIds); @endphp
            <article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4" data-id="{{ $c->id }}" data-ts="{{ $c->created_at->timestamp }}">
                <div class="flex items-center gap-2.5">
                    <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-50 text-rail-700 font-bold">{{ mb_substr($c->user->name ?? 'راكب', 0, 1) }}</span>
                    <div class="flex-1 min-w-0">
                        @if ($c->user)
                            <a href="{{ route('profile', $c->user) }}" class="font-bold text-slate-800 text-sm truncate block hover:text-rail-600 transition">{{ $c->user->name }}</a>
                        @else
                            <div class="font-bold text-slate-800 text-sm truncate">راكب</div>
                        @endif
                        <div class="text-[11px] text-slate-400" data-ago>{{ $c->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $catColors[$c->category] ?? $catColors['general'] }}">{{ \App\Models\Complaint::CATEGORIES[$c->category] ?? 'عام' }}</span>
                </div>
                <a href="{{ route('complaints.show', $c) }}" class="block"><p class="text-sm text-slate-700 leading-relaxed mt-3 whitespace-pre-line line-clamp-4">{{ $c->body }}</p></a>
                @if ($c->category === 'ticket')
                    <div class="mt-2 rounded-2xl bg-emerald-50 ring-1 ring-emerald-100 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-bold text-slate-800 text-sm flex items-center gap-1.5 min-w-0">
                                <span class="truncate max-w-[38%]">{{ $c->fromStation->name_ar ?? '—' }}</span>
                                <x-icon name="arrow-left" class="w-3.5 h-3.5 text-emerald-600 shrink-0"/>
                                <span class="truncate max-w-[38%]">{{ $c->toStation->name_ar ?? '—' }}</span>
                            </div>
                            @if ($c->price_egp)<span class="font-extrabold text-emerald-700 text-sm whitespace-nowrap">{{ number_format($c->price_egp) }} ج.م</span>@endif
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-2">
                            <span class="text-xs text-slate-500">{{ $c->travel_date?->translatedFormat('l j F') }}</span>
                            @if ($c->contact)
                                <a href="https://wa.me/2{{ preg_replace('/\D/', '', $c->contact) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-full px-3 py-1.5">تواصل</a>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-50">
                    @if ($c->train)
                        <a href="{{ route('complaints.index', ['train' => $c->train->number]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-rail-700 bg-rail-50 rounded-full px-2.5 py-1"><x-icon name="train" class="w-3.5 h-3.5"/> قطار {{ $c->train->number }}</a>
                    @endif
                    <a href="{{ route('complaints.show', $c) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-400 hover:text-rail-600 transition">
                        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8A8.5 8.5 0 0 1 21 11.5z"/></svg>
                        <span data-comments>{{ $c->comments_count }}</span>
                    </a>
                    <button type="button" data-like data-url="{{ route('complaints.like', $c) }}" class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold {{ $liked ? 'text-rose-600' : 'text-slate-400' }} active:scale-90 transition">
                        <x-icon name="heart" class="w-5 h-5"/><span data-like-count>{{ $c->likers_count }}</span>
                    </button>
                </div>
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

    <script>
        (() => {
            const IS_AUTH = @json(auth()->check());
            const LOGIN = @json(route('login'));
            const FEED_URL = @json(route('complaints.feed'));
            const STORE_URL = @json(route('complaints.store'));
            const CATEGORY = @json(request('category'));
            const TRAIN = @json($train->number ?? null);
            const csrf = () => document.querySelector('meta[name=csrf-token]')?.content;
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
            const CAT = { question: 'bg-sky-100 text-sky-700', news: 'bg-emerald-100 text-emerald-700', warning: 'bg-amber-100 text-amber-700', delay: 'bg-orange-100 text-orange-700', complaint: 'bg-rose-100 text-rose-700', general: 'bg-rail-100 text-rail-700' };

            const feed = document.getElementById('feed');
            const heartSvg = '<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
            const chatSvg = '<svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8A8.5 8.5 0 0 1 21 11.5z"/></svg>';

            const timeAgo = (ts) => {
                const s = Math.max(0, Math.floor(Date.now() / 1000 - ts));
                if (s < 60) return 'دلوقتي';
                const m = Math.floor(s / 60); if (m < 60) return `منذ ${m} د`;
                const h = Math.floor(m / 60); if (h < 24) return `منذ ${h} س`;
                const d = Math.floor(h / 24); if (d < 30) return `منذ ${d} يوم`;
                try { return new Date(ts * 1000).toLocaleDateString('ar-EG'); } catch (e) { return ''; }
            };

            const arrowSvg = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7-7-7 7 7 7"/></svg>';
            function ticketBox(p) {
                if (p.category !== 'ticket') return '';
                const price = p.price ? `<span class="font-extrabold text-emerald-700 text-sm whitespace-nowrap">${Number(p.price).toLocaleString('ar-EG')} ج.م</span>` : '';
                const wa = p.wa ? `<a href="https://wa.me/2${p.wa}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 bg-emerald-600 text-white text-xs font-bold rounded-full px-3 py-1.5">تواصل</a>` : '';
                return `<div class="mt-2 rounded-2xl bg-emerald-50 ring-1 ring-emerald-100 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-bold text-slate-800 text-sm flex items-center gap-1.5 min-w-0"><span class="truncate max-w-[38%]">${esc(p.from || '—')}</span>${arrowSvg}<span class="truncate max-w-[38%]">${esc(p.to || '—')}</span></div>
                        ${price}
                    </div>
                    <div class="flex items-center justify-between gap-2 mt-2"><span class="text-xs text-slate-500">${esc(p.travel_date || '')}</span>${wa}</div>
                </div>`;
            }
            function card(p) {
                const name = p.user_url ? `<a href="${p.user_url}" class="font-bold text-slate-800 text-sm truncate block hover:text-rail-600 transition">${esc(p.user)}</a>` : `<div class="font-bold text-slate-800 text-sm truncate">${esc(p.user)}</div>`;
                const train = p.train_number ? `<a href="${p.train_url}" class="inline-flex items-center gap-1 text-xs font-bold text-rail-700 bg-rail-50 rounded-full px-2.5 py-1">قطار ${esc(p.train_number)}</a>` : '';
                return `<article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4" data-id="${p.id}" data-ts="${p.ts}">
                    <div class="flex items-center gap-2.5">
                        <span class="w-10 h-10 shrink-0 grid place-items-center rounded-full bg-rail-50 text-rail-700 font-bold">${esc(p.initial)}</span>
                        <div class="flex-1 min-w-0">${name}<div class="text-[11px] text-slate-400" data-ago>${timeAgo(p.ts)}</div></div>
                        <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 ${CAT[p.category] || CAT.general}">${esc(p.category_label)}</span>
                    </div>
                    <a href="${p.url}" class="block"><p class="text-sm text-slate-700 leading-relaxed mt-3 whitespace-pre-line line-clamp-4">${esc(p.body)}</p></a>
                    ${ticketBox(p)}
                    <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-50">
                        ${train}
                        <a href="${p.url}" class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-400">${chatSvg}<span data-comments>${p.comments}</span></a>
                        <button type="button" data-like data-url="${p.like_url}" class="ms-auto inline-flex items-center gap-1.5 text-sm font-bold ${p.liked ? 'text-rose-600' : 'text-slate-400'} active:scale-90 transition">${heartSvg}<span data-like-count>${p.likes}</span></button>
                    </div>
                </article>`;
            }

            const ids = () => [...feed.querySelectorAll('[data-id]')].map(a => +a.dataset.id);
            const maxId = () => ids().length ? Math.max(...ids()) : 0;
            const minId = () => ids().length ? Math.min(...ids()) : 0;
            const qs = (extra) => new URLSearchParams({ ...(CATEGORY ? { category: CATEGORY } : {}), ...(TRAIN ? { train: TRAIN } : {}), sort: order, ...extra }).toString();
            let order = 'new';

            // تحديث الأوقات الحيّة
            const refreshTimes = () => feed.querySelectorAll('[data-ts]').forEach(el => { const a = el.querySelector('[data-ago]'); if (a) a.textContent = timeAgo(+el.dataset.ts); });
            refreshTimes(); setInterval(refreshTimes, 30000);

            // الإعجاب (تفويض)
            feed.addEventListener('click', async (e) => {
                const btn = e.target.closest('[data-like]'); if (!btn) return;
                if (!IS_AUTH) { location.href = LOGIN; return; }
                const countEl = btn.querySelector('[data-like-count]');
                try {
                    const r = await fetch(btn.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } });
                    if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; }
                    const d = await r.json(); if (!d.ok) return;
                    countEl.textContent = d.count;
                    btn.classList.toggle('text-rose-600', d.liked);
                    btn.classList.toggle('text-slate-400', !d.liked);
                    try { navigator.vibrate?.(8); } catch (e) {}
                } catch (e) {}
            });

            // نشر بوست فوري
            const form = document.getElementById('post-form');
            if (form) form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('post-submit'), err = document.getElementById('post-err');
                err.hidden = true; btn.disabled = true; const t = btn.textContent; btn.textContent = '…';
                try {
                    const r = await fetch(STORE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }, body: new FormData(form) });
                    if (r.status === 419 || r.status === 401) { location.href = LOGIN; return; }
                    const d = await r.json();
                    if (d.ok && d.post) {
                        document.getElementById('feed-empty')?.remove();
                        feed.insertAdjacentHTML('afterbegin', card(d.post));
                        form.querySelector('#post-body').value = '';
                        try { navigator.vibrate?.(12); } catch (e) {}
                    } else { err.textContent = 'مش قادر ينشر، جرّب تاني.'; err.hidden = false; }
                } catch (e) { err.textContent = 'حصل خطأ، جرّب تاني.'; err.hidden = false; }
                finally { btn.disabled = false; btn.textContent = t; }
            });

            // بوستات جديدة (polling)
            const pill = document.getElementById('new-pill'), pillCount = document.getElementById('new-count');
            let buffer = [];
            async function poll() {
                if (order !== 'new' || document.hidden) return;
                try {
                    const r = await fetch(`${FEED_URL}?${qs({ after: maxId() })}`, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (d.posts && d.posts.length) {
                        const known = new Set([...buffer.map(p => p.id), ...ids()]);
                        d.posts.forEach(p => { if (!known.has(p.id)) buffer.push(p); });
                        if (buffer.length) { pillCount.textContent = buffer.length; pill.hidden = false; }
                    }
                } catch (e) {}
            }
            setInterval(poll, 12000);
            pill.addEventListener('click', () => {
                buffer.sort((a, b) => a.id - b.id).forEach(p => feed.insertAdjacentHTML('afterbegin', card(p)));
                buffer = []; pill.hidden = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // تمرير لا نهائي
            const loading = document.getElementById('feed-loading'); let done = false, busy = false;
            async function loadMore() {
                if (busy || done) return; busy = true; loading.hidden = false;
                try {
                    const r = await fetch(`${FEED_URL}?${qs({ before: minId() })}`, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (!d.posts || !d.posts.length) { done = true; }
                    else { d.posts.forEach(p => feed.insertAdjacentHTML('beforeend', card(p))); if (d.posts.length < 20) done = true; }
                } catch (e) {} finally { busy = false; loading.hidden = true; }
            }
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(es => { if (es.some(x => x.isIntersecting)) loadMore(); }, { rootMargin: '400px' }).observe(document.getElementById('feed-sentinel'));
            }

            // تغيير الترتيب → إعادة تحميل الفيد
            document.querySelectorAll('.order-btn').forEach(b => b.addEventListener('click', async () => {
                if (order === b.dataset.order) return;
                order = b.dataset.order;
                document.querySelectorAll('.order-btn').forEach(x => {
                    const on = x === b;
                    x.classList.toggle('bg-rail-600', on); x.classList.toggle('text-white', on);
                    x.classList.toggle('bg-white', !on); x.classList.toggle('ring-1', !on); x.classList.toggle('ring-slate-200', !on); x.classList.toggle('text-slate-600', !on);
                });
                buffer = []; pill.hidden = true; done = false;
                feed.innerHTML = '<div class="flex justify-center py-6"><span class="w-6 h-6 border-2 border-slate-200 border-t-rail-500 rounded-full animate-spin"></span></div>';
                try {
                    const r = await fetch(`${FEED_URL}?${qs({})}`, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    feed.innerHTML = (d.posts || []).map(card).join('') || '<p class="text-sm text-slate-400 text-center py-6">لسه مفيش بوستات.</p>';
                } catch (e) { feed.innerHTML = '<p class="text-sm text-red-500 text-center py-6">تعذّر التحميل.</p>'; }
            }));
        })();
    </script>
@endsection
