window.EnrLive=(()=>{let e=e=>{if(!e||e.length<16)return`—`;let[t,n]=e.substr(11,5).split(`:`).map(Number),r=t<12?`ص`:`م`;return t=t%12||12,`${t}:${String(n).padStart(2,`0`)} ${r}`},t=e=>(e/100).toLocaleString(`ar-EG`)+` ج.م`,n=e=>!e||!e.length?``:`
            <div class="flex flex-wrap gap-1 mt-2">${e.slice().sort((e,t)=>e.number-+t.number).map(e=>{let t=e.available&&!e.sold&&!e.locked,n=t?`bg-emerald-500 text-white`:`bg-slate-100 text-slate-400`,r=Math.round((e.cost||0)/100);return`<div class="flex flex-col items-center justify-center min-w-[2.6rem] px-1.5 py-1 rounded-lg ${n}" title="${t?`متاح`:`محجوز`}">
                    <span class="text-[11px] font-bold leading-none ${t?``:`line-through`}">${e.number}</span>
                    <span class="text-[9px] leading-none mt-0.5 opacity-90">${r} ج</span>
                </div>`}).join(``)}</div>
            <div class="flex gap-3 mt-2 text-[10px] text-slate-400">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> متاح</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-100 inline-block"></span> محجوز</span>
            </div>`,r=r=>{let i=((r.train||{}).servicePoints||[]).map(e=>{let r=e.coachClass||{},i=r.localizationMap?.ar||r.shortName||`درجة`,a=(e.availableSeats||[]).length;return`
                <div class="bg-white rounded-lg border border-slate-200 p-3 mb-2">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="font-medium">عربة ${e.name??`—`} · <span class="text-rail-700">${i}</span></div>
                        <div class="text-sm">${t(e.cost)} · <span class="${a?`text-emerald-700`:`text-red-600`}">${a} مقعد متاح</span></div>
                    </div>
                    ${n(e.places)}
                </div>`}).join(``);return`
            <div class="mb-4">
                <div class="flex items-center gap-4 flex-wrap text-sm mb-3 bg-white rounded-lg border border-slate-200 p-3">
                    <span class="font-bold text-lg">${e(r.fromDate)}</span>
                    <span class="text-slate-400">←</span>
                    <span class="font-bold text-lg">${e(r.finishDate)}</span>
                    <span class="text-slate-500">⏱ ${r.duration} د</span>
                    <span class="text-slate-500">📏 ${r.totalDistance} كم</span>
                    <span class="text-slate-500">🪑 ${r.availableSeats} مقعد متاح</span>
                    <span class="text-slate-500">🚉 ${(r.route||[]).length} محطة</span>
                </div>
                ${i||`<p class="text-sm text-slate-400">لا توجد تفاصيل عربات.</p>`}
            </div>`};return{fmtTime:e,egp:t,render:e=>(Array.isArray(e)?e:[]).filter(e=>e.steps&&e.steps[0]).map(e=>r(e.steps[0])).join(``)||`<p class="text-sm text-slate-400">لا توجد رحلات لهذا اليوم.</p>`,buildUrl:(e,{from:t,to:n,number:r,date:i})=>`${e}?from=${t}&to=${n}&transfers=false&with_reservations=true&without_reservations=false&skip_places_information=false&departureDate=${i}&project=enr${r?`&trainNumber=`+r:``}`}})();