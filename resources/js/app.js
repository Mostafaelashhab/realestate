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
        <div class="flex justify-center gap-4 mt-2 text-[10px] text-slate-400">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> متاح</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-200 inline-block"></span> محجوز</span>
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

    // مخطط العربة بالمواقع الحقيقية (topLeft.x/y) — يعرض ٢ أو ٤... في الصف حسب العربة تلقائيًا.
    const seatPlan = (pts) => {
        const xC = clusterAxis(pts.map(p => p.topLeft.x), 20); // أعمدة على طول العربة
        const yC = clusterAxis(pts.map(p => p.topLeft.y), 20); // مقاعد عرضية (٢ / ٤ / ٥...)
        const cols = xC.centers.length, rows = yC.centers.length;

        // الممر = أكبر فجوة بين الصفوف العرضية (لو واضحة).
        let aisleAfter = -1, maxGap = 0;
        for (let i = 1; i < rows; i++) {
            const g = yC.centers[i] - yC.centers[i - 1];
            if (g > maxGap) { maxGap = g; aisleAfter = i - 1; }
        }
        const avgGap = rows > 1 ? (yC.centers[rows - 1] - yC.centers[0]) / (rows - 1) : 0;
        if (maxGap < avgGap * 1.3) aisleAfter = -1;

        const SEAT = 28, GX = 6, GY = 6, AISLE = 16;
        const colW = SEAT + GX, rowH = SEAT + GY;

        const seats = pts.map(p => {
            const isSeat = (p.params?.kind ?? 'seat') === 'seat';
            const ok = isSeat && p.available && !p.sold && !p.locked;
            const left = xC.indexOf(p.topLeft.x) * colW;
            const row = yC.indexOf(p.topLeft.y);
            const top = row * rowH + (aisleAfter >= 0 && row > aisleAfter ? AISLE : 0);
            const price = Math.round((p.cost || 0) / 100);
            const cushion = !isSeat ? 'bg-slate-100 text-slate-300'
                : ok ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400';
            const backColor = ok ? 'bg-emerald-700' : 'bg-slate-300';
            const backSide = p.params?.dir === 'right' ? 'right-0.5' : 'left-0.5';
            const title = `${isSeat ? 'مقعد ' : ''}${p.number} — ${ok ? 'متاح' : (isSeat ? 'محجوز' : (p.params?.kind || ''))}${price && isSeat ? ' — ' + price + ' ج.م' : ''}`;
            return `<div class="absolute" style="left:${left}px;top:${top}px;width:${SEAT}px;height:${SEAT}px" title="${title}">
                <div class="relative w-full h-full rounded-md grid place-items-center ${cushion}">
                    ${isSeat ? `<span class="absolute inset-y-1 w-1 rounded ${backColor} ${backSide}"></span>` : ''}
                    <span class="text-[9px] font-bold leading-none ${ok ? '' : (isSeat ? 'line-through' : '')}">${p.number}</span>
                </div>
            </div>`;
        }).join('');

        const W = cols * colW - GX;
        const H = rows * rowH - GY + (aisleAfter >= 0 ? AISLE : 0);

        return `
            <div class="mt-3">
                <div class="overflow-x-auto pb-2">
                    <div class="relative mx-auto bg-slate-50 border-2 border-slate-200 rounded-2xl p-3" style="width:${Math.round(W + 24)}px">
                        <div class="relative" style="width:${Math.round(W)}px;height:${Math.round(H)}px">${seats}</div>
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

    const trip = (step) => {
        const t = step.train || {};
        const coaches = (t.servicePoints || []).map(sp => {
            const cc = sp.coachClass || {};
            const cls = cc.localizationMap?.ar || cc.shortName || 'درجة';
            const avail = (sp.availableSeats || []).length;
            return `
                <div class="bg-white rounded-lg border border-slate-200 p-3 mb-2">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="font-medium">عربة ${sp.name ?? '—'} · <span class="text-rail-700">${cls}</span></div>
                        <div class="text-sm">${egp(sp.cost)} · <span class="${avail ? 'text-emerald-700' : 'text-red-600'}">${avail} مقعد متاح</span></div>
                    </div>
                    ${seatMap(sp.places)}
                </div>`;
        }).join('');

        return `
            <div class="mb-4">
                <div class="flex items-center gap-4 flex-wrap text-sm mb-3 bg-white rounded-lg border border-slate-200 p-3">
                    <span class="font-bold text-lg">${fmtTime(step.fromDate)}</span>
                    <span class="text-slate-400">←</span>
                    <span class="font-bold text-lg">${fmtTime(step.finishDate)}</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${ICN.clock} ${step.duration} د</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${ICN.ruler} ${step.totalDistance} كم</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${ICN.seat} ${step.availableSeats} مقعد متاح</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${ICN.station} ${(step.route || []).length} محطة</span>
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

    return { fmtTime, egp, render, totalSeats, buildUrl };
})();
