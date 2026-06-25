@extends('layouts.app')

@section('title', 'محفظة تذاكري')
@section('og_desc', 'احفظ صور تذاكرك واعرضها في المحطة بدون نت.')

@section('content')
    <div class="flex items-center justify-between mb-1">
        <h1 class="text-xl font-extrabold flex items-center gap-2">
            <x-icon name="ticket" class="w-6 h-6 text-rail-600" /> محفظة تذاكري
        </h1>
        <button id="add-ticket" type="button"
            class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 active:scale-95 text-white text-sm font-bold rounded-xl px-3.5 py-2 transition">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4"
                stroke-linecap="round">
                <path d="M12 5v14M5 12h14" />
            </svg>
            أضف تذكرة
        </button>
    </div>
    <p class="text-sm text-slate-500 mb-4">صوّر تذكرتك واحفظها هنا — تتخزّن على جهازك وتظهر للمفتّش حتى من غير نت.</p>

    {{-- قائمة التذاكر --}}
    <div id="tickets-list" class="space-y-3"></div>

    {{-- حالة فارغة --}}
    <div id="tickets-empty" hidden class="text-center py-14 px-6">
        <div class="w-20 h-20 mx-auto mb-4 grid place-items-center rounded-3xl bg-rail-50 text-rail-500">
            <x-icon name="ticket" class="w-10 h-10" />
        </div>
        <p class="font-bold text-slate-700">مفيش تذاكر محفوظة</p>
        <p class="text-sm text-slate-500 mt-1 mb-5">احفظ صورة تذكرتك عشان توصلها بسرعة في المحطة حتى بدون نت.</p>
        <button id="add-ticket-empty" type="button"
            class="inline-flex items-center gap-1.5 bg-rail-600 hover:bg-rail-700 text-white text-sm font-bold rounded-xl px-4 py-2.5 transition">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4"
                stroke-linecap="round">
                <path d="M12 5v14M5 12h14" />
            </svg>
            أضف أول تذكرة
        </button>
    </div>

    {{-- نموذج الإضافة (نافذة) --}}
    <div id="ticket-modal" hidden
        class="fixed inset-0 z-50 grid place-items-end sm:place-items-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white w-full max-w-xl rounded-t-3xl sm:rounded-3xl p-5 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-extrabold text-lg">تذكرة جديدة</h2>
                <button id="ticket-close" type="button" aria-label="إغلاق"
                    class="w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:bg-slate-100">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            {{-- اختيار الصورة --}}
            <label id="photo-drop"
                class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 hover:border-rail-400 bg-slate-50 transition overflow-hidden">
                <input id="photo-input" type="file" accept="image/*" capture="environment" class="sr-only">
                <div id="photo-placeholder" class="py-8 text-center text-slate-500">
                    <svg viewBox="0 0 24 24" class="w-9 h-9 mx-auto mb-2 text-slate-400" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z" />
                        <circle cx="12" cy="13" r="3.5" />
                    </svg>
                    <p class="font-bold text-sm">صوّر التذكرة أو اختر صورة</p>
                    <p class="text-xs mt-0.5">الباركود/الـ QR لازم يبان واضح</p>
                </div>
                <img id="photo-preview" hidden alt="" class="w-full max-h-72 object-contain bg-slate-900">
            </label>

            {{-- بيانات اختيارية للبحث السريع --}}
            <div class="grid grid-cols-2 gap-2.5 mt-4">
                <input id="t-number" inputmode="numeric" placeholder="رقم القطار"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <input id="t-date" type="date"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <input id="t-from" placeholder="من محطة"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <input id="t-to" placeholder="إلى محطة"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <input id="t-coach" placeholder="العربة"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
                <input id="t-seat" placeholder="الكرسي"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:bg-white focus:border-rail-400 focus:outline-none">
            </div>

            <p id="ticket-err" hidden class="text-sm text-red-600 mt-3"></p>

            <button id="ticket-save" type="button"
                class="w-full mt-4 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-3.5 transition disabled:opacity-50">
                حفظ التذكرة
            </button>
        </div>
    </div>

    {{-- عارض ملء الشاشة (للتفتيش) --}}
    <div id="ticket-viewer" hidden class="fixed inset-0 z-60 bg-white flex flex-col">
        <div
            class="flex items-center justify-between px-4 py-3 bg-rail-700 text-white pt-[max(0.75rem,env(safe-area-inset-top))]">
            <div id="viewer-meta" class="min-w-0 text-sm font-bold truncate"></div>
            <button id="viewer-close" type="button" aria-label="إغلاق"
                class="shrink-0 w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25">
                <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-auto grid place-items-center bg-white p-2">
            <img id="viewer-img" alt="تذكرة" class="max-w-full">
        </div>
        <div id="viewer-details" class="px-4 py-3 text-sm bg-slate-50 border-t border-slate-200"></div>
        <div
            class="px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] flex items-center gap-2 bg-white border-t border-slate-200">
            <p class="text-xs text-slate-400 flex-1">💡 ارفع إضاءة الشاشة لأقصاها وقت التفتيش</p>
            <button id="viewer-delete" type="button"
                class="text-sm font-bold text-red-600 hover:bg-red-50 rounded-xl px-3 py-2">حذف</button>
        </div>
    </div>

    <script>
        (() => {
            const KEY = 'qm:tickets';
            const listEl = document.getElementById('tickets-list');
            const emptyEl = document.getElementById('tickets-empty');
            const modal = document.getElementById('ticket-modal');
            const viewer = document.getElementById('ticket-viewer');

            const load = () => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; } };
            const save = (arr) => { try { localStorage.setItem(KEY, JSON.stringify(arr)); return true; } catch (e) { return false; } };
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            const fmtDate = (d) => {
                if (!d) return '';
                try { return new Date(d + 'T00:00').toLocaleDateString('ar-EG', { weekday: 'short', day: 'numeric', month: 'long' }); }
                catch (e) { return d; }
            };
            const subtitle = (t) => {
                const route = [t.from, t.to].filter(Boolean).join(' ← ');
                const seat = [t.coach && ('عربة ' + t.coach), t.seat && ('كرسي ' + t.seat)].filter(Boolean).join(' · ');
                return [route, seat].filter(Boolean).join(' • ');
            };

            // — عرض القائمة —
            function renderList() {
                const items = load().sort((a, b) => (b.date || '').localeCompare(a.date || ''));
                emptyEl.hidden = items.length > 0;
                const today = new Date().toISOString().slice(0, 10);
                listEl.innerHTML = items.map(t => {
                    const upcoming = t.date && t.date >= today;
                    return `<button type="button" data-view="${t.id}"
                            class="w-full text-start bg-white rounded-2xl shadow-sm ring-1 ${upcoming ? 'ring-rail-200' : 'ring-slate-100'} p-3 flex items-center gap-3 hover:ring-rail-300 active:scale-[.99] transition ${upcoming ? '' : 'opacity-70'}">
                            <img src="${t.photo}" alt="" class="w-16 h-16 rounded-xl object-cover bg-slate-100 shrink-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    ${t.number ? `<span class="font-extrabold">قطار ${esc(t.number)}</span>` : '<span class="font-extrabold">تذكرة</span>'}
                                    ${upcoming ? '<span class="text-[10px] font-bold bg-rail-50 text-rail-700 rounded-full px-2 py-0.5">قادمة</span>' : ''}
                                </div>
                                ${t.date ? `<p class="text-xs text-slate-500 mt-0.5">${esc(fmtDate(t.date))}</p>` : ''}
                                ${subtitle(t) ? `<p class="text-xs text-slate-500 mt-0.5 truncate">${esc(subtitle(t))}</p>` : ''}
                            </div>
                            <svg viewBox="0 0 24 24" class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>`;
                }).join('');
            }

            // — النموذج —
            const photoInput = document.getElementById('photo-input');
            const photoPreview = document.getElementById('photo-preview');
            const photoPlaceholder = document.getElementById('photo-placeholder');
            const errEl = document.getElementById('ticket-err');
            const saveBtn = document.getElementById('ticket-save');
            let photoData = null;

            const showErr = (m) => { errEl.textContent = m; errEl.hidden = !m; };
            function openModal() {
                photoData = null;
                photoPreview.hidden = true; photoPreview.removeAttribute('src');
                photoPlaceholder.hidden = false;
                ['t-number', 't-date', 't-from', 't-to', 't-coach', 't-seat'].forEach(id => document.getElementById(id).value = '');
                showErr('');
                modal.hidden = false;
            }
            const closeModal = () => { modal.hidden = true; };

            // ضغط الصورة قبل التخزين (أقصى بُعد 1400px، JPEG) عشان localStorage ميمتلئش.
            function compress(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => {
                        const img = new Image();
                        img.onload = () => {
                            const max = 1400;
                            let { width: w, height: h } = img;
                            if (w > max || h > max) { const r = Math.min(max / w, max / h); w = Math.round(w * r); h = Math.round(h * r); }
                            const canvas = document.createElement('canvas');
                            canvas.width = w; canvas.height = h;
                            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                            resolve(canvas.toDataURL('image/jpeg', 0.72));
                        };
                        img.onerror = reject;
                        img.src = reader.result;
                    };
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
            }

            photoInput.addEventListener('change', async () => {
                const file = photoInput.files?.[0];
                if (!file) return;
                showErr('');
                try {
                    photoData = await compress(file);
                    photoPreview.src = photoData;
                    photoPreview.hidden = false;
                    photoPlaceholder.hidden = true;
                } catch (e) { showErr('تعذّر قراءة الصورة، جرّب صورة تانية.'); }
            });

            saveBtn.addEventListener('click', () => {
                if (!photoData) { showErr('صوّر التذكرة أو اختر صورة الأول.'); return; }
                const t = {
                    id: 't' + Date.now(),
                    photo: photoData,
                    number: document.getElementById('t-number').value.trim(),
                    date: document.getElementById('t-date').value,
                    from: document.getElementById('t-from').value.trim(),
                    to: document.getElementById('t-to').value.trim(),
                    coach: document.getElementById('t-coach').value.trim(),
                    seat: document.getElementById('t-seat').value.trim(),
                };
                const arr = load();
                arr.push(t);
                if (!save(arr)) { showErr('مساحة التخزين ممتلئة — احذف تذاكر قديمة وحاول تاني.'); return; }
                closeModal();
                renderList();
            });

            // — العارض ملء الشاشة —
            const viewerImg = document.getElementById('viewer-img');
            const viewerMeta = document.getElementById('viewer-meta');
            const viewerDetails = document.getElementById('viewer-details');
            const viewerDelete = document.getElementById('viewer-delete');
            let currentId = null;

            function openViewer(id) {
                const t = load().find(x => x.id === id);
                if (!t) return;
                currentId = id;
                viewerImg.src = t.photo;
                viewerMeta.textContent = [t.number && ('قطار ' + t.number), fmtDate(t.date)].filter(Boolean).join(' — ') || 'تذكرة';
                const rows = [
                    [t.from || t.to ? [t.from, t.to].filter(Boolean).join(' ← ') : '', 'المسار'],
                    [t.coach, 'العربة'], [t.seat, 'الكرسي'],
                ].filter(([v]) => v);
                viewerDetails.innerHTML = rows.length
                    ? rows.map(([v, l]) => `<span class="inline-flex items-center gap-1 me-3"><span class="text-slate-400">${l}:</span> <b>${esc(v)}</b></span>`).join('')
                    : '<span class="text-slate-400">من غير بيانات إضافية</span>';
                viewer.hidden = false;
            }
            const closeViewer = () => { viewer.hidden = true; currentId = null; };

            viewerDelete.addEventListener('click', () => {
                if (!currentId) return;
                if (!confirm('تحذف التذكرة دي؟')) return;
                save(load().filter(x => x.id !== currentId));
                closeViewer();
                renderList();
            });

            // تكبير/تصغير الصورة بالضغط
            viewerImg.addEventListener('click', () => {
                const zoomed = viewerImg.classList.toggle('max-w-none');
                viewerImg.classList.toggle('cursor-zoom-out', zoomed);
            });

            // — الأحداث —
            document.getElementById('add-ticket').addEventListener('click', openModal);
            document.getElementById('add-ticket-empty').addEventListener('click', openModal);
            document.getElementById('ticket-close').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            document.getElementById('viewer-close').addEventListener('click', closeViewer);
            listEl.addEventListener('click', (e) => {
                const b = e.target.closest('[data-view]');
                if (b) openViewer(b.dataset.view);
            });
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                if (!viewer.hidden) closeViewer(); else if (!modal.hidden) closeModal();
            });

            renderList();
        })();
    </script>
@endsection