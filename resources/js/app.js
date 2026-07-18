// Alpine.js — تفاعلية خفيفة (أكشنز بدون إعادة تحميل).
import Alpine from 'alpinejs';
window.Alpine = Alpine;

const csrfToken = () => document.querySelector('meta[name=csrf-token]')?.content;

// إرسال POST كـ JSON مع معالجة موحّدة للأخطاء (419 لوجين · 429 تهدئة · باقي الأخطاء).
// يرجّع { ok, data, msg } — أو يعيد التوجيه للوجين عند انتهاء الجلسة.
async function postJson(url, body, loginUrl) {
    let r;
    try {
        r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
    } catch (e) {
        return { ok: false, data: null, msg: 'مفيش نت — راجع اتصالك وحاول تاني.' };
    }
    // انتهت الجلسة / مش مسجّل دخول → رجّعه لصفحة الدخول.
    // (بيرجع 401/419، أو 302 بيتبعه fetch لصفحة اللوجين فيبقى redirected أو رد HTML مش JSON.)
    if (r.status === 401 || r.status === 419 || r.redirected || !(r.headers.get('content-type') || '').includes('json')) {
        if (loginUrl) location.href = loginUrl;
        return { ok: false, data: null, redirect: true };
    }
    if (r.status === 429) return { ok: false, data: null, msg: 'بعتّ طلبات كتير بسرعة — استنى دقيقة وحاول تاني.' };
    let d = null;
    try { d = await r.json(); } catch (e) { /* رد مش JSON */ }
    if (!r.ok || !d) return { ok: false, data: d, msg: (d && d.message) || 'حصل خطأ، جرّب تاني.' };
    return { ok: !!d.ok, data: d, msg: d.message || '' };
}

// بلاغ حالة القطر (في الموعد/متأخر/اتلغى) بدون reload.
Alpine.data('statusReport', () => ({
    loading: false, msg: '', ok: false,
    async send(status) {
        if (this.loading) return;
        this.loading = true; this.msg = '';
        const res = await postJson(this.$el.dataset.url, { status }, this.$el.dataset.login);
        this.loading = false;
        if (res.redirect) return;
        this.ok = res.ok;
        this.msg = res.msg || (res.ok ? 'اتسجّل، شكرًا!' : 'حصل خطأ، جرّب تاني.');
    },
}));

// تقييم القطر (نجوم + تعليق) بدون reload.
Alpine.data('reviewForm', (rating = 0, comment = '', avg = 0, count = 0, hadReview = false) => ({
    rating, comment, avg, count,
    loading: false, msg: '', ok: false,
    submitted: false, mine: null, hadReview,
    setRating(n) { this.rating = n; this.msg = ''; },
    async submit() {
        if (this.loading) return;
        if (!this.rating) { this.ok = false; this.msg = 'اختار تقييمك بالنجوم الأول.'; return; }
        this.loading = true; this.msg = '';
        const res = await postJson(this.$el.dataset.url, { rating: this.rating, comment: this.comment }, this.$el.dataset.login);
        this.loading = false;
        if (res.redirect) return;
        this.ok = res.ok;
        this.msg = res.msg || (res.ok ? 'اتسجّل، شكرًا!' : 'حصل خطأ، جرّب تاني.');
        if (res.ok && res.data) { this.avg = res.data.avg; this.count = res.data.count; this.mine = res.data.review; this.submitted = true; }
    },
    // أول حرف من اسم صاحب الرأي (للأفاتار).
    get mineInitial() { return this.mine ? (this.mine.user || 'أنا').substring(0, 1) : ''; },
    // عدد النجوم الممتلئة لقيمة معيّنة.
    filled(n) { return this.rating >= n; },
}));

Alpine.start();

