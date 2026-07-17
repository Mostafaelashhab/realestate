@extends('layouts.app')

@section('hideHeader', '1')
@section('title', 'سوق التذاكر')
@section('og_desc', 'بيع أو بدّل تذكرتك مع باقي الركّاب — سوق تذاكر قطارات مصر.')

@section('content')
    {{-- شريط علوي --}}
    <div class="flex items-center gap-3 pt-[max(0.5rem,env(safe-area-inset-top))] mb-4">
        <a href="{{ route('home') }}" aria-label="رجوع" class="w-10 h-10 shrink-0 grid place-items-center rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm text-slate-600 active:scale-90 transition">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="font-extrabold text-slate-800 leading-tight">سوق التذاكر</h1>
            <p class="text-[11px] text-slate-400">بيع أو بدّل تذكرتك مع الركّاب</p>
        </div>
    </div>

    @if (session('ok'))
        <div class="bg-emerald-50 text-emerald-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ session('ok') }}</div>
    @endif

    {{-- تنبيه أمان --}}
    <div class="flex items-start gap-2 bg-amber-50 text-amber-800 text-xs rounded-2xl px-4 py-3 mb-4 leading-relaxed">
        <x-icon name="alert" class="w-4 h-4 shrink-0 mt-0.5"/>
        الموقع بيوصّل الركّاب ببعض بس. اتأكد من التذكرة قبل أي دفع — إحنا مش مسؤولين عن المعاملات بين الأفراد.
    </div>

    {{-- نموذج إضافة إعلان --}}
    @auth
        <details class="bg-white rounded-3xl shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 mb-5 group">
            <summary class="flex items-center gap-2 px-4 py-3.5 cursor-pointer font-bold text-slate-800">
                <span class="w-8 h-8 grid place-items-center rounded-xl bg-rail-100 text-rail-700"><x-icon name="ticket" class="w-4 h-4"/></span>
                أضف إعلان تذكرة
                <svg viewBox="0 0 24 24" class="w-4 h-4 ms-auto text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <form action="{{ route('tickets.store') }}" method="POST" class="px-4 pb-4 pt-1 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">من محطة</label>
                        <select name="from_station_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                            <option value="" disabled selected>القيام</option>
                            @foreach ($stations as $st)<option value="{{ $st->id }}">{{ $st->name_ar }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">إلى محطة</label>
                        <select name="to_station_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                            <option value="" disabled selected>النزول</option>
                            @foreach ($stations as $st)<option value="{{ $st->id }}">{{ $st->name_ar }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">التاريخ</label>
                        <input type="date" name="travel_date" required value="{{ now()->toDateString() }}" min="{{ now()->toDateString() }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">رقم القطر (اختياري)</label>
                        <input type="text" name="train_number" inputmode="numeric" placeholder="مثال: 903" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">النوع</label>
                        <select name="kind" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                            <option value="sale">للبيع</option>
                            <option value="swap">للتبديل</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">عدد التذاكر</label>
                        <input type="number" name="seats" value="1" min="1" max="10" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">السعر (ج.م)</label>
                        <input type="number" name="price_egp" min="0" placeholder="اختياري" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">رقم للتواصل (موبايل/واتساب)</label>
                    <input type="text" name="contact" inputmode="tel" required placeholder="01xxxxxxxxx" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1">ملاحظة (اختياري)</label>
                    <input type="text" name="note" maxlength="300" placeholder="الدرجة، رقم العربة، تفاصيل…" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
                </div>
                @if ($errors->any())
                    <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                @endif
                <button type="submit" class="w-full bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl py-3 transition">انشر الإعلان</button>
            </form>
        </details>
    @else
        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 bg-white rounded-3xl ring-1 ring-slate-100 shadow-sm text-rail-700 font-bold text-sm px-4 py-4 mb-5 hover:ring-rail-200 transition">
            سجّل دخول عشان تضيف إعلان
        </a>
    @endauth

    {{-- فلاتر --}}
    @php $kind = request('kind'); @endphp
    <div class="flex items-center gap-2 mb-3">
        <a href="{{ route('tickets.index') }}" class="px-4 py-2 rounded-full font-bold text-xs transition {{ ! $kind ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">الكل</a>
        <a href="{{ route('tickets.index', ['kind' => 'sale']) }}" class="px-4 py-2 rounded-full font-bold text-xs transition {{ $kind === 'sale' ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">للبيع</a>
        <a href="{{ route('tickets.index', ['kind' => 'swap']) }}" class="px-4 py-2 rounded-full font-bold text-xs transition {{ $kind === 'swap' ? 'bg-rail-600 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600' }}">للتبديل</a>
    </div>

    {{-- الإعلانات --}}
    <div class="space-y-3">
        @forelse ($listings as $l)
            @php $wa = preg_replace('/\D/', '', $l->contact); @endphp
            <article class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4">
                <div class="flex items-center justify-between gap-2">
                    <div class="font-extrabold text-slate-800 flex items-center gap-1.5 min-w-0">
                        <span class="truncate max-w-[38%]">{{ $l->fromStation->name_ar ?? '—' }}</span>
                        <x-icon name="arrow-left" class="w-4 h-4 text-rail-600 shrink-0"/>
                        <span class="truncate max-w-[38%]">{{ $l->toStation->name_ar ?? '—' }}</span>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold rounded-full px-2.5 py-1 {{ $l->kind === 'swap' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">{{ \App\Models\TicketListing::KINDS[$l->kind] ?? '' }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 mt-2">
                    <span class="inline-flex items-center gap-1"><x-icon name="calendar" class="w-3.5 h-3.5 text-slate-400"/> {{ $l->travel_date?->translatedFormat('l j F') }}</span>
                    @if ($l->train_number)<span class="inline-flex items-center gap-1"><x-icon name="train" class="w-3.5 h-3.5 text-slate-400"/> قطار {{ $l->train_number }}</span>@endif
                    <span>{{ $l->seats }} تذكرة</span>
                    @if ($l->class_ar)<span>{{ $l->class_ar }}</span>@endif
                </div>

                @if ($l->note)<p class="text-sm text-slate-600 mt-2">{{ $l->note }}</p>@endif

                <div class="flex items-center justify-between gap-2 mt-3 pt-3 border-t border-slate-50">
                    <div>
                        @if ($l->kind === 'sale' && $l->price_egp)
                            <span class="font-extrabold text-rail-700 text-lg">{{ number_format($l->price_egp) }} <span class="text-xs font-medium text-slate-400">ج.م</span></span>
                        @else
                            <span class="font-bold text-slate-500 text-sm">{{ $l->kind === 'swap' ? 'قابل للتبديل' : 'السعر بالتفاوض' }}</span>
                        @endif
                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $l->user->name ?? 'راكب' }} · {{ $l->created_at->diffForHumans() }}</div>
                    </div>
                    @auth
                        @if ($l->user_id === auth()->id())
                            <form action="{{ route('tickets.close', $l) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-bold text-slate-400 hover:text-red-600 border border-slate-200 rounded-full px-3 py-2 transition">إقفال</button>
                            </form>
                        @else
                            <a href="https://wa.me/2{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-95 text-white font-bold text-sm rounded-full px-4 py-2 transition">
                                تواصل
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 text-white font-bold text-sm rounded-full px-4 py-2 transition">تواصل</a>
                    @endauth
                </div>
            </article>
        @empty
            <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center">
                <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100"><x-icon name="ticket" class="w-7 h-7"/></div>
                <p class="font-bold text-slate-700">لسه مفيش إعلانات</p>
                <p class="text-sm text-slate-500 mt-1">كن أول من ينشر تذكرة للبيع أو التبديل.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $listings->links() }}</div>
@endsection
