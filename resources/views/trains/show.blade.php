@extends('layouts.app')

@section('title', "قطار {$train->number}")
@section('og_title', "قطار {$train->number}" . ($origin && $terminal ? " — {$origin->name_ar} ← {$terminal->name_ar}" : ''))
@section('og_desc', trim((\App\Support\Format::time($depart) ? \App\Support\Format::time($depart) . ' ← ' . \App\Support\Format::time($arrive) . ' · ' : '') . ($duration ? $duration . ' · ' : '') . 'مواعيد وأسعار رحلتك على قطارات مصر.'))

@php
    // مولّد صف نجوم (يُستخدم في الهيرو وفي قائمة الآراء).
    $starRow = function ($n, $size = 'w-4 h-4') {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $on = $i <= round($n);
            $out .= '<svg viewBox="0 0 24 24" class="' . $size . ' ' . ($on ? 'text-amber-400' : 'text-white/25') . '" fill="' . ($on ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3.5l2.6 5.3 5.8.9-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.2-4.1 5.8-.9z"/></svg>';
        }
        return $out;
    };
    // لون أفاتار البروفايل حسب نوع القطر (كل فئة لها هوية لونية).
    $typeAvatar = match ($train->type) {
        'vip' => 'from-amber-500 to-amber-700',
        'spanish' => 'from-sky-500 to-blue-700',
        'talgo' => 'from-violet-500 to-purple-700',
        'improved' => 'from-teal-500 to-emerald-700',
        'russian' => 'from-rose-500 to-red-700',
        'ordinary' => 'from-slate-500 to-slate-700',
        default => 'from-rail-600 to-rail-800',
    };

    // نسخة رمادية فاتحة لصفوف الآراء على خلفية بيضاء.
    $starRowLight = function ($n) {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $on = $i <= round($n);
            $out .= '<svg viewBox="0 0 24 24" class="w-4 h-4 ' . ($on ? 'text-amber-400' : 'text-slate-300') . '" fill="' . ($on ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3.5l2.6 5.3 5.8.9-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.2-4.1 5.8-.9z"/></svg>';
        }
        return $out;
    };
@endphp

@section('content')
    {{-- ========================= بروفايل القطر (غلاف + أفاتار متداخل) ========================= --}}
    <section id="top" class="mb-4 scroll-mt-4">
        {{-- الغلاف: صورة القطر --}}
        <div class="relative h-40 sm:h-48 rounded-3xl overflow-hidden bg-rail-800">
            <img src="{{ asset('images/train-hero.png') }}" alt=""
                class="absolute inset-0 w-full h-full object-cover" />
            <div class="absolute inset-0 bg-linear-to-t from-black/70 via-black/25 to-black/10"></div>

            {{-- زر المشاركة أعلى الغلاف --}}
            <button type="button" data-share
                data-share-title="قطار {{ $train->number }}@if ($origin && $terminal) — {{ $origin->name_ar }} ← {{ $terminal->name_ar }}@endif"
                aria-label="مشاركة"
                class="absolute top-3 end-3 w-9 h-9 grid place-items-center rounded-full bg-black/30 backdrop-blur hover:bg-black/50 active:scale-90 text-white transition">
                <x-icon name="share" class="w-5 h-5" />
            </button>

            {{-- المسار كسطر فوق الغلاف --}}
            @if ($origin && $terminal)
                <div class="absolute bottom-3 end-4 text-white text-sm font-bold drop-shadow flex items-center gap-1.5">
                    <span class="truncate max-w-28">{{ $origin->name_ar }}</span>
                    <x-icon name="train" class="w-4 h-4 shrink-0 text-white/90" />
                    <span class="truncate max-w-28">{{ $terminal->name_ar }}</span>
                </div>
            @endif
        </div>

        {{-- شريط الهوية: أفاتار متداخل + زر متابعة --}}
        <div class="px-1">
            <div class="flex items-end gap-3 -mt-11 relative z-10">
                {{-- أفاتار دائري (لونه حسب نوع القطر) --}}
                <div class="relative shrink-0">
                    <div class="w-24 h-24 rounded-full bg-linear-to-br {{ $typeAvatar }} ring-4 ring-white shadow-lg grid place-items-center">
                        <x-icon name="train" class="w-11 h-11 text-white" />
                    </div>
                    <span class="absolute -bottom-1 start-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-bold text-slate-700 bg-white ring-1 ring-slate-100 shadow-sm rounded-full px-2 py-0.5">{{ $train->type_label }}</span>
                </div>

                {{-- زر متابعة (المفضلة) --}}
                <button id="fav-btn" type="button" aria-label="متابعة القطر"
                    class="ms-auto mb-1 inline-flex items-center gap-1.5 rounded-full bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold px-4 py-2 shadow-sm shadow-rail-600/30 transition">
                    <x-icon name="star" class="w-4 h-4" />
                    <span data-fav-label>تابع</span>
                </button>
            </div>

            {{-- الاسم + النوع + التقييم --}}
            <div class="mt-2.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-slate-800 leading-none">قطار {{ $train->number }}</h1>
                    @if ($train->active)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rail-700 bg-rail-50 ring-1 ring-rail-100 px-2 py-0.5 rounded-full">
                            <x-icon name="check" class="w-3 h-3" /> مؤكد
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                    <a href="#reviews" class="inline-flex items-center gap-1 hover:opacity-80 transition">
                        <span class="inline-flex gap-0.5">{!! $starRowLight($reviewsCount ? $reviewsAvg : 0) !!}</span>
                        @if ($reviewsCount > 0)
                            <span class="text-sm font-bold text-slate-700">{{ $reviewsAvg }}</span>
                            <span class="text-xs text-slate-400">({{ $reviewsCount }})</span>
                        @else
                            <span class="text-xs text-slate-400">لسه مفيش تقييم</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- شريط إحصائيات (بنمط بروفايل) --}}
            <div class="mt-4 flex items-stretch rounded-2xl bg-white ring-1 ring-slate-100 shadow-sm divide-x divide-slate-100 text-center overflow-hidden">
                <div class="flex-1 py-3">
                    <div class="text-lg font-extrabold text-slate-800 leading-none">{{ \App\Support\Format::time($depart) ?? '—' }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">القيام</div>
                </div>
                <div class="flex-1 py-3">
                    <div class="text-lg font-extrabold text-slate-800 leading-none">{{ \App\Support\Format::time($arrive) ?? '—' }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">الوصول</div>
                </div>
                <div class="flex-1 py-3">
                    <div id="stat-duration" class="text-lg font-extrabold text-slate-800 leading-none whitespace-nowrap">{{ $duration ?? '—' }}</div>
                    <div class="text-[11px] text-slate-400 mt-1">المدة</div>
                </div>
                <div class="flex-1 py-3">
                    <div id="stat-distance" class="text-lg font-extrabold text-slate-800 leading-none whitespace-nowrap">—</div>
                    <div class="text-[11px] text-slate-400 mt-1">كم</div>
                </div>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const KEY = 'qm:fav';
            const num = @json($train->number);
            const label = @json(trim(($origin?->name_ar ? $origin->name_ar . ' ← ' . $terminal?->name_ar : ($train->type_label ?? ''))));
            const url = @json(request()->getRequestUri());
            const btn = document.getElementById('fav-btn');
            const lbl = btn.querySelector('[data-fav-label]');
            const get = () => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; } };
            const isFav = () => get().some(f => f.number === num);
            const paint = () => {
                const on = isFav();
                if (lbl) lbl.textContent = on ? 'متابَع' : 'تابع';
                // متابَع: أبيض بإطار أخضر · غير متابَع: أخضر ممتلئ
                btn.classList.toggle('bg-rail-600', !on);
                btn.classList.toggle('hover:bg-rail-700', !on);
                btn.classList.toggle('text-white', !on);
                btn.classList.toggle('bg-white', on);
                btn.classList.toggle('text-rail-700', on);
                btn.classList.toggle('ring-1', on);
                btn.classList.toggle('ring-rail-200', on);
            };
            btn.addEventListener('click', () => {
                let list = get();
                list = isFav() ? list.filter(f => f.number !== num) : [{ number: num, label, url }, ...list].slice(0, 12);
                try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { }
                paint();
            });
            paint();
        })();

        {{-- نسجّل القطر المعروض في «رحلاتي» لعرض الأكثر بحثًا/الأقرب ميعادًا بالرئيسية --}}
        @if ($origin && $terminal)
            @php
                $dateISO = request('date') ?: now()->toDateString();
                $depHM = $depart ? \Illuminate\Support\Carbon::parse($depart)->format('H:i') : null;
            @endphp
            (() => {
                try {
                    const key = @json($train->number . '|' . $origin->id . '|' . $terminal->id . '|' . $dateISO);
                    let trips = {};
                    try { trips = JSON.parse(localStorage.getItem('qm:trips') || '{}'); } catch (e) {}
                    const prev = trips[key]?.count || 0;
                    trips[key] = {
                        number: @json((string) $train->number),
                        fromName: @json($origin->name_ar),
                        toName: @json($terminal->name_ar),
                        ftime: @json(\App\Support\Format::time($depart)),
                        ttime: @json(\App\Support\Format::time($arrive)),
                        dur: @json($duration),
                        url: @json(request()->getRequestUri()),
                        dateLabel: @json(\Illuminate\Support\Carbon::parse($dateISO)->translatedFormat('j F Y')),
                        depISO: @json($depHM ? $dateISO . 'T' . $depHM : null),
                        count: prev + 1,
                        seen: Date.now(),
                    };
                    // نحتفظ بأحدث ٢٠ رحلة فقط.
                    const entries = Object.entries(trips).sort((a, b) => (b[1].seen || 0) - (a[1].seen || 0)).slice(0, 20);
                    localStorage.setItem('qm:trips', JSON.stringify(Object.fromEntries(entries)));
                } catch (e) {}
            })();
        @endif
    </script>

    {{-- ========================= فين القطر دلوقتي (تقدير حسب الجدول) ========================= --}}
    @php
        $statusStops = $scheduleStops->map(function ($s) {
            $arr = $s->arrival_time ? \Illuminate\Support\Carbon::parse($s->arrival_time) : null;
            $dep = $s->departure_time ? \Illuminate\Support\Carbon::parse($s->departure_time) : null;
            return [
                'name' => $s->station->name_ar,
                'arr' => $arr ? $arr->hour * 60 + $arr->minute : null,
                'dep' => $dep ? $dep->hour * 60 + $dep->minute : null,
            ];
        })->values();
    @endphp
    <section id="status" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 mb-4 scroll-mt-20">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-bold text-sm flex items-center gap-1.5"><x-icon name="train" class="w-4 h-4 text-rail-600" /> فين القطر دلوقتي؟</h2>
            <span class="text-[11px] text-slate-400 whitespace-nowrap">تقدير حسب الجدول</span>
        </div>

        <div id="status-summary" class="mt-2.5">
            <div class="animate-pulse h-12 bg-slate-100 rounded-2xl"></div>
        </div>

        {{-- مؤشر مصداقية القطر من بلاغات الركّاب (آخر ٩٠ يوم) --}}
        @if ($reliability)
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-emerald-50 py-2">
                    <div class="text-base font-extrabold text-emerald-700 leading-none">{{ $reliability['on_time_pct'] }}%</div>
                    <div class="text-[10px] text-emerald-700/70 mt-1">التزام بالموعد</div>
                </div>
                <div class="rounded-xl bg-amber-50 py-2">
                    <div class="text-base font-extrabold text-amber-700 leading-none">{{ $reliability['median_delay'] ? '~' . $reliability['median_delay'] . 'د' : '—' }}</div>
                    <div class="text-[10px] text-amber-700/70 mt-1">متوسط التأخير</div>
                </div>
                <div class="rounded-xl bg-slate-50 py-2">
                    <div class="text-base font-extrabold text-slate-700 leading-none">{{ $reliability['count'] }}</div>
                    <div class="text-[10px] text-slate-500 mt-1">بلاغ</div>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5 text-center">مؤشر مصداقية من بلاغات الركّاب خلال آخر ٩٠ يوم.</p>
        @endif

        {{-- بلاغات الركّاب عن الحالة (Alpine — بدون reload) --}}
        <div class="mt-3 border-t border-slate-100 pt-3" x-data="statusReport"
            data-url="{{ route('trains.status.store', $train) }}" data-login="{{ route('login') }}">

            @if ($liveStatus)
                @php
                    $stMap = ['on_time' => 'bg-emerald-50 text-emerald-800', 'delayed' => 'bg-amber-50 text-amber-800', 'cancelled' => 'bg-red-50 text-red-700'];
                @endphp
                <div class="flex items-center justify-between gap-2 rounded-xl {{ $stMap[$liveStatus['status']] ?? $stMap['on_time'] }} px-3 py-2 mb-2.5">
                    <span class="text-sm font-extrabold">{{ $liveStatus['headline'] }}</span>
                    <span class="text-[11px] opacity-80 whitespace-nowrap">{{ $liveStatus['count'] }} بلاغ · {{ $liveStatus['last_ago'] }}</span>
                </div>
            @endif

            {{-- رسالة النتيجة بدون إعادة تحميل --}}
            <div x-cloak x-show="msg" x-transition class="mb-2.5 text-xs rounded-xl px-3 py-2"
                :class="ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'" x-text="msg"></div>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-slate-400 shrink-0">بلّغ عن حالته:</span>
                @auth
                    <div class="flex items-center gap-1.5 flex-wrap" :class="loading && 'opacity-60 pointer-events-none'">
                        <button type="button" @click="send('on_time')" class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 text-xs font-bold rounded-lg px-2.5 py-1.5 transition active:scale-95"><x-icon name="check" class="w-3.5 h-3.5"/> في الموعد</button>
                        <button type="button" @click="send('delayed')" class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 hover:bg-amber-100 text-xs font-bold rounded-lg px-2.5 py-1.5 transition active:scale-95"><x-icon name="clock" class="w-3.5 h-3.5"/> متأخر</button>
                        <button type="button" @click="send('cancelled')" class="inline-flex items-center gap-1 bg-red-50 text-red-700 hover:bg-red-100 text-xs font-bold rounded-lg px-2.5 py-1.5 transition active:scale-95"><x-icon name="alert" class="w-3.5 h-3.5"/> اتلغى</button>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold text-rail-700 hover:underline">سجّل دخول للتبليغ</a>
                @endauth
            </div>
        </div>
    </section>

    <script>
        (() => {
            const summaryEl = document.getElementById('status-summary');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            // — تقدير مكان القطر حسب الجدول (وقت القاهرة الحالي) —
            const STOPS = @json($statusStops);
            function cairoNowMin() {
                try {
                    const p = new Intl.DateTimeFormat('en-GB', { timeZone: 'Africa/Cairo', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(new Date());
                    return (+p.find(x => x.type === 'hour').value) * 60 + (+p.find(x => x.type === 'minute').value);
                } catch (e) { const d = new Date(); return d.getHours() * 60 + d.getMinutes(); }
            }
            const fmtMin = (m) => {
                const h = Math.floor(m / 60), mm = String(m % 60).padStart(2, '0');
                return `${(h % 12) || 12}:${mm} ${h < 12 ? 'ص' : 'م'}`;
            };
            function scheduleEstimate() {
                const stops = STOPS.filter(s => s.dep != null || s.arr != null);
                if (stops.length < 2) return null;
                const eff = stops.map(s => ({ name: s.name, in: s.arr ?? s.dep, out: s.dep ?? s.arr }));
                const firstDep = eff[0].out, lastArr = eff[eff.length - 1].in, last = eff.length - 1;
                if (lastArr <= firstDep) return null; // رحلة عابرة لمنتصف الليل — نتجنّب تقدير غلط
                const now = cairoNowMin();

                // قبل القيام
                if (now < firstDep) return {
                    text: 'لسه ما قامش', sub: `بيقوم ${fmtMin(firstDep)} من ${eff[0].name} (بعد ~${firstDep - now} دقيقة)`,
                    cur: eff[0].name, next: eff[1].name, frac: 0, arrived: false,
                };
                // وصل آخر محطة
                if (now >= lastArr) return {
                    text: `المفروض وصل ${eff[last].name}`, sub: `الوصول حوالي ${fmtMin(lastArr)}`,
                    cur: eff[last].name, next: null, frac: 1, arrived: true,
                };
                // في الطريق — نحدّد القطعة الحالية (المحطة الحالية واللي جايه)
                for (let i = 0; i < last; i++) {
                    if (now >= eff[i].in && now <= eff[i].out) return {
                        text: `في محطة ${eff[i].name}`, sub: `جايه ${eff[i + 1].name} الساعة ${fmtMin(eff[i + 1].in)}`,
                        cur: eff[i].name, next: eff[i + 1].name, frac: 0, arrived: false,
                    };
                    if (now > eff[i].out && now < eff[i + 1].in) {
                        const f = (now - eff[i].out) / (eff[i + 1].in - eff[i].out);
                        return {
                            text: `بين ${eff[i].name} و ${eff[i + 1].name}`, sub: `جايه ${eff[i + 1].name} الساعة ${fmtMin(eff[i + 1].in)}`,
                            cur: eff[i].name, next: eff[i + 1].name, frac: Math.max(0, Math.min(1, f)), arrived: false,
                        };
                    }
                }
                return { text: 'القطار في الطريق', sub: '', cur: eff[0].name, next: eff[last].name, frac: 0.5, arrived: false };
            }

            // تايم‌لاين رأسي بين محطتين: المحطة الحالية فوق، الجاية تحت، والقطر متحرّك على الخط في مكانه.
            function progressBar(est) {
                const pct = Math.max(4, Math.min(96, est.frac * 100)).toFixed(0);
                const train = `<svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8"/><path d="M7 16l-2 4m12-4l2 4"/></svg>`;
                const next = est.next
                    ? `<div class="flex items-start gap-3">
                           <span class="mt-0.5 w-3.5 h-3.5 rounded-full bg-white border-2 border-amber-400 shrink-0"></span>
                           <div class="min-w-0">
                               <div class="text-sm font-bold text-amber-600 truncate">${esc(est.next)}</div>
                               <div class="text-[11px] text-slate-400">المحطة الجاية</div>
                           </div>
                       </div>`
                    : `<div class="flex items-center gap-3">
                           <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 shrink-0"></span>
                           <div class="text-sm font-bold text-emerald-600">وصل وجهته</div>
                       </div>`;
                return `<div class="mt-3">
                        {{-- المحطة الحالية --}}
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 w-3.5 h-3.5 rounded-full bg-rail-600 ring-4 ring-rail-100 shrink-0"></span>
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-rail-800 truncate">${esc(est.cur)}</div>
                                <div class="text-[11px] text-slate-400">${est.arrived ? 'آخر محطة' : (est.frac > 0 ? 'آخر محطة عدّاها' : 'المحطة الحالية')}</div>
                            </div>
                        </div>
                        {{-- الوصلة الرأسية + القطر متحرّك --}}
                        <div class="relative h-12 ms-[6px] my-0.5">
                            <span class="absolute inset-y-0 w-0.5 bg-linear-to-b from-rail-400 to-amber-300"></span>
                            <span class="absolute -start-[11px] grid place-items-center w-7 h-7 rounded-full bg-rail-600 text-white ring-4 ring-white shadow-md transition-all duration-500"
                                style="top:calc(${pct}% - 14px)">${train}</span>
                        </div>
                        {{-- المحطة الجاية --}}
                        ${next}
                    </div>`;
            }

            function render() {
                const est = scheduleEstimate();
                if (!est) {
                    summaryEl.innerHTML = '<div class="rounded-2xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-500">مواعيد الجدول مش كافية لتقدير مكان القطر دلوقتي.</div>';
                    return;
                }
                summaryEl.innerHTML = `<div class="rounded-2xl bg-rail-50 border border-rail-200 p-3.5">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-rail-600 text-white rounded-full px-2 py-0.5">حسب الجدول</span>
                            <span class="font-extrabold text-rail-800">${esc(est.text)}</span>
                        </div>
                        ${progressBar(est)}
                        ${est.sub ? `<p class="text-xs text-rail-700/70 mt-1">${esc(est.sub)}</p>` : ''}
                        <p class="text-[11px] text-slate-400 mt-1.5">تقدير من مواعيد الجدول — مش تتبّع فعلي.</p>
                    </div>`;
            }

            render();
            // نحدّث المكان كل دقيقة طول ما الصفحة مفتوحة
            setInterval(render, 60000);
        })();
    </script>

    {{-- ========================= الأسعار الرسمية (حيّة من الهيئة حسب القيام/النزول) ========================= --}}
    @php
        // الأسعار المخزّنة كحالة أولية (fallback) قبل ما تحمّل الحيّة.
        $faresInitial = $fares->isNotEmpty()
            ? $fares->map(fn ($f) => ['name' => $f->class_ar, 'price' => (int) round($f->price)])->values()->all()
            : [];
    @endphp
    <section id="fares" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4 scroll-mt-20">
        <h2 class="font-bold flex items-center gap-2"><x-icon name="ticket" class="w-5 h-5 text-rail-600" /> الأسعار الرسمية</h2>

        @if ($routeStops->count() >= 2 && $origin?->enr_id && $terminal?->enr_id)
            {{-- محدّدات القيام/النزول --}}
            <div class="grid grid-cols-2 gap-2 mb-2 mt-3">
                <div>
                    <label for="price-from" class="block text-[11px] text-slate-400 mb-1">محطة القيام</label>
                    <div class="relative">
                        <x-icon name="dot" class="absolute top-1/2 -translate-y-1/2 start-2.5 w-2.5 h-2.5 text-rail-600 pointer-events-none"/>
                        <select id="price-from" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 ps-7 pe-3 py-2 text-sm font-medium focus:bg-white focus:border-rail-400 focus:outline-none">
                            @foreach ($routeStops as $s)
                                <option value="{{ $s['enr'] }}" @selected($origin?->enr_id == $s['enr'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label for="price-to" class="block text-[11px] text-slate-400 mb-1">محطة النزول</label>
                    <div class="relative">
                        <x-icon name="pin" class="absolute top-1/2 -translate-y-1/2 start-2.5 w-3 h-3 text-amber-500 pointer-events-none"/>
                        <select id="price-to" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 ps-7 pe-3 py-2 text-sm font-medium focus:bg-white focus:border-rail-400 focus:outline-none">
                            @foreach ($routeStops as $s)
                                <option value="{{ $s['enr'] }}" @selected($terminal?->enr_id == $s['enr'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div id="prices-result" class="mt-1"></div>

            <script>
                (() => {
                    const out = document.getElementById('prices-result');
                    const fromSel = document.getElementById('price-from');
                    const toSel = document.getElementById('price-to');
                    const BASE = @json(route('trains.prices', $train));
                    const ROUTE = @json($routeStops);
                    const INITIAL = @json($faresInitial);
                    const START = @json(now()->addDay()->toDateString()); // نبدأ من بكرة
                    const MAX_DAYS = 14; // نزوّد لحد ١٤ يوم لو مفيش أسعار
                    const orderOf = (enr) => ROUTE.find(s => s.enr === enr)?.order;
                    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
                    const egp = (n) => Number(n).toLocaleString('ar-EG');
                    const addDays = (iso, n) => { const d = new Date(iso + 'T00:00'); d.setDate(d.getDate() + n); return d; };
                    const isoOf = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    const labelOf = (d) => { try { return new Intl.DateTimeFormat('ar-EG', { weekday: 'long', day: 'numeric', month: 'long' }).format(d); } catch (e) { return isoOf(d); } };
                    const searching = (d) => `<div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-center text-sm text-slate-500"><span class="inline-block w-4 h-4 me-1 align-middle border-2 border-slate-300 border-t-rail-500 rounded-full animate-spin"></span> بندوّر على أقرب يوم فيه أسعار… (${labelOf(d)})</div>`;

                    const card = (c, cheapest) => {
                        const on = c.price === cheapest;
                        return `<div class="relative rounded-2xl border p-3 ${on ? 'border-rail-300 bg-rail-50 ring-1 ring-rail-200' : 'border-slate-200'}">
                            ${on ? '<span class="absolute -top-2 end-2 text-[10px] font-bold bg-rail-600 text-white rounded-full px-2 py-0.5">الأرخص</span>' : ''}
                            <div class="text-xs text-slate-500 truncate">${esc(c.name)}</div>
                            <div class="text-lg font-extrabold text-rail-800 mt-0.5 whitespace-nowrap">${egp(c.price)} <span class="text-xs font-medium text-slate-400">ج.م</span></div>
                        </div>`;
                    };
                    const grid = (classes) => {
                        const cheapest = Math.min(...classes.map(c => c.price));
                        return `<div class="flex items-baseline gap-1.5 mb-2.5">
                                <span class="text-xs text-slate-400">يبدأ من</span>
                                <span class="text-xl font-extrabold text-rail-700 leading-none">${egp(cheapest)}</span>
                                <span class="text-xs text-slate-400">ج.م</span>
                            </div>`
                            + `<div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">${classes.map(c => card(c, cheapest)).join('')}</div>`;
                    };
                    // تحديث خانة إحصائية في البروفايل.
                    const setStat = (id, val) => { const el = document.getElementById(id); if (el && val) el.textContent = val; };
                    const fmtDur = (min) => `${Math.floor(min / 60)}س ${String(min % 60).padStart(2, '0')}د`;
                    const SKELETON = '<div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 animate-pulse">'
                        + Array(3).fill('<div class="rounded-2xl border border-slate-200 p-3"><div class="h-3 w-14 bg-slate-200 rounded"></div><div class="h-5 w-20 bg-slate-200 rounded mt-2"></div></div>').join('')
                        + '</div>';

                    // حالة أولية: أسعار مخزّنة لو موجودة.
                    const hasInitial = INITIAL.length > 0;
                    if (hasInitial) out.innerHTML = grid(INITIAL);

                    // منع تداخل عمليات البحث لو المستخدم غيّر المحطة وسط البحث.
                    let runId = 0;

                    async function load() {
                        const my = ++runId;
                        const from = fromSel.value, to = toSel.value;
                        const fo = orderOf(from), too = orderOf(to);
                        if (fo == null || too == null || fo >= too) {
                            out.innerHTML = '<div class="rounded-2xl bg-amber-50 border border-amber-200 p-3 text-sm text-amber-700">اختار محطة قيام قبل محطة النزول.</div>';
                            return;
                        }
                        if (!out.children.length) out.innerHTML = SKELETON;

                        try {
                            // نبدأ من بكرة، ونزوّد يوم ورا يوم لحد ما نلاقي أسعار.
                            for (let i = 0; i < MAX_DAYS; i++) {
                                if (my !== runId) return; // بحث أحدث بدأ
                                const d = addDays(START, i);
                                const u = new URL(BASE);
                                u.searchParams.set('from', from);
                                u.searchParams.set('to', to);
                                u.searchParams.set('date', isoOf(d));

                                let data;
                                try {
                                    const res = await fetch(u, { headers: { 'Accept': 'application/json' } });
                                    data = await res.json();
                                } catch (e) { continue; }
                                if (my !== runId) return;

                                if (data.ok && (data.classes || []).length) {
                                    out.innerHTML = grid(data.classes);
                                    // نحدّث المسافة والمدة في بروفايل القطر من بيانات الهيئة.
                                    if (data.distance) setStat('stat-distance', data.distance + ' كم');
                                    if (data.duration_min) setStat('stat-duration', fmtDur(data.duration_min));
                                    return;
                                }
                                // مفيش أسعار لليوم ده — نوضّح إننا بندوّر ونكمّل.
                                if (!hasInitial || i > 0) out.innerHTML = searching(d);
                            }
                            out.innerHTML = '<div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-center text-sm text-slate-500">مفيش أسعار متاحة على نظام الهيئة للقطعة دي في الأيام الجاية.</div>';
                        } finally { /* لا شيء */ }
                    }

                    fromSel.addEventListener('change', load);
                    toSel.addEventListener('change', load);

                    // نجيب الأسعار (والمسافة/المدة للبروفايل) فورًا عند فتح الصفحة.
                    load();
                })();
            </script>
        @elseif (!empty($faresInitial))
            {{-- مفيش أكواد ENR للمحطات — نعرض الأسعار المخزّنة --}}
            @php $minFare = collect($faresInitial)->min('price'); @endphp
            <p class="text-xs text-slate-400 mt-1 mb-3">من نظام الحجز الرسمي لهيئة السكة الحديد.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                @foreach ($faresInitial as $f)
                    <div class="relative rounded-2xl border p-3 {{ $f['price'] == $minFare ? 'border-rail-300 bg-rail-50 ring-1 ring-rail-200' : 'border-slate-200' }}">
                        @if ($f['price'] == $minFare && count($faresInitial) > 1)
                            <span class="absolute -top-2 end-2 text-[10px] font-bold bg-rail-600 text-white rounded-full px-2 py-0.5">الأرخص</span>
                        @endif
                        <div class="text-xs text-slate-500 truncate">{{ $f['name'] }}</div>
                        <div class="text-lg font-extrabold text-rail-800 mt-0.5 whitespace-nowrap">{{ number_format($f['price']) }} <span class="text-xs font-medium text-slate-400">ج.م</span></div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-center mt-3">
                <p class="text-sm text-slate-500">الأسعار الرسمية للمسار ده لسه مش متاحة.</p>
            </div>
        @endif

        <a href="{{ route('report', ['type' => 'price', 'train' => $train->number]) }}"
            class="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5" />
            السعر غلط؟ بلّغنا
        </a>
    </section>

    {{-- ========================= المقاعد المتاحة (مخفي مؤقتًا لحين إذن الهيئة) ========================= --}}
    @php $isAuth = auth()->check(); $isPremium = (bool) auth()->user()?->isPremium(); @endphp
    @if (config('enr.show_seats') && $origin?->enr_id && $terminal?->enr_id)
        <section id="live" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4 scroll-mt-20">
            <h2 class="font-bold flex items-center gap-2">
                <span class="relative flex w-2.5 h-2.5">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-rail-400 opacity-75 animate-ping"></span>
                    <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-rail-600"></span>
                </span>
                المقاعد المتاحة دلوقتي
            </h2>
            <p class="text-xs text-slate-400 mt-1 mb-3">مباشر من نظام الهيئة — مواعيد دقيقة، عربات، درجات, أسعار، ومقاعد فاضية.
            </p>

            {{-- اختيار محطة القيام والوصول (لجلب توافر قطعة مختلفة من المسار) --}}
            <div class="grid grid-cols-2 gap-2 mb-2">
                <div>
                    <label for="live-from" class="block text-[11px] text-slate-400 mb-1">من محطة</label>
                    <div class="relative">
                        <x-icon name="dot" class="absolute top-1/2 -translate-y-1/2 start-2.5 w-2.5 h-2.5 text-rail-600 pointer-events-none"/>
                        <select id="live-from" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 ps-7 pe-3 py-2 text-sm font-medium focus:bg-white focus:border-rail-400 focus:outline-none">
                            @foreach ($routeStops as $s)
                                <option value="{{ $s['enr'] }}" @selected($origin?->enr_id == $s['enr'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label for="live-to" class="block text-[11px] text-slate-400 mb-1">إلى محطة</label>
                    <div class="relative">
                        <x-icon name="pin" class="absolute top-1/2 -translate-y-1/2 start-2.5 w-3 h-3 text-amber-500 pointer-events-none"/>
                        <select id="live-to" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 ps-7 pe-3 py-2 text-sm font-medium focus:bg-white focus:border-rail-400 focus:outline-none">
                            @foreach ($routeStops as $s)
                                <option value="{{ $s['enr'] }}" @selected($terminal?->enr_id == $s['enr'])>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap mb-3">
                <div class="relative flex-1 min-w-40">
                    <x-icon name="calendar"
                        class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none" />
                    <input type="date" id="live-date" value="{{ now()->toDateString() }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-2 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                </div>
                <button id="live-btn"
                    class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold rounded-xl px-4 py-2 transition">
                    <x-icon name="refresh" class="w-4 h-4" /> تحديث
                </button>
            </div>
            <div id="live-result"></div>
        </section>

        <script>
            (() => {
                const btn = document.getElementById('live-btn');
                const out = document.getElementById('live-result');
                const dateInput = document.getElementById('live-date');
                const fromSel = document.getElementById('live-from');
                const toSel = document.getElementById('live-to');
                // لو جاي من إشعار/رابط بتاريخ محدد، نستخدمه.
                const _qDate = new URLSearchParams(location.search).get('date');
                if (_qDate && /^\d{4}-\d{2}-\d{2}$/.test(_qDate)) dateInput.value = _qDate;
                const SEARCH_URL = @json(config('enr.search_url'));
                const NUMBER = @json($train->number);
                const ORIGIN_ENR = @json($origin->enr_id);
                const TERMINAL_ENR = @json($terminal->enr_id);
                const ROUTE = @json($routeStops); // [{enr,name,order}] لترتيب المحطات
                const orderOf = (enr) => ROUTE.find(s => s.enr === enr)?.order;
                const nameOf = (enr) => ROUTE.find(s => s.enr === enr)?.name ?? '';
                const SKELETON = '<div class="animate-pulse space-y-3">' +
                    '<div class="bg-white rounded-lg border border-slate-200 p-3">' +
                    '<div class="flex gap-2 mb-3"><div class="h-5 w-14 bg-slate-200 rounded"></div><div class="h-5 w-14 bg-slate-200 rounded"></div><div class="h-5 w-20 bg-slate-200 rounded"></div></div>' +
                    '<div class="flex flex-wrap gap-1.5">' + Array(16).fill('<div class="w-7 h-9 bg-slate-200 rounded-md"></div>').join('') + '</div>' +
                    '</div></div>';
                // محطات قيام أبعد على نفس القطار (الأقرب فالأبعد).
                const ALTS = @json($boardingAlternatives);

                const errBox = (msg) => `<div class="rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">${msg} جرّب زر «تحديث» تاني.</div>`;
                const CSRF = document.querySelector('meta[name=csrf-token]')?.content;

                // مراقبة المقاعد (Premium)
                const IS_AUTH = @json($isAuth);
                const IS_PREMIUM = @json($isPremium);
                const SEATWATCH_URL = @json(route('trains.seatwatch', $train));
                const LOGIN_URL = @json(route('login'));
                const PREMIUM_URL = @json(route('premium'));

                // زر «نبّهني أول ما يفضى كرسي» — يظهر لما القطار مكتمل (مجاني لأي مسجّل).
                const BELL = '<svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>';
                function seatWatchCta() {
                    if (!IS_AUTH) {
                        return `<a href="${LOGIN_URL}" class="mt-3 flex items-center justify-center gap-2 rounded-2xl bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold px-4 py-3 transition">${BELL} سجّل عشان نبّهك أول ما يفضى كرسي</a>`;
                    }
                    return `<button type="button" data-seatwatch class="mt-3 w-full flex items-center justify-center gap-2 rounded-2xl bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white text-sm font-bold px-4 py-3 transition">${BELL} نبّهني أول ما يفضى كرسي</button>`;
                }

                async function onSeatWatch(btn) {
                    const orig = btn.textContent;
                    const fail = (msg) => {
                        btn.disabled = false; btn.textContent = orig;
                        btn.nextElementSibling?.remove();
                        btn.insertAdjacentHTML('afterend', `<p class="seatwatch-err text-xs text-red-600 mt-2 text-center">${msg}</p>`);
                    };
                    btn.disabled = true; btn.textContent = 'جاري التفعيل…';
                    btn.parentElement.querySelector('.seatwatch-err')?.remove();
                    try {
                        const res = await fetch(SEATWATCH_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                            body: JSON.stringify({
                                from_enr: fromSel.value, to_enr: toSel.value,
                                from_name: nameOf(fromSel.value), to_name: nameOf(toSel.value),
                                date: dateInput.value,
                            }),
                        });
                        if (res.status === 401 || res.status === 419) { location.href = LOGIN_URL; return; }
                        if (!res.ok) {
                            let msg = 'تعذّر التفعيل، حاول تاني.';
                            try { const j = await res.json(); if (j.message) msg = j.message; } catch (_) {}
                            fail(msg);
                            return;
                        }
                        btn.outerHTML = '<div class="mt-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold px-4 py-3 text-center">✓ تمام! هنبّهك أول ما يفضى كرسي على المسار ده.</div>';
                    } catch (e) { fail('تعذّر الاتصال، حاول تاني.'); }
                }

                // إرسال رد الهيئة للموقع لتحديث المواعيد/الأسعار — مرّة كل ساعة لكل (مسار+تاريخ).
                function snapshot(data, from) {
                    const key = `${NUMBER}:${from}:${dateInput.value}`;
                    let map = {};
                    try { map = JSON.parse(localStorage.getItem('qm:snap') || '{}'); } catch (e) { }
                    if (map[key] && Date.now() - map[key] < 3600000) return;
                    fetch('{{ route('enr.snapshot') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(data),
                    }).then(() => {
                        map[key] = Date.now();
                        try { localStorage.setItem('qm:snap', JSON.stringify(map)); } catch (e) { }
                    }).catch(() => { });
                }

                // اقتراح محطات أبعد لما مفيش مقاعد من المحطة الحالية.
                // ALTS مرتّبة من الأقرب للأبعد؛ نعرض فقط ما هو أبعد من المحطة الحالية.
                function suggestFarther(currentName, currentEnr) {
                    const idx = ALTS.findIndex(a => a.enr === currentEnr);
                    const candidates = idx === -1 ? ALTS : ALTS.slice(idx + 1);
                    if (!candidates.length) return '';
                    const chips = candidates.map(a =>
                        `<button type="button" data-alt-enr="${a.enr}" data-alt-name="${a.name}"
                                    class="alt-board border border-rail-200 bg-rail-50 hover:bg-rail-100 text-rail-800 text-sm font-medium rounded-lg px-3 py-1.5 transition">
                                    ${a.name}
                                </button>`).join('');
                    return `
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-bold text-amber-800 mb-1">مفيش مقاعد من ${currentName}؟</p>
                                    <p class="text-xs text-amber-700 mb-3">القطار جاي من محطات أبعد — جرّب تحجز من محطة قبلها وانزل في وجهتك، يمكن يكون فيها مقاعد:</p>
                                    <div class="flex flex-wrap gap-2">${chips}</div>
                                </div>`;
                }

                // حالة فارغة: القطار مش شغّال في اليوم ده (إجازة/يوم إجازته) + اقتراح أيام قريبة.
                function dayChip(offset) {
                    const d = new Date(dateInput.value + 'T00:00');
                    if (isNaN(d)) return '';
                    d.setDate(d.getDate() + offset);
                    const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    let label;
                    try { label = new Intl.DateTimeFormat('ar-EG', { weekday: 'long', day: 'numeric', month: 'short' }).format(d); }
                    catch (e) { label = iso; }
                    return `<button type="button" data-go-date="${iso}"
                        class="border border-rail-200 bg-rail-50 hover:bg-rail-100 text-rail-800 text-sm font-bold rounded-xl px-3 py-1.5 transition">${label}</button>`;
                }
                function emptyState() {
                    return `<div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 grid place-items-center rounded-2xl bg-white text-slate-300 ring-1 ring-slate-200">
                            <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="13" rx="2"/><path d="M5 11h14M9 3v8m6-8v8M7 16l-2 4m12-4l2 4"/></svg>
                        </div>
                        <p class="font-bold text-slate-700">القطار ده مش شغّال في اليوم ده</p>
                        <p class="text-sm text-slate-500 mt-1 leading-relaxed">ممكن يكون إجازة أو يوم إجازته، فمفيش رحلات على نظام الهيئة — حتى لو ميعاده لسه ماجاش. جرّب يوم تاني:</p>
                        <div class="flex flex-wrap gap-2 justify-center mt-3">${[1, 2, 3].map(dayChip).join('')}</div>
                    </div>`;
                }

                async function loadLive() {
                    if (typeof EnrLive === 'undefined') {
                        out.innerHTML = errBox('تعذّر تحميل أداة العرض.');
                        return;
                    }

                    const from = fromSel.value, to = toSel.value;
                    const name = nameOf(from), toName = nameOf(to);
                    const fo = orderOf(from), too = orderOf(to);

                    // لازم محطة القيام تبقى قبل محطة الوصول في المسار.
                    if (fo == null || too == null || fo >= too) {
                        out.innerHTML = errBox('اختار محطة قيام قبل محطة الوصول.');
                        return;
                    }

                    btn.disabled = true;
                    btn.textContent = 'جاري الجلب…';
                    out.innerHTML = SKELETON;

                    // مهلة زمنية حتى لا تظل الصفحة معلّقة لو تأخّر نظام الهيئة.
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 25000);

                    try {
                        const url = EnrLive.buildUrl(SEARCH_URL, { from, to, number: NUMBER, date: dateInput.value });
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, signal: controller.signal });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();

                        // عنوان يوضّح القطعة المعروضة لو مختلفة عن المسار الافتراضي.
                        const heading = (from !== ORIGIN_ENR || to !== TERMINAL_ENR)
                            ? `<p class="text-sm text-rail-700 font-bold mb-2">التوافر: ${name} ← ${toName}</p>`
                            : '';

                        // الهيئة بترجّع فاضي لو القطار مش شغّال في اليوم ده (إجازة مثلًا) — حتى لو الميعاد ماجاش.
                        const hasTrips = Array.isArray(data) && data.some(i => i.steps && i.steps[0]);
                        let html = heading + (hasTrips ? EnrLive.render(data) : emptyState());

                        // لو فيه رحلات لكن كلها بدون مقاعد: نبّهني أول ما يفضى كرسي + اقتراح محطات أبعد.
                        if (hasTrips && EnrLive.totalSeats(data) === 0) {
                            html += seatWatchCta();
                            html += suggestFarther(name, from);
                        }
                        out.innerHTML = html;

                        // نلتقط البيانات (مواعيد + أسعار) لتحديث الموقع تلقائيًا — مرة كل ساعة لكل مسار.
                        if (hasTrips) snapshot(data, from);

                        // اختيار محطة قيام أبعد من الاقتراح: نظبط القائمة ونعيد الجلب.
                        out.querySelectorAll('.alt-board').forEach(b =>
                            b.addEventListener('click', () => { selectFrom(b.dataset.altEnr); loadLive(); }));

                        // تفعيل مراقبة المقاعد.
                        out.querySelectorAll('[data-seatwatch]').forEach(b =>
                            b.addEventListener('click', () => onSeatWatch(b)));

                        // أزرار «جرّب يوم تاني» في الحالة الفارغة.
                        out.querySelectorAll('[data-go-date]').forEach(b =>
                            b.addEventListener('click', () => { dateInput.value = b.dataset.goDate; loadLive(); }));
                    } catch (e) {
                        out.innerHTML = errBox(e.name === 'AbortError'
                            ? 'انتهت مهلة الاتصال .'
                            : `تعذّر  احضار البيانات (${e.message}).`);
                    } finally {
                        clearTimeout(timer);
                        btn.disabled = false;
                        btn.textContent = 'تحديث';
                    }
                }

                // يضبط قيمة قائمة محطة القيام (يضيف الخيار لو مش موجود).
                function selectFrom(enr) {
                    if (![...fromSel.options].some(o => o.value === enr)) {
                        const o = document.createElement('option');
                        o.value = enr; o.textContent = nameOf(enr) || enr;
                        fromSel.appendChild(o);
                    }
                    fromSel.value = enr;
                }

                btn.addEventListener('click', () => loadLive());
                dateInput.addEventListener('change', () => loadLive());
                fromSel.addEventListener('change', () => loadLive());
                toSel.addEventListener('change', () => loadLive());

                // نجلب التوافر اللحظي لمّا القسم يقرب من الشاشة فقط (أسرع تحميل + طلبات أقل).
                let loaded = false;
                const loadOnce = () => { if (!loaded) { loaded = true; loadLive(); } };
                const section = document.getElementById('live');

                function start() {
                    if ('IntersectionObserver' in window && section) {
                        const io = new IntersectionObserver((entries) => {
                            if (entries.some((e) => e.isIntersecting)) { loadOnce(); io.disconnect(); }
                        }, { rootMargin: '300px' });
                        io.observe(section);
                    } else {
                        loadOnce();
                    }
                    // لو جاي من إشعار المقاعد (#live) ننزل للقسم (وده هيشغّل الجلب).
                    if (location.hash === '#live') {
                        section?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        loadOnce();
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', start);
                } else {
                    start();
                }
            })();
        </script>
    @endif

    {{-- ========================= جدول المحطات ========================= --}}
    <section id="schedule" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4 scroll-mt-20">
        <h2 class="font-bold flex items-center gap-2 flex-wrap">
            <x-icon name="station" class="w-5 h-5 text-rail-600" />
            جدول المحطات
            <span class="text-xs font-normal text-slate-400">({{ $scheduleStops->count() }} محطة)</span>
            @if ($validSegment)
                <span class="text-xs font-normal text-rail-600">— رحلتك: {{ $origin->name_ar }} ←
                    {{ $terminal->name_ar }}</span>
            @endif
        </h2>
        <p class="text-xs text-slate-400 mt-1 mb-4">السعر لكل محطة = حتى {{ $terminal?->name_ar }}</p>

        <ol class="relative">
            @foreach ($scheduleStops as $stop)
                @php
                    $isFirst = $loop->first;
                    $isLast = $loop->last;
                    $stationFare = $isLast ? null : $stationFares->get($stop->station_id);
                    $arr = \App\Support\Format::time($stop->arrival_time);
                    $dep = \App\Support\Format::time($stop->departure_time);
                    // الوقت الأساسي: قيام لأول محطة، وصول لباقي المحطات.
                    $primary = $isFirst ? ($dep ?? $arr) : ($arr ?? $dep);
                    // وقت القيام الثانوي للمحطات الوسطى (لو مختلف عن الوصول).
                    $secondary = (!$isFirst && !$isLast && $arr && $dep && $arr !== $dep) ? $dep : null;
                @endphp
                <li class="flex gap-2.5">
                    {{-- عمود الوقت --}}
                    <div class="w-14 shrink-0 text-end pt-0.5">
                        @if ($primary)
                            <div class="text-sm font-extrabold leading-tight whitespace-nowrap {{ $isFirst ? 'text-rail-700' : ($isLast ? 'text-amber-600' : 'text-slate-700') }}">{{ $primary }}</div>
                            @if ($secondary)
                                <div class="text-[10px] text-slate-400 whitespace-nowrap mt-0.5">قيام {{ $secondary }}</div>
                            @endif
                        @else
                            <div class="text-xs text-slate-300 pt-0.5">—</div>
                        @endif
                    </div>

                    {{-- عمود القضبان --}}
                    <div class="relative flex flex-col items-center w-5 shrink-0">
                        @if ($isFirst)
                            <span class="mt-1 w-4 h-4 rounded-full bg-rail-600 ring-4 ring-rail-100 shrink-0"></span>
                        @elseif ($isLast)
                            <span class="-mt-1 text-amber-500 shrink-0"><x-icon name="pin" class="w-6 h-6" /></span>
                        @else
                            <span class="mt-1.5 w-3 h-3 rounded-full bg-white border-2 border-rail-300 shrink-0"></span>
                        @endif
                        @unless ($isLast)
                            <span class="w-1 flex-1 rounded-full bg-linear-to-b from-rail-300 to-rail-200 my-1"></span>
                        @endunless
                    </div>

                    {{-- بطاقة المحطة --}}
                    <a href="{{ route('stations.show', $stop->station) }}" class="flex-1 min-w-0 pb-6 group">
                        <div class="flex items-center justify-between gap-2 -mx-2 px-2 py-1.5 rounded-xl group-hover:bg-slate-50 transition">
                            <div class="min-w-0 flex items-center gap-2 flex-wrap">
                                <span class="font-bold truncate {{ $isFirst || $isLast ? 'text-rail-800' : 'text-slate-700' }} group-hover:text-rail-600 transition">{{ $stop->station->name_ar }}</span>
                                @if ($isFirst)
                                    <span class="text-[10px] font-bold text-rail-700 bg-rail-50 rounded-full px-2 py-0.5">قيام</span>
                                @elseif ($isLast)
                                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 rounded-full px-2 py-0.5">وصول</span>
                                @endif
                            </div>
                            @if ($stationFare !== null)
                                <span class="shrink-0 text-xs font-bold bg-rail-50 text-rail-700 rounded-lg px-2 py-1 whitespace-nowrap">{{ number_format($stationFare) }} ج.م</span>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ol>

        <a href="{{ route('report', ['type' => 'schedule', 'train' => $train->number]) }}"
            class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-rail-600 transition">
            <x-icon name="flag" class="w-3.5 h-3.5" />
            ميعاد غلط؟ بلّغنا
        </a>
    </section>

    {{-- ========================= آراء الركّاب (مجتمع القطر — Alpine بدون reload) ========================= --}}
    @php
        $starSvg = '<path d="M12 3.5l2.6 5.3 5.8.9-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.2-4.1 5.8-.9z"/>';
    @endphp
    <section id="reviews" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4 scroll-mt-20"
        x-data="reviewForm({{ $myReview->rating ?? 0 }}, '', {{ $reviewsAvg ?: 0 }}, {{ $reviewsCount ?: 0 }}, {{ $myReview ? 'true' : 'false' }})"
        x-init="comment = ($el.dataset.initComment ?? '')"
        data-url="{{ route('trains.reviews.store', $train) }}"
        data-login="{{ route('login') }}"
        data-init-comment="{{ $myReview->comment ?? '' }}">

        <div class="flex items-center justify-between gap-2 mb-1">
            <h2 class="font-bold flex items-center gap-2"><x-icon name="star" class="w-5 h-5 text-amber-400"/> آراء الركّاب</h2>
            <div class="flex items-center gap-1.5" x-show="count > 0" x-cloak>
                <span class="inline-flex gap-0.5">
                    <template x-for="i in 5" :key="i">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" :class="i <= Math.round(avg) ? 'text-amber-400' : 'text-slate-300'" :fill="i <= Math.round(avg) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">{!! $starSvg !!}</svg>
                    </template>
                </span>
                <span class="text-sm font-bold text-slate-700" x-text="avg"></span>
                <span class="text-xs text-slate-400">(<span x-text="count"></span>)</span>
            </div>
        </div>

        {{-- نموذج التقييم --}}
        @auth
            <form @submit.prevent="submit" class="mt-3 rounded-2xl bg-slate-50 ring-1 ring-slate-100 p-4">
                <p class="text-sm font-medium mb-2" x-text="(hadReview || submitted) ? 'عدّل تقييمك' : 'قيّم رحلتك مع القطر ده'"></p>
                <div class="flex items-center gap-1 mb-3">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="setRating({{ $i }})" aria-label="{{ $i }} نجوم"
                            class="w-9 h-9 grid place-items-center active:scale-90 transition"
                            :class="filled({{ $i }}) ? 'text-amber-400' : 'text-slate-300'">
                            <svg viewBox="0 0 24 24" class="w-7 h-7" :fill="filled({{ $i }}) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">{!! $starSvg !!}</svg>
                        </button>
                    @endfor
                </div>
                <textarea x-model="comment" maxlength="500" rows="2" placeholder="اكتب رأيك (اختياري)…"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-rail-400 focus:outline-none"></textarea>
                <div x-cloak x-show="msg" x-transition class="mt-2 text-sm rounded-xl px-3 py-2"
                    :class="ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'" x-text="msg"></div>
                <button type="submit" :disabled="loading"
                    class="mt-2 w-full bg-rail-600 hover:bg-rail-700 active:scale-[.99] disabled:opacity-60 text-white font-bold rounded-xl px-4 py-2.5 transition">
                    <span x-text="loading ? 'جاري الحفظ…' : ((hadReview || submitted) ? 'حدّث التقييم' : 'أضف تقييمك')"></span>
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="mt-3 flex items-center justify-center gap-2 rounded-2xl bg-slate-50 ring-1 ring-slate-100 text-rail-700 font-bold text-sm px-4 py-3 hover:ring-rail-200 transition">
                سجّل دخول عشان تقيّم القطر
            </a>
        @endauth

        {{-- قائمة الآراء --}}
        <div class="mt-4 space-y-3">
            {{-- رأيك بعد الإضافة/التحديث (فوري) --}}
            <template x-if="submitted && mine">
                <div class="border-t border-slate-50 pt-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-8 h-8 grid place-items-center rounded-full bg-rail-50 text-rail-700 text-xs font-bold shrink-0" x-text="mineInitial"></span>
                            <span class="font-bold text-sm truncate" x-text="mine.user"></span>
                            <span class="text-[10px] font-bold text-rail-700 bg-rail-50 rounded-full px-1.5 py-0.5 shrink-0">رأيك</span>
                        </div>
                        <span class="inline-flex shrink-0 gap-0.5">
                            <template x-for="i in 5" :key="i">
                                <svg viewBox="0 0 24 24" class="w-4 h-4" :class="i <= mine.rating ? 'text-amber-400' : 'text-slate-300'" :fill="i <= mine.rating ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">{!! $starSvg !!}</svg>
                            </template>
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 mt-1.5 leading-relaxed" x-show="mine.comment" x-text="mine.comment"></p>
                    <p class="text-[11px] text-slate-400 mt-1">دلوقتي</p>
                </div>
            </template>

            @forelse ($reviews as $rev)
                <div class="border-t border-slate-50 pt-3 first:border-0 first:pt-0"
                    @if ($myReview && $rev->id === $myReview->id) x-show="!submitted" x-cloak @endif>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-8 h-8 grid place-items-center rounded-full bg-rail-50 text-rail-700 text-xs font-bold shrink-0">{{ mb_substr($rev->user->name ?? 'مستخدم', 0, 1) }}</span>
                            <span class="font-bold text-sm truncate">{{ $rev->user->name ?? 'مستخدم' }}</span>
                        </div>
                        <span class="inline-flex shrink-0">{!! $starRowLight($rev->rating) !!}</span>
                    </div>
                    @if ($rev->comment)
                        <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">{{ $rev->comment }}</p>
                    @endif
                    <p class="text-[11px] text-slate-400 mt-1">{{ $rev->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-400 text-center py-3" x-show="!submitted">لسه مفيش آراء — كن أول من يقيّم القطر ده.</p>
            @endforelse
        </div>

        <a href="{{ route('trains.top') }}"
            class="mt-4 flex items-center justify-center gap-1.5 rounded-2xl bg-slate-50 ring-1 ring-slate-100 text-rail-700 font-bold text-sm px-4 py-2.5 hover:ring-rail-200 transition">
            <x-icon name="star" class="w-4 h-4 text-amber-400" /> شوف أعلى القطارات تقييمًا
        </a>
    </section>

    {{-- ========================= قطارات تانية على نفس المسار ========================= --}}
    @if (!empty($sameRouteTrains))
        <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4">
            <div class="flex items-baseline justify-between gap-2 mb-3">
                <h2 class="font-bold flex items-center gap-2"><x-icon name="train" class="w-5 h-5 text-rail-600" /> قطارات تانية على نفس المسار</h2>
                <span class="text-xs text-slate-400 truncate">{{ $origin->name_ar }} ← {{ $terminal->name_ar }}</span>
            </div>

            <div class="space-y-2">
                @foreach ($sameRouteTrains as $rt)
                    <a href="{{ $rt['url'] }}"
                        class="flex items-center gap-3 rounded-2xl border border-slate-200 hover:border-rail-300 hover:bg-rail-50/50 active:scale-[.99] p-3 transition">
                        <span class="shrink-0 grid place-items-center min-w-12 h-10 px-2 rounded-xl bg-rail-50 text-rail-700 font-extrabold text-sm ring-1 ring-rail-100">{{ $rt['number'] }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-bold text-slate-700 truncate">{{ $rt['type'] }}</div>
                            <div class="text-xs text-slate-500 mt-0.5 whitespace-nowrap">
                                <span class="font-bold text-slate-700">{{ $rt['depart'] ?? '—' }}</span>
                                <span class="text-slate-300">←</span>
                                <span class="font-bold text-slate-700">{{ $rt['arrive'] ?? '—' }}</span>
                            </div>
                        </div>
                        @if ($rt['price'])
                            <span class="shrink-0 text-xs font-bold bg-slate-50 text-slate-600 rounded-lg px-2 py-1 whitespace-nowrap">من {{ number_format($rt['price']) }} ج.م</span>
                        @endif
                        <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
