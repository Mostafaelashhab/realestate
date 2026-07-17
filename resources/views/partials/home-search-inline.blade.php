{{-- بحث inline: حقول تفتح منتقي محطات بفلتر بحث بالكتابة --}}
@php
    $stationsJs = $stations->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar, 'lat' => $s->lat, 'lng' => $s->lng])->values();
@endphp
<section id="search-box" class="scroll-mt-4 mb-4">
    @error('number')
        <div class="flex items-center gap-2 bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-3">
            <x-icon name="alert" class="w-5 h-5 shrink-0"/> {{ $message }}
        </div>
    @enderror

    <form action="{{ route('search') }}" method="GET" id="inline-search">
        <input type="hidden" name="from" id="s-from">
        <input type="hidden" name="to" id="s-to">

        <div class="bg-white rounded-3xl shadow-lg shadow-slate-300/40 ring-1 ring-slate-100 p-2">
            {{-- محطة القيام --}}
            <button type="button" data-picker="from" class="w-full flex items-center gap-3 px-2 py-3 rounded-2xl text-start active:bg-slate-50 transition">
                <span class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl bg-rail-100 text-rail-700"><span class="w-3 h-3 rounded-full bg-rail-600"></span></span>
                <div class="flex-1 min-w-0">
                    <div class="text-[11px] font-bold text-slate-400">محطة القيام</div>
                    <div class="font-extrabold text-slate-400 truncate" id="from-disp">اختار المحطة</div>
                </div>
                <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <div class="relative border-t border-slate-100 mx-3">
                <button type="button" id="swap-btn" aria-label="عكس المحطتين"
                    class="absolute -top-4 end-2 w-9 h-9 grid place-items-center rounded-full bg-white ring-1 ring-slate-200 shadow-md text-rail-600 active:scale-90 transition">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                </button>
            </div>

            {{-- محطة النزول --}}
            <button type="button" data-picker="to" class="w-full flex items-center gap-3 px-2 py-3 rounded-2xl text-start active:bg-slate-50 transition">
                <span class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl bg-amber-100 text-amber-600"><x-icon name="pin" class="w-4 h-4"/></span>
                <div class="flex-1 min-w-0">
                    <div class="text-[11px] font-bold text-slate-400">محطة النزول</div>
                    <div class="font-extrabold text-slate-400 truncate" id="to-disp">اختار المحطة</div>
                </div>
                <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <div class="border-t border-slate-100 mx-3"></div>

            {{-- التاريخ --}}
            <div class="relative flex items-center gap-3 px-2 py-3 rounded-2xl">
                <span class="w-11 h-11 shrink-0 grid place-items-center rounded-2xl bg-sky-100 text-sky-600"><x-icon name="calendar" class="w-5 h-5"/></span>
                <div class="flex-1 min-w-0">
                    <div class="text-[11px] font-bold text-slate-400">تاريخ السفر</div>
                    <div class="font-extrabold text-slate-800 truncate" id="date-disp">النهاردة</div>
                </div>
                <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                <input type="date" name="date" id="s-date" value="{{ now()->toDateString() }}" aria-label="تاريخ السفر" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
            </div>
        </div>

        <div class="flex items-center gap-2 mt-3 flex-wrap">
            <div id="s-days" class="flex gap-2"></div>
            <button type="button" id="near-btn" class="ms-auto inline-flex items-center gap-1 text-xs font-bold text-rail-700 hover:text-rail-800">
                <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/> أقرب محطة ليّ
            </button>
        </div>
        <p id="near-msg" hidden class="text-xs text-amber-600 mt-1"></p>
        <p id="search-err" hidden class="text-sm text-red-600 mt-2"></p>

        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl py-4 mt-3 shadow-lg shadow-rail-600/25 transition">
            <x-icon name="search" class="w-5 h-5"/> ابحث عن القطارات
        </button>
    </form>

    <div class="text-center mt-3">
        <button type="button" id="num-toggle" class="mx-auto inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-rail-600 transition">
            <x-icon name="search" class="w-3.5 h-3.5"/> أو دوّر برقم القطار
        </button>
        <form action="{{ route('search') }}" method="GET" id="num-form" hidden class="flex gap-2 mt-3">
            <input type="text" name="number" inputmode="numeric" placeholder="رقم القطار (مثال: 936)" required
                class="flex-1 min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:border-rail-500 transition">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 active:scale-95 text-white font-bold rounded-2xl px-6 transition whitespace-nowrap">اعرض</button>
        </form>
    </div>

    {{-- منتقي المحطات (bottom sheet بفلتر بحث) --}}
    <div id="picker" hidden class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-picker-close></div>
        <div class="absolute inset-x-0 bottom-0 mx-auto max-w-xl bg-white rounded-t-3xl p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl flex flex-col" style="max-height:80vh">
            <div class="w-10 h-1 rounded-full bg-slate-200 mx-auto mb-3"></div>
            <div class="flex items-center gap-2 mb-3">
                <h3 id="picker-title" class="font-extrabold text-slate-800 flex-1">اختار المحطة</h3>
                <button type="button" data-picker-close aria-label="إغلاق" class="w-8 h-8 grid place-items-center rounded-xl text-slate-400 hover:bg-slate-100">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="relative mb-2">
                <x-icon name="search" class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-slate-400 pointer-events-none"/>
                <input type="text" id="picker-search" placeholder="اكتب اسم المحطة…" autocomplete="off"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 ps-9 pe-3 py-3 text-sm focus:bg-white focus:border-rail-500 focus:outline-none">
            </div>
            <button type="button" id="picker-near" class="self-start inline-flex items-center gap-1.5 text-xs font-bold text-rail-700 hover:text-rail-800 mb-2">
                <x-icon name="pin" class="w-3.5 h-3.5 text-amber-500"/> أقرب محطة ليّ
            </button>
            <ul id="picker-list" class="overflow-y-auto flex-1 -mx-1 px-1"></ul>
        </div>
    </div>

    <script>
        (() => {
            const STATIONS = @json($stationsJs);
            const norm = (s) => s.replace(/[إأآ]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/[ً-ْٰ]/g, '').trim();
            const TODAY = @json(now()->toDateString());
            const NEAR = STATIONS.filter(s => s.lat && s.lng);
            const PH = 'اختار المحطة';

            const box = document.getElementById('search-box');
            const form = document.getElementById('inline-search');
            const fromHidden = document.getElementById('s-from');
            const toHidden = document.getElementById('s-to');
            const fromDisp = document.getElementById('from-disp');
            const toDisp = document.getElementById('to-disp');
            const dateInput = document.getElementById('s-date');
            const dateDisp = document.getElementById('date-disp');
            const daysBox = document.getElementById('s-days');
            const err = document.getElementById('search-err');

            const dispOf = (t) => t === 'from' ? fromDisp : toDisp;
            const hiddenOf = (t) => t === 'from' ? fromHidden : toHidden;
            function setStation(t, id, name) {
                hiddenOf(t).value = id;
                const d = dispOf(t);
                d.textContent = name; d.classList.remove('text-slate-400'); d.classList.add('text-slate-800');
            }

            // ── المنتقي ──
            const picker = document.getElementById('picker');
            const pTitle = document.getElementById('picker-title');
            const pSearch = document.getElementById('picker-search');
            const pList = document.getElementById('picker-list');
            let target = 'from';

            function renderList(q) {
                const nq = norm(q || '');
                const other = target === 'from' ? toHidden.value : fromHidden.value;
                const items = STATIONS.filter(s => String(s.id) !== String(other) && (!nq || norm(s.name).includes(nq)));
                pList.innerHTML = items.slice(0, 100).map(s =>
                    `<li><button type="button" data-id="${s.id}" data-name="${s.name}" class="w-full text-start px-3 py-3 rounded-xl text-sm font-medium hover:bg-rail-50 active:bg-rail-100 transition">${s.name}</button></li>`
                ).join('') || '<li class="px-3 py-4 text-sm text-slate-400 text-center">مفيش محطة بالاسم ده</li>';
            }
            function openPicker(t) {
                target = t;
                pTitle.textContent = t === 'from' ? 'محطة القيام' : 'محطة النزول';
                pSearch.value = '';
                renderList('');
                picker.hidden = false;
                document.body.style.overflow = 'hidden';
                setTimeout(() => pSearch.focus(), 100);
            }
            function closePicker() { picker.hidden = true; document.body.style.overflow = ''; }

            document.querySelectorAll('[data-picker]').forEach(b => b.addEventListener('click', () => openPicker(b.dataset.picker)));
            picker.querySelectorAll('[data-picker-close]').forEach(b => b.addEventListener('click', closePicker));
            pSearch.addEventListener('input', () => renderList(pSearch.value));
            pSearch.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); pList.querySelector('[data-id]')?.click(); } });
            pList.addEventListener('click', (e) => {
                const b = e.target.closest('[data-id]');
                if (!b) return;
                setStation(target, b.dataset.id, b.dataset.name);
                closePicker();
            });

            // أقرب محطة (جوه المنتقي أو من الزر تحت)
            const nearMsg = document.getElementById('near-msg');
            function doNear(setTarget) {
                if (!navigator.geolocation || !NEAR.length) { nearMsg.textContent = 'تحديد الموقع مش متاح دلوقتي.'; nearMsg.hidden = false; return; }
                const toR = (x) => x * Math.PI / 180;
                const dist = (la1, lo1, la2, lo2) => { const dLa = toR(la2 - la1), dLo = toR(lo2 - lo1); const a = Math.sin(dLa / 2) ** 2 + Math.cos(toR(la1)) * Math.cos(toR(la2)) * Math.sin(dLo / 2) ** 2; return Math.asin(Math.sqrt(a)); };
                navigator.geolocation.getCurrentPosition((pos) => {
                    const { latitude: la, longitude: lo } = pos.coords;
                    let best = null;
                    NEAR.forEach(s => { const d = dist(la, lo, +s.lat, +s.lng); if (!best || d < best.d) best = { ...s, d }; });
                    if (best) setStation(setTarget, best.id, best.name);
                    closePicker();
                }, () => { nearMsg.textContent = 'تعذّر تحديد مكانك، حاول تاني.'; nearMsg.hidden = false; closePicker(); },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
            }
            document.getElementById('picker-near').addEventListener('click', () => doNear(target));
            document.getElementById('near-btn').addEventListener('click', () => { nearMsg.hidden = true; doNear('from'); });

            // عكس
            document.getElementById('swap-btn').addEventListener('click', () => {
                const fv = fromHidden.value, ft = fromDisp.textContent, fClassed = !fromDisp.classList.contains('text-slate-400');
                const tv = toHidden.value, tt = toDisp.textContent, tClassed = !toDisp.classList.contains('text-slate-400');
                if (tClassed) setStation('from', tv, tt); else { fromHidden.value = ''; fromDisp.textContent = PH; fromDisp.classList.add('text-slate-400'); fromDisp.classList.remove('text-slate-800'); }
                if (fClassed) setStation('to', fv, ft); else { toHidden.value = ''; toDisp.textContent = PH; toDisp.classList.add('text-slate-400'); toDisp.classList.remove('text-slate-800'); }
            });

            // التاريخ + الكبسات
            const isoAdd = (n) => { const d = new Date(TODAY + 'T00:00'); d.setDate(d.getDate() + n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
            const QUICK = { [isoAdd(0)]: 'النهاردة', [isoAdd(1)]: 'بكرة', [isoAdd(2)]: 'بعد بكرة' };
            const fmtDate = (iso) => QUICK[iso] || (() => { try { return new Intl.DateTimeFormat('ar-EG', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(iso + 'T00:00')); } catch (e) { return iso; } })();
            const DAYS = [[0, 'النهاردة'], [1, 'بكرة'], [2, 'بعد بكرة']];
            function paintDays() {
                daysBox.innerHTML = DAYS.map(([n, lbl]) => {
                    const iso = isoAdd(n), on = dateInput.value === iso;
                    return `<button type="button" data-iso="${iso}" class="rounded-xl px-3 py-1.5 text-xs font-bold transition ${on ? 'bg-rail-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">${lbl}</button>`;
                }).join('');
                dateDisp.textContent = fmtDate(dateInput.value);
            }
            daysBox.addEventListener('click', (e) => { const b = e.target.closest('[data-iso]'); if (b) { dateInput.value = b.dataset.iso; paintDays(); } });
            dateInput.addEventListener('change', paintDays);
            paintDays();

            // تحقق
            form.addEventListener('submit', (e) => {
                err.hidden = true;
                if (!fromHidden.value || !toHidden.value) { e.preventDefault(); err.textContent = 'اختار محطة القيام ومحطة النزول.'; err.hidden = false; return; }
                if (fromHidden.value === toHidden.value) { e.preventDefault(); err.textContent = 'محطة القيام والنزول مش ممكن يكونوا نفس المحطة.'; err.hidden = false; }
            });

            // رقم القطر
            const numForm = document.getElementById('num-form');
            document.getElementById('num-toggle').addEventListener('click', () => { numForm.hidden = !numForm.hidden; if (!numForm.hidden) numForm.querySelector('input').focus(); });

            // الخدمات السريعة
            document.querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => {
                try { navigator.vibrate?.(10); } catch (e) {}
                if (b.dataset.quick === 'near') { box.scrollIntoView({ behavior: 'smooth', block: 'start' }); nearMsg.hidden = true; doNear('from'); }
                else openPicker('from');
            }));
        })();
    </script>
</section>
