@extends('layouts.app')

@section('title', 'متابعة الرحلة')
@section('og_title', 'تابع رحلتي لحظيًا — قطارات مصر')
@section('og_desc', 'متابعة مباشرة لموقع الرحلة على الخريطة.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')
    {{-- تنبيه قرب الوصول --}}
    <div id="arrive-alert" hidden class="bg-emerald-500 text-white rounded-2xl px-4 py-3 mb-4 flex items-center gap-2 font-bold">
        <x-icon name="pin" class="w-5 h-5 shrink-0"/> اقترب من الوجهة!
    </div>

    {{-- بطاقة الرحلة --}}
    <section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-4">
        <div class="flex items-center gap-2 mb-2 flex-wrap">
            @if ($trip->train_number)
                <span class="bg-rail-700 text-white text-sm font-bold px-3 py-1 rounded-lg">قطار {{ $trip->train_number }}</span>
            @endif
            <span id="trip-badge" class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span> <span id="trip-badge-text">جاري التحميل…</span>
            </span>
        </div>

        @if ($trip->from_name && $trip->to_name)
            <div class="flex items-center gap-2 text-slate-700">
                <x-icon name="dot" class="w-2.5 h-2.5 text-rail-600 shrink-0"/>
                <span class="font-medium">{{ $trip->from_name }}</span>
                <x-icon name="arrow-left" class="w-4 h-4 text-slate-400 shrink-0"/>
                <x-icon name="pin" class="w-4 h-4 text-amber-500 shrink-0"/>
                <span class="font-medium">{{ $trip->to_name }}</span>
            </div>
        @endif

        <p class="text-xs text-slate-400 mt-2">
            <span id="trip-updated">—</span>
            @if ($trip->eta) · الوصول المتوقّع (جدول): {{ $trip->eta }} @endif
        </p>
        <p id="trip-eta-live" hidden class="text-sm font-bold text-rail-700 mt-1"></p>
    </section>

    {{-- الخريطة --}}
    <div id="map" class="rounded-3xl ring-1 ring-slate-200 overflow-hidden" style="height: 60vh; min-height: 360px;"></div>

    <p class="text-[11px] text-slate-400 text-center mt-3 leading-relaxed">
        الموقع لحظي من جهاز المسافر. لو وقف التحديث يبقى المشاركة اتقفلت أو الشبكة ضعيفة.
    </p>

    @php
        $initState = [
            'lat' => $trip->last_lat,
            'lng' => $trip->last_lng,
            'to_lat' => $trip->to_lat,
            'to_lng' => $trip->to_lng,
            'active' => ! $trip->isExpired(),
            'last_ago' => $trip->last_at?->diffForHumans(),
            'last_at' => $trip->last_at?->toIso8601String(),
        ];
    @endphp
    <script>
        (() => {
            const STATE_URL = @json(route('trip.state', $trip->token));
            const init = @json($initState);

            const CAIRO = [30.0626, 31.2497];
            const map = L.map('map', { zoomControl: true, attributionControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18, attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            let marker = null;
            const badge = document.getElementById('trip-badge');
            const badgeText = document.getElementById('trip-badge-text');
            const updated = document.getElementById('trip-updated');
            const etaEl = document.getElementById('trip-eta-live');
            const arriveAlert = document.getElementById('arrive-alert');

            const DEST = (init.to_lat != null && init.to_lng != null) ? [init.to_lat, init.to_lng] : null;
            let prev = null; // {lat, lng, t}

            // مسافة بالأمتار (haversine)
            function meters(a, b) {
                const R = 6371000, toR = (d) => d * Math.PI / 180;
                const dLat = toR(b[0] - a[0]), dLng = toR(b[1] - a[1]);
                const x = Math.sin(dLat / 2) ** 2 + Math.cos(toR(a[0])) * Math.cos(toR(b[0])) * Math.sin(dLng / 2) ** 2;
                return R * 2 * Math.asin(Math.min(1, Math.sqrt(x)));
            }

            // حساب الوصول التقديري من السرعة + تنبيه قرب الوجهة
            function updateEta(lat, lng, atIso) {
                if (!DEST) return;
                const remaining = meters([lat, lng], DEST);
                if (remaining < 1500) { arriveAlert.hidden = false; }

                const t = atIso ? Date.parse(atIso) : null;
                if (prev && t && t > prev.t) {
                    const dist = meters([prev.lat, prev.lng], [lat, lng]);
                    const speed = dist / ((t - prev.t) / 1000); // م/ث
                    if (speed > 1) {
                        const min = Math.round(remaining / speed / 60);
                        etaEl.textContent = remaining < 800 ? 'على وشك الوصول' : `وصول تقديري: ~${min} دقيقة (${(remaining / 1000).toFixed(0)} كم)`;
                        etaEl.hidden = false;
                    }
                }
                if (t) prev = { lat, lng, t };
            }

            function setBadge(active, hasLoc) {
                if (active && hasLoc) { badge.className = 'inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full bg-rail-50 text-rail-700'; badgeText.textContent = 'يتحرّك الآن'; badge.firstElementChild.className = 'w-2 h-2 rounded-full bg-rail-600'; }
                else if (active) { badge.className = 'inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700'; badgeText.textContent = 'في انتظار الموقع…'; badge.firstElementChild.className = 'w-2 h-2 rounded-full bg-amber-500'; }
                else { badge.className = 'inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500'; badgeText.textContent = 'انتهت المشاركة'; badge.firstElementChild.className = 'w-2 h-2 rounded-full bg-slate-400'; }
            }

            function place(lat, lng, recenter) {
                const pos = [lat, lng];
                if (!marker) {
                    marker = L.circleMarker(pos, { radius: 9, color: '#fff', weight: 3, fillColor: '#0f7a4b', fillOpacity: 1 }).addTo(map);
                    map.setView(pos, 14);
                } else {
                    marker.setLatLng(pos);
                    if (recenter) map.panTo(pos);
                }
            }

            // حالة أولية
            if (init.lat != null && init.lng != null) { place(init.lat, init.lng, true); updateEta(init.lat, init.lng, init.last_at); }
            else { map.setView(CAIRO, 6); }
            setBadge(init.active, init.lat != null);
            if (init.last_ago) updated.textContent = 'آخر تحديث: ' + init.last_ago;

            let timer = null;
            async function poll() {
                try {
                    const s = await fetch(STATE_URL, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
                    setBadge(s.active, s.lat != null);
                    if (s.lat != null && s.lng != null) { place(s.lat, s.lng, true); updateEta(s.lat, s.lng, s.last_at); }
                    if (s.last_ago) updated.textContent = 'آخر تحديث: ' + s.last_ago;
                    if (!s.active && timer) { clearInterval(timer); timer = null; }
                } catch (e) {}
            }
            timer = setInterval(poll, 10000);
            poll();
        })();
    </script>
@endsection
