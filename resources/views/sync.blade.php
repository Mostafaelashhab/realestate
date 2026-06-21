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

        document.querySelectorAll('.fetch-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('tr');
                const id = row.dataset.row;
                const status = row.querySelector('.status');
                const detail = document.querySelector(`[data-detail="${id}"]`);
                const content = detail.querySelector('.detail-content');
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
                        status.innerHTML = '<span class="text-amber-600">لا رحلات</span>';
                        btn.textContent = 'إعادة';
                        btn.disabled = false;
                        return;
                    }

                    // حفظ الأسعار/النوع في الموقع
                    let savedMsg = '';
                    try {
                        const imp = await fetch(IMPORT_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                            body: JSON.stringify(data),
                        });
                        const result = await imp.json();
                        savedMsg = `${CHECK} تم حفظ ${result.saved} سعر في الموقع`;
                        status.innerHTML = CHECK;
                    } catch (e) {
                        savedMsg = 'تعذّر الحفظ، لكن البيانات معروضة بالأسفل.';
                    }

                    content.innerHTML = `<div class="text-xs text-emerald-700 mb-2">${savedMsg}</div>` + EnrLive.render(data);
                    detail.classList.remove('hidden');
                    btn.textContent = 'تحديث';
                    btn.disabled = false;
                } catch (e) {
                    status.innerHTML = `<span class="text-red-600">خطأ: ${e.message}</span>`;
                    btn.textContent = 'إعادة';
                    btn.disabled = false;
                }
            });
        });
    </script>
@endsection
