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

    const seatMap = (places) => {
        if (!places || !places.length) return '';
        const chips = places
            .slice()
            .sort((a, b) => (+a.number) - (+b.number))
            .map(p => {
                const ok = p.available && !p.sold && !p.locked;
                const cls = ok ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400';
                const price = Math.round((p.cost || 0) / 100);
                return `<div class="flex flex-col items-center justify-center min-w-[2.6rem] px-1.5 py-1 rounded-lg ${cls}" title="${ok ? 'متاح' : 'محجوز'}">
                    <span class="text-[11px] font-bold leading-none ${ok ? '' : 'line-through'}">${p.number}</span>
                    <span class="text-[9px] leading-none mt-0.5 opacity-90">${price} ج</span>
                </div>`;
            }).join('');
        return `
            <div class="flex flex-wrap gap-1 mt-2">${chips}</div>
            <div class="flex gap-3 mt-2 text-[10px] text-slate-400">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> متاح</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-100 inline-block"></span> محجوز</span>
            </div>`;
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
