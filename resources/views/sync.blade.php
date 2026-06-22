@extends('layouts.app')

@section('title', 'مزامنة الأسعار الرسمية')

@section('content')
    <h1 class="text-xl font-bold mb-1">مزامنة البيانات الرسمية</h1>
    <p class="text-sm text-slate-500 mb-4">
        اضغط على أي قطار ليتم النداء من <b>متصفحك</b> لنظام الهيئة وعرض كل بياناته (مواعيد، عربات، درجات، أسعار، مقاعد متاحة)،
        مع حفظ الأسعار والنوع في الموقع. واحد واحد، يدوي.
    </p>

    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex items-center gap-3 flex-wrap">
        <label class="text-sm font-medium">تاريخ الرحلة:</label>
        <input type="date" id="trip-date" value="{{ now()->addDay()->toDateString() }}"
            class="rounded-lg border border-slate-300 px-3 py-1.5">
        <span class="inline-flex items-center gap-1 text-xs text-slate-400">المحمّل أسعاره معلّم بـ <x-icon name="check" class="w-4 h-4 text-emerald-600"/></span>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex items-center gap-3 flex-wrap">
        <button id="sync-all" class="bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold rounded-lg px-4 py-2">مزامنة الكل</button>
        <button id="sync-stop" hidden class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold rounded-lg px-4 py-2">إيقاف</button>
        <label class="text-xs text-slate-600 flex items-center gap-1.5"><input type="checkbox" id="skip-done" checked class="accent-rail-600"> تخطّي المحمّل مسبقًا</label>
        <span id="sync-progress" class="text-sm text-slate-500 ms-auto"></span>
    </div>

    {{-- إضافة قطار جديد برقمه + جلب كل بياناته من الهيئة --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <h2 class="font-bold text-sm mb-1">إضافة قطار برقمه</h2>
        <p class="text-xs text-slate-400 mb-3">اكتب رقم القطار واختر مساره (من → إلى) والتاريخ، ونجيب كل بياناته من الهيئة (مواعيد، درجات، أسعار، مقاعد). لو القطار مش موجود هيتضاف تلقائيًا بمحطاته.</p>
        <div class="flex flex-wrap gap-2 items-center">
            <input id="add-number" inputmode="numeric" placeholder="رقم القطار" class="w-28 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
            <select id="add-from" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm max-w-[10rem]">
                <option value="">من…</option>
                @foreach ($stations as $s)<option value="{{ $s->enr_id }}">{{ $s->name_ar }}</option>@endforeach
            </select>
            <select id="add-to" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm max-w-[10rem]">
                <option value="">إلى…</option>
                @foreach ($stations as $s)<option value="{{ $s->enr_id }}">{{ $s->name_ar }}</option>@endforeach
            </select>
            <input type="date" id="add-date" value="{{ now()->addDay()->toDateString() }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
            <button id="add-btn" class="bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold rounded-lg px-4 py-1.5">جيب وأضف</button>
        </div>
        <div id="add-result" class="text-sm mt-3"></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-slate-500 border-b border-slate-200 text-right">
                    <th class="p-3 font-medium">القطار</th>
                    <th class="p-3 font-medium">النوع الحالي</th>
                    <th class="p-3 font-medium">المسار</th>
                    <th class="p-3 font-medium">الحالة</th>
                    <th class="p-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trains as $t)
                    <tr class="border-b border-slate-50" data-row="{{ $t['id'] }}">
                        <td class="p-3 font-bold">{{ $t['number'] }}</td>
                        <td class="p-3 text-slate-600">{{ $t['type'] }}</td>
                        <td class="p-3 text-slate-500 text-xs">{{ $t['from_name'] }} ← {{ $t['to_name'] }}</td>
                        <td class="p-3 status">
                            @if ($t['has_fares'])
                                <x-icon name="check" class="w-4 h-4 text-emerald-600"/>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($t['from_enr'] && $t['to_enr'])
                                <button class="fetch-btn bg-rail-600 hover:bg-rail-700 text-white text-xs rounded-lg px-3 py-1.5"
                                    data-from="{{ $t['from_enr'] }}" data-to="{{ $t['to_enr'] }}" data-number="{{ $t['number'] }}">
                                    جيب البيانات
                                </button>
                            @else
                                <span class="text-xs text-slate-300">لا يوجد كود محطة</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="detail hidden" data-detail="{{ $t['id'] }}">
                        <td colspan="5" class="p-0">
                            <div class="detail-content bg-slate-50 border-t border-slate-200 p-4"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        const SEARCH_URL = @json($searchUrl);
        const IMPORT_URL = "{{ route('sync.import', $token) }}";
        const CSRF = "{{ csrf_token() }}";
        const CHECK = '<svg viewBox="0 0 24 24" class="w-4 h-4 inline text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

        // مزامنة قطار واحد. showDetail=false في المزامنة الجماعية لتفادي ثقل الـ DOM.
        async function syncRow(btn, showDetail = true) {
            const row = btn.closest('tr');
            const id = row.dataset.row;
            const status = row.querySelector('.status');
            const date = document.getElementById('trip-date').value;
            btn.disabled = true;
            btn.textContent = '...';

            const url = EnrLive.buildUrl(SEARCH_URL, {
                from: btn.dataset.from, to: btn.dataset.to, number: btn.dataset.number, date,
            });

            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                if (!Array.isArray(data) || !data.length) {
                    status.innerHTML = '<span class="text-amber-600 text-xs">لا رحلات</span>';
                    btn.textContent = 'إعادة';
                    btn.disabled = false;
                    return 'empty';
                }

                let saved = 0, savedMsg = '';
                try {
                    const imp = await fetch(IMPORT_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    const result = await imp.json();
                    saved = result.saved ?? 0;
                    savedMsg = `${CHECK} تم حفظ ${saved} سعر و${result.times ?? 0} ميعاد في الموقع`;
                    status.innerHTML = CHECK;
                } catch (e) {
                    savedMsg = 'تعذّر الحفظ، لكن البيانات معروضة بالأسفل.';
                }

                if (showDetail) {
                    const detail = document.querySelector(`[data-detail="${id}"]`);
                    const content = detail.querySelector('.detail-content');
                    content.innerHTML = `<div class="text-xs text-emerald-700 mb-2">${savedMsg}</div>` + EnrLive.render(data);
                    detail.classList.remove('hidden');
                }
                btn.textContent = 'تحديث';
                btn.disabled = false;
                return 'saved';
            } catch (e) {
                status.innerHTML = `<span class="text-red-600 text-xs">خطأ</span>`;
                btn.textContent = 'إعادة';
                btn.disabled = false;
                return 'error';
            }
        }

        document.querySelectorAll('.fetch-btn').forEach(btn => {
            btn.addEventListener('click', () => syncRow(btn, true));
        });

        // مزامنة الكل: تسلسلي مع مهلة بسيطة بين القطارات، وإمكانية الإيقاف.
        let stopFlag = false;
        const allBtn = document.getElementById('sync-all');
        const stopBtn = document.getElementById('sync-stop');
        const prog = document.getElementById('sync-progress');

        allBtn.addEventListener('click', async () => {
            const skipDone = document.getElementById('skip-done').checked;
            const targets = [...document.querySelectorAll('.fetch-btn')]
                .filter(b => !skipDone || !b.closest('tr').querySelector('.status svg'));

            if (!targets.length) { prog.textContent = 'مفيش قطارات تتزامن.'; return; }

            stopFlag = false;
            allBtn.hidden = true;
            stopBtn.hidden = false;
            let done = 0, saved = 0, empty = 0, err = 0;

            for (const b of targets) {
                if (stopFlag) break;
                b.closest('tr').scrollIntoView({ block: 'center', behavior: 'smooth' });
                const r = await syncRow(b, false);
                done++;
                if (r === 'saved') saved++; else if (r === 'empty') empty++; else err++;
                prog.textContent = `${done}/${targets.length} — حُفظ ${saved} · بلا رحلات ${empty} · أخطاء ${err}`;
                await new Promise(res => setTimeout(res, 400));
            }

            stopBtn.hidden = true;
            allBtn.hidden = false;
            prog.textContent += stopFlag ? ' — تم الإيقاف' : ' — اكتمل ✓';
        });

        stopBtn.addEventListener('click', () => { stopFlag = true; });

        // إضافة قطار برقمه: نجيب من الهيئة (متصفّح) ونرسل للاستيراد (ينشئ القطار لو جديد).
        document.getElementById('add-btn').addEventListener('click', async () => {
            const number = document.getElementById('add-number').value.trim();
            const from = document.getElementById('add-from').value;
            const to = document.getElementById('add-to').value;
            const date = document.getElementById('add-date').value;
            const out = document.getElementById('add-result');
            const addBtn = document.getElementById('add-btn');

            if (!number || !from || !to || from === to) {
                out.innerHTML = '<span class="text-amber-600">اكتب رقم القطار واختر محطتين مختلفتين.</span>';
                return;
            }
            addBtn.disabled = true; addBtn.textContent = '...';
            out.textContent = 'جاري الجلب من الهيئة…';

            try {
                const res = await fetch(EnrLive.buildUrl(SEARCH_URL, { from, to, number, date }), { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (!Array.isArray(data) || !data.length) {
                    out.innerHTML = '<span class="text-amber-600">الهيئة مرجّعتش رحلات للرقم/المسار/التاريخ ده. اتأكد منهم.</span>';
                } else {
                    const imp = await fetch(IMPORT_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: JSON.stringify(data),
                    });
                    const r = await imp.json();
                    const added = r.created ? ` — وأُضيف ${r.created} قطار جديد ✓` : '';
                    out.innerHTML = `<div class="text-emerald-700 mb-2">${CHECK} حُفظ ${r.saved} سعر و${r.times} ميعاد${added}</div>` + EnrLive.render(data);
                }
            } catch (e) {
                out.innerHTML = `<span class="text-red-600">تعذّر الجلب: ${e.message}</span>`;
            }
            addBtn.disabled = false; addBtn.textContent = 'جيب وأضف';
        });
    </script>
@endsection
