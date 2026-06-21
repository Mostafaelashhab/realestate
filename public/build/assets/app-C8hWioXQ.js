window.EnrLive=(()=>{let e=e=>`<svg viewBox="0 0 24 24" class="w-4 h-4 inline-block align-text-bottom" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${e}</svg>`,t={clock:e(`<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>`),ruler:e(`<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.4 2.4 0 0 1 0-3.4l2.6-2.6a2.4 2.4 0 0 1 3.4 0z"/><path d="m14.5 12.5 2-2m-4-1 2-2m-4-1 2-2m-4-1 2-2"/>`),seat:e(`<path d="M5 11a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2"/><path d="M5 11V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"/><path d="M5 19v2m14-2v2"/>`),station:e(`<path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-5h6v5M9 11h6"/>`)},n=e=>{if(!e||e.length<16)return`—`;let[t,n]=e.substr(11,5).split(`:`).map(Number),r=t<12?`ص`:`م`;return t=t%12||12,`${t}:${String(n).padStart(2,`0`)} ${r}`},r=e=>(e/100).toLocaleString(`ar-EG`)+` ج.م`,i=e=>!e||!e.length?``:`
            <div class="flex flex-wrap gap-1 mt-2">${e.slice().sort((e,t)=>e.number-+t.number).map(e=>{let t=e.available&&!e.sold&&!e.locked,n=t?`bg-emerald-500 text-white`:`bg-slate-100 text-slate-400`,r=Math.round((e.cost||0)/100);return`<div class="flex flex-col items-center justify-center min-w-[2.6rem] px-1.5 py-1 rounded-lg ${n}" title="${t?`متاح`:`محجوز`}">
                    <span class="text-[11px] font-bold leading-none ${t?``:`line-through`}">${e.number}</span>
                    <span class="text-[9px] leading-none mt-0.5 opacity-90">${r} ج</span>
                </div>`}).join(``)}</div>
            <div class="flex gap-3 mt-2 text-[10px] text-slate-400">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> متاح</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-100 inline-block"></span> محجوز</span>
            </div>`,a=e=>{let a=((e.train||{}).servicePoints||[]).map(e=>{let t=e.coachClass||{},n=t.localizationMap?.ar||t.shortName||`درجة`,a=(e.availableSeats||[]).length;return`
                <div class="bg-white rounded-lg border border-slate-200 p-3 mb-2">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="font-medium">عربة ${e.name??`—`} · <span class="text-rail-700">${n}</span></div>
                        <div class="text-sm">${r(e.cost)} · <span class="${a?`text-emerald-700`:`text-red-600`}">${a} مقعد متاح</span></div>
                    </div>
                    ${i(e.places)}
                </div>`}).join(``);return`
            <div class="mb-4">
                <div class="flex items-center gap-4 flex-wrap text-sm mb-3 bg-white rounded-lg border border-slate-200 p-3">
                    <span class="font-bold text-lg">${n(e.fromDate)}</span>
                    <span class="text-slate-400">←</span>
                    <span class="font-bold text-lg">${n(e.finishDate)}</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${t.clock} ${e.duration} د</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${t.ruler} ${e.totalDistance} كم</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${t.seat} ${e.availableSeats} مقعد متاح</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">${t.station} ${(e.route||[]).length} محطة</span>
                </div>
                ${a||`<p class="text-sm text-slate-400">لا توجد تفاصيل عربات.</p>`}
            </div>`};return{fmtTime:n,egp:r,render:e=>(Array.isArray(e)?e:[]).filter(e=>e.steps&&e.steps[0]).map(e=>a(e.steps[0])).join(``)||`<p class="text-sm text-slate-400">لا توجد رحلات لهذا اليوم.</p>`,totalSeats:e=>(Array.isArray(e)?e:[]).filter(e=>e.steps&&e.steps[0]).reduce((e,t)=>e+(t.steps[0].availableSeats||0),0),buildUrl:(e,{from:t,to:n,number:r,date:i})=>`${e}?from=${t}&to=${n}&transfers=false&with_reservations=true&without_reservations=false&skip_places_information=false&departureDate=${i}&project=enr${r?`&trainNumber=`+r:``}`}})();