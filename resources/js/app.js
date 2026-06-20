// أدوات عرض بيانات الرحلات الرسمية (يستعملها صفحة المزامنة وصفحة القطار).
window.EnrLive = (() => {
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
                const cls = ok ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400 line-through';
                return `<span class="inline-flex items-center justify-center w-7 h-7 rounded text-[10px] ${cls}" title="${ok ? 'متاح' : 'محجوز'}">${p.number}</span>`;
            }).join('');
        return `<div class="flex flex-wrap gap-1 mt-2">${chips}</div>`;
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
                    <span class="text-slate-500">⏱ ${step.duration} د</span>
                    <span class="text-slate-500">📏 ${step.totalDistance} كم</span>
                    <span class="text-slate-500">🪑 ${step.availableSeats} مقعد متاح</span>
                    <span class="text-slate-500">🚉 ${(step.route || []).length} محطة</span>
                </div>
                ${coaches || '<p class="text-sm text-slate-400">لا توجد تفاصيل عربات.</p>'}
            </div>`;
    };

    const render = (data) => (Array.isArray(data) ? data : [])
        .filter(item => item.steps && item.steps[0])
        .map(item => trip(item.steps[0]))
        .join('') || '<p class="text-sm text-slate-400">لا توجد رحلات لهذا اليوم.</p>';

    // يبني رابط بحث الهيئة الرسمي
    const buildUrl = (base, { from, to, number, date }) =>
        `${base}?from=${from}&to=${to}&transfers=false&with_reservations=true`
        + `&without_reservations=false&skip_places_information=false`
        + `&departureDate=${date}&project=enr${number ? '&trainNumber=' + number : ''}`;

    return { fmtTime, egp, render, buildUrl };
})();