// أدوات عرض بيانات الرحلات الرسمية (يستعملها صفحة المزامنة وصفحة القطار).
window.EnrLive = (() => {
    // أيقونات SVG داخلية بدل الإيموجي.
    const svg = (paths) => `<svg viewBox="0 0 24 24" class="w-4 h-4 inline-block align-text-bottom" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;
    const ICN = {
        clock: svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
        ruler: svg('<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.4 2.4 0 0 1 0-3.4l2.6-2.6a2.4 2.4 0 0 1 3.4 0z"/><path d="m14.5 12.5 2-2m-4-1 2-2m-4-1 2-2m-4-1 2-2"/>'),
        seat: svg('<path d="M5 11a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2"/><path d="M5 11V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"/><path d="M5 19v2m14-2v2"/>'),
        station: svg('<path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-5h6v5M9 11h6"/>'),
    };

    const fmtTime = (iso) => {
        if (!iso || iso.length < 16) return '—';
        let [h, m] = iso.substr(11, 5).split(':').map(Number);
        const mer = h < 12 ? 'ص' : 'م';
        h = h % 12 || 12;
        return `${h}:${String(m).padStart(2, '0')} ${mer}`;
    };

    const egp = (piasters) => (piasters / 100).toLocaleString('ar-EG') + ' ج.م';

    const seatLegend = `
        <div class="flex justify-center flex-wrap gap-3 mt-3 text-[11px] text-slate-500">
            <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-rail-600 inline-block"></span> متاح</span>
            <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-slate-200 inline-block"></span> محجوز</span>
            <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded bg-amber-400 inline-block"></span> مختار</span>
        </div>`;

    // يجمّع إحداثيات متقاربة في خانات (يتجاهل ضوضاء ١-٢ بكسل) ويرجّع المراكز ودالة الفهرسة.
    const clusterAxis = (values, tol) => {
        const sorted = [...new Set(values)].sort((a, b) => a - b);
        const centers = [];
        let group = [sorted[0]];
        for (let i = 1; i < sorted.length; i++) {
            if (sorted[i] - sorted[i - 1] <= tol) group.push(sorted[i]);
            else { centers.push(group.reduce((s, v) => s + v, 0) / group.length); group = [sorted[i]]; }
        }
        centers.push(group.reduce((s, v) => s + v, 0) / group.length);
        const indexOf = (v) => {
            let best = 0, bd = Infinity;
            centers.forEach((c, i) => { const d = Math.abs(c - v); if (d < bd) { bd = d; best = i; } });
            return best;
        };
        return { centers, indexOf };
    };

    // مخطط العربة كشبكة أنيقة: صفوف مرقّمة + أعمدة A/B · ممر · C/D، وكراسي قابلة للاختيار.
    const seatPlan = (pts) => {
        const lenAxis = clusterAxis(pts.map(p => p.topLeft.x), 20); // طول العربة → صفوف مرقّمة
        const acrossAxis = clusterAxis(pts.map(p => p.topLeft.y), 20); // العرض → أعمدة (A/B/C/D)
        const numRows = lenAxis.centers.length, numCols = acrossAxis.centers.length;

        // الممر = أكبر فجوة بين الأعمدة العرضية (لو واضحة).
        let aisleAfter = -1, maxGap = 0;
        for (let i = 1; i < numCols; i++) {
            const g = acrossAxis.centers[i] - acrossAxis.centers[i - 1];
            if (g > maxGap) { maxGap = g; aisleAfter = i - 1; }
        }
        const avgGap = numCols > 1 ? (acrossAxis.centers[numCols - 1] - acrossAxis.centers[0]) / (numCols - 1) : 0;
        if (maxGap < avgGap * 1.3) aisleAfter = -1;

        // خريطة المقاعد حسب (صف، عمود).
        const grid = {};
        pts.forEach(p => { grid[lenAxis.indexOf(p.topLeft.x) + '-' + acrossAxis.indexOf(p.topLeft.y)] = p; });

        const letters = 'ABCDEFGH';
        const cell = (p) => {
            if (!p) return '<div></div>';
            const isSeat = (p.params?.kind ?? 'seat') === 'seat';
            if (!isSeat) return `<div class="grid place-items-center text-slate-300 text-xs" title="${p.params?.kind || ''}">◦</div>`;
            const ok = p.available && !p.sold && !p.locked;
            const price = Math.round((p.cost || 0) / 100);
            const title = `مقعد ${p.number} — ${ok ? 'متاح' : 'محجوز'}${price ? ' — ' + price + ' ج.م' : ''}`;
            // رقم الكرسي مكتوب على الكرسي نفسه (بدل أيقونة عامة) — يظهر على الموبايل من غير hover.
            const cls = ok
                ? 'bg-rail-50 text-rail-700 ring-1 ring-rail-200 hover:bg-rail-100'
                : 'bg-slate-100 text-slate-300 ring-1 ring-slate-200 line-through';
            return `<button type="button" ${ok ? `data-seat="${p.number}"` : 'disabled'} title="${title}"
                class="seat w-9 h-9 rounded-lg grid place-items-center text-[11px] font-extrabold tabular-nums leading-none ${cls} transition">${p.number}</button>`;
        };

        // قالب الأعمدة: رقم الصف + الأعمدة + عمود الممر.
        let tmpl = '1.75rem';
        for (let c = 0; c < numCols; c++) { tmpl += ' 2.25rem'; if (c === aisleAfter) tmpl += ' 1rem'; }

        // رأس الأعمدة.
        let header = '<div></div>';
        for (let c = 0; c < numCols; c++) {
            header += `<div class="text-center text-[11px] font-bold text-slate-400">${letters[c] || (c + 1)}</div>`;
            if (c === aisleAfter) header += '<div class="text-center text-[9px] text-slate-300 leading-tight self-center">ممر</div>';
        }

        // الصفوف.
        let body = '';
        for (let r = 0; r < numRows; r++) {
            body += `<div class="grid place-items-center text-[11px] font-bold text-slate-400">${r + 1}</div>`;
            for (let c = 0; c < numCols; c++) {
                body += cell(grid[r + '-' + c]);
                if (c === aisleAfter) body += '<div></div>';
            }
        }

        return `
            <div class="mt-3" data-seats>
                <div class="overflow-x-auto pb-1">
                    <div class="inline-grid gap-1.5 mx-auto items-center bg-slate-50 border border-slate-200 rounded-2xl p-3" style="grid-template-columns:${tmpl}">
                        ${header}${body}
                    </div>
                </div>
                ${seatLegend}
            </div>`;
    };

    // مقعد بسيط (احتياطي لو الهيئة ما رجّعتش إحداثيات).
    const seatChip = (p) => {
        const ok = p.available && !p.sold && !p.locked;
        const price = Math.round((p.cost || 0) / 100);
        const cushion = ok ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400';
        return `<div class="flex flex-col items-center justify-center w-10 rounded-lg py-1 ${cushion}" title="مقعد ${p.number} — ${ok ? 'متاح' : 'محجوز'}${price ? ' — ' + price + ' ج' : ''}">
            <span class="text-[10px] font-bold leading-none ${ok ? '' : 'line-through'}">${p.number}</span>
        </div>`;
    };

    const seatMap = (places) => {
        if (!places || !places.length) return '';
        const positioned = places.filter(p => p.topLeft && typeof p.topLeft.x === 'number' && typeof p.topLeft.y === 'number');

        if (positioned.length) return seatPlan(positioned);

        // احتياطي: شبكة بسيطة بترتيب الأرقام.
        const chips = places.slice().sort((a, b) => (+a.number) - (+b.number)).map(seatChip).join('');
        return `<div class="mt-3"><div class="flex flex-wrap gap-1.5 justify-center">${chips}</div>${seatLegend}</div>`;
    };

    // شريحة إحصائية صغيرة
    const stat = (icon, text) => `<span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 rounded-full px-2.5 py-1 whitespace-nowrap">${icon} ${text}</span>`;

    // كارت عربة واحدة
    const coachCard = (sp) => {
        const cc = sp.coachClass || {};
        const cls = cc.localizationMap?.ar || cc.shortName || 'درجة';
        const avail = (sp.availableSeats || []).length;
        const availBadge = avail
            ? `<span class="text-xs font-bold bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1 whitespace-nowrap">${avail} متاح</span>`
            : `<span class="text-xs font-bold bg-red-50 text-red-600 rounded-full px-2.5 py-1 whitespace-nowrap">مكتمل</span>`;
        return `
            <div class="bg-white rounded-2xl border border-slate-200 p-3.5 mb-2.5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs font-bold bg-rail-50 text-rail-700 rounded-lg px-2 py-1 whitespace-nowrap">عربة ${sp.name ?? '—'}</span>
                        <span class="text-sm font-medium text-slate-700 truncate">${cls}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-sm font-extrabold text-rail-800 whitespace-nowrap">${egp(sp.cost)}</span>
                        ${availBadge}
                    </div>
                </div>
                ${seatMap(sp.places)}
            </div>`;
    };

    const trip = (step) => {
        const t = step.train || {};
        const coaches = (t.servicePoints || []).map(coachCard).join('');
        const seats = step.availableSeats || 0;

        return `
            <div class="mb-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-3">
                    <div class="flex items-center gap-3">
                        <div class="text-center min-w-0">
                            <div class="text-xl font-extrabold leading-none">${fmtTime(step.fromDate)}</div>
                            <div class="text-[11px] text-slate-400 mt-1">قيام</div>
                        </div>
                        <div class="flex-1 flex flex-col items-center px-1">
                            <span class="text-[11px] text-slate-400 mb-1">${step.duration} د</span>
                            <div class="w-full flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rail-600 shrink-0"></span>
                                <span class="flex-1 border-t border-dashed border-slate-300"></span>
                                <span class="text-rail-500 shrink-0">${ICN.seat}</span>
                                <span class="flex-1 border-t border-dashed border-slate-300"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                            </div>
                        </div>
                        <div class="text-center min-w-0">
                            <div class="text-xl font-extrabold leading-none">${fmtTime(step.finishDate)}</div>
                            <div class="text-[11px] text-slate-400 mt-1">وصول</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5 justify-center mt-3 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 whitespace-nowrap font-bold ${seats ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}">${ICN.seat} ${seats} مقعد متاح</span>
                        ${stat(ICN.ruler, `${step.totalDistance} كم`)}
                        ${stat(ICN.station, `${(step.route || []).length} محطة`)}
                    </div>
                </div>
                ${coaches || '<p class="text-sm text-slate-400">لا توجد تفاصيل عربات.</p>'}
            </div>`;
    };

    const render = (data) => (Array.isArray(data) ? data : [])
        .filter(item => item.steps && item.steps[0])
        .map(item => trip(item.steps[0]))
        .join('') || '<p class="text-sm text-slate-400">لا توجد رحلات لهذا اليوم.</p>';

    // إجمالي المقاعد المتاحة عبر كل الرحلات (لاقتراح محطة أبعد لو صفر).
    const totalSeats = (data) => (Array.isArray(data) ? data : [])
        .filter(item => item.steps && item.steps[0])
        .reduce((sum, item) => sum + (item.steps[0].availableSeats || 0), 0);

    // يبني رابط بحث الهيئة الرسمي
    const buildUrl = (base, { from, to, number, date }) =>
        `${base}?from=${from}&to=${to}&transfers=false&with_reservations=true`
        + `&without_reservations=false&skip_places_information=false`
        + `&departureDate=${date}&project=enr${number ? '&trainNumber=' + number : ''}`;

    // اختيار الكرسي بالضغط (مختار واحد لكل عربة) — يشتغل في صفحة القطر وصفحة المزامنة.
    if (typeof document !== 'undefined') {
        document.addEventListener('click', (e) => {
            const seat = e.target.closest('.seat[data-seat]');
            if (!seat) return;
            const wrap = seat.closest('[data-seats]');
            if (wrap) wrap.querySelectorAll('.seat-selected').forEach(s => { if (s !== seat) s.classList.remove('seat-selected'); });
            seat.classList.toggle('seat-selected');
            try { navigator.vibrate?.(12); } catch (e) {} // إحساس لمسي عند اختيار الكرسي
        });
    }

    return { fmtTime, egp, render, totalSeats, buildUrl };
})();
