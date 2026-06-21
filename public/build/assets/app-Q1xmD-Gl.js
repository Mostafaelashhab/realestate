window.EnrLive=(()=>{let e=e=>`<svg viewBox="0 0 24 24" class="w-4 h-4 inline-block align-text-bottom" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${e}</svg>`,t={clock:e(`<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>`),ruler:e(`<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.4 2.4 0 0 1 0-3.4l2.6-2.6a2.4 2.4 0 0 1 3.4 0z"/><path d="m14.5 12.5 2-2m-4-1 2-2m-4-1 2-2m-4-1 2-2"/>`),seat:e(`<path d="M5 11a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2"/><path d="M5 11V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"/><path d="M5 19v2m14-2v2"/>`),station:e(`<path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-5h6v5M9 11h6"/>`)},n=e=>{if(!e||e.length<16)return`—`;let[t,n]=e.substr(11,5).split(`:`).map(Number),r=t<12?`ص`:`م`;return t=t%12||12,`${t}:${String(n).padStart(2,`0`)} ${r}`},r=e=>(e/100).toLocaleString(`ar-EG`)+` ج.م`,i=`
        <div class="flex justify-center gap-4 mt-2 text-[10px] text-slate-400">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> متاح</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-200 inline-block"></span> محجوز</span>
        </div>`,a=(e,t)=>{let n=[...new Set(e)].sort((e,t)=>e-t),r=[],i=[n[0]];for(let e=1;e<n.length;e++)n[e]-n[e-1]<=t?i.push(n[e]):(r.push(i.reduce((e,t)=>e+t,0)/i.length),i=[n[e]]);return r.push(i.reduce((e,t)=>e+t,0)/i.length),{centers:r,indexOf:e=>{let t=0,n=1/0;return r.forEach((r,i)=>{let a=Math.abs(r-e);a<n&&(n=a,t=i)}),t}}},o=e=>{let t=a(e.map(e=>e.topLeft.x),20),n=a(e.map(e=>e.topLeft.y),20),r=t.centers.length,o=n.centers.length,s=-1,c=0;for(let e=1;e<o;e++){let t=n.centers[e]-n.centers[e-1];t>c&&(c=t,s=e-1)}let l=o>1?(n.centers[o-1]-n.centers[0])/(o-1):0;c<l*1.3&&(s=-1);let u=e.map(e=>{let r=(e.params?.kind??`seat`)===`seat`,i=r&&e.available&&!e.sold&&!e.locked,a=t.indexOf(e.topLeft.x)*34,o=n.indexOf(e.topLeft.y),c=o*34+(s>=0&&o>s?16:0),l=Math.round((e.cost||0)/100),u=r?i?`bg-emerald-500 text-white`:`bg-slate-200 text-slate-400`:`bg-slate-100 text-slate-300`,d=i?`bg-emerald-700`:`bg-slate-300`,f=e.params?.dir===`right`?`right-0.5`:`left-0.5`;return`<div class="absolute" style="left:${a}px;top:${c}px;width:28px;height:28px" title="${`${r?`مقعد `:``}${e.number} — ${i?`متاح`:r?`محجوز`:e.params?.kind||``}${l&&r?` — `+l+` ج.م`:``}`}">
                <div class="relative w-full h-full rounded-md grid place-items-center ${u}">
                    ${r?`<span class="absolute inset-y-1 w-1 rounded ${d} ${f}"></span>`:``}
                    <span class="text-[9px] font-bold leading-none ${i?``:r?`line-through`:``}">${e.number}</span>
                </div>
            </div>`}).join(``),d=r*34-6,f=o*34-6+(s>=0?16:0);return`
            <div class="mt-3">
                <div class="overflow-x-auto pb-2">
                    <div class="relative mx-auto bg-slate-50 border-2 border-slate-200 rounded-2xl p-3" style="width:${Math.round(d+24)}px">
                        <div class="relative" style="width:${Math.round(d)}px;height:${Math.round(f)}px">${u}</div>
                    </div>
                </div>
                ${i}
            </div>`},s=e=>{let t=e.available&&!e.sold&&!e.locked,n=Math.round((e.cost||0)/100);return`<div class="flex flex-col items-center justify-center w-10 rounded-lg py-1 ${t?`bg-emerald-500 text-white`:`bg-slate-200 text-slate-400`}" title="مقعد ${e.number} — ${t?`متاح`:`محجوز`}${n?` — `+n+` ج`:``}">
            <span class="text-[10px] font-bold leading-none ${t?``:`line-through`}">${e.number}</span>
        </div>`},c=e=>{if(!e||!e.length)return``;let t=e.filter(e=>e.topLeft&&typeof e.topLeft.x==`number`&&typeof e.topLeft.y==`number`);return t.length?o(t):`<div class="mt-3"><div class="flex flex-wrap gap-1.5 justify-center">${e.slice().sort((e,t)=>e.number-+t.number).map(s).join(``)}</div>${i}</div>`},l=e=>{let i=((e.train||{}).servicePoints||[]).map(e=>{let t=e.coachClass||{},n=t.localizationMap?.ar||t.shortName||`درجة`,i=(e.availableSeats||[]).length;return`
                <div class="bg-white rounded-lg border border-slate-200 p-3 mb-2">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="font-medium">عربة ${e.name??`—`} · <span class="text-rail-700">${n}</span></div>
                        <div class="text-sm">${r(e.cost)} · <span class="${i?`text-emerald-700`:`text-red-600`}">${i} مقعد متاح</span></div>
                    </div>
                    ${c(e.places)}
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
                ${i||`<p class="text-sm text-slate-400">لا توجد تفاصيل عربات.</p>`}
            </div>`};return{fmtTime:n,egp:r,render:e=>(Array.isArray(e)?e:[]).filter(e=>e.steps&&e.steps[0]).map(e=>l(e.steps[0])).join(``)||`<p class="text-sm text-slate-400">لا توجد رحلات لهذا اليوم.</p>`,totalSeats:e=>(Array.isArray(e)?e:[]).filter(e=>e.steps&&e.steps[0]).reduce((e,t)=>e+(t.steps[0].availableSeats||0),0),buildUrl:(e,{from:t,to:n,number:r,date:i})=>`${e}?from=${t}&to=${n}&transfers=false&with_reservations=true&without_reservations=false&skip_places_information=false&departureDate=${i}&project=enr${r?`&trainNumber=`+r:``}`}})();