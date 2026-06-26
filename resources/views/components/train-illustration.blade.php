{{-- قطار حديث (مواجهة ٣/٤) — جسم أبيض لمّاع + زجاج غامق + لمسات خضرا + خطوط سرعة --}}
<svg viewBox="0 0 240 180" fill="none" aria-hidden="true" {{ $attributes->merge(['class' => 'w-60']) }}>
    <defs>
        <linearGradient id="qmBody" x1="0.1" y1="0" x2="0.3" y2="1">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset=".55" stop-color="#eef2f6"/>
            <stop offset="1" stop-color="#bcc7d3"/>
        </linearGradient>
        <linearGradient id="qmSide" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#e9eef3"/>
            <stop offset="1" stop-color="#a7b3c0"/>
        </linearGradient>
        <linearGradient id="qmGlass" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#14492f"/>
            <stop offset="1" stop-color="#05241a"/>
        </linearGradient>
    </defs>

    {{-- خطوط سرعة --}}
    <g stroke="#22c55e" stroke-linecap="round">
        <path d="M4 66h36" stroke-width="4" stroke-opacity=".75"/>
        <path d="M0 90h28" stroke-width="5" stroke-opacity=".4"/>
        <path d="M10 112h30" stroke-width="3" stroke-opacity=".7"/>
    </g>

    {{-- ظل --}}
    <ellipse cx="150" cy="156" rx="88" ry="9" fill="#000" fill-opacity=".18"/>

    {{-- جسم جانبي (يمتد لليمين) --}}
    <path d="M120 36 H214 a18 18 0 0 1 18 18 V120 a14 14 0 0 1-14 14 H120 Z" fill="url(#qmSide)"/>
    <g fill="url(#qmGlass)">
        <rect x="135" y="56" width="26" height="22" rx="5"/>
        <rect x="168" y="56" width="26" height="22" rx="5"/>
        <rect x="201" y="56" width="22" height="22" rx="5"/>
    </g>
    <path d="M122 98 H226" stroke="#16a34a" stroke-width="7" stroke-linecap="round"/>

    {{-- مقدّمة انسيابية (الكابينة) --}}
    <path d="M122 36 Q70 36 50 80 Q40 104 47 128 a14 14 0 0 0 14 12 H122 a14 14 0 0 0 14-14 V54 a18 18 0 0 0-14-18 Z" fill="url(#qmBody)"/>

    {{-- زجاج القيادة --}}
    <path d="M114 52 Q80 55 65 86 H114 Z" fill="url(#qmGlass)"/>
    <path d="M110 57 Q88 60 76 80" stroke="#ffffff" stroke-opacity=".3" stroke-width="3" stroke-linecap="round"/>

    {{-- لمسة خضرا على المقدّمة --}}
    <path d="M122 94 Q88 94 62 120" stroke="#22c55e" stroke-width="6" stroke-linecap="round"/>

    {{-- نور أمامي --}}
    <circle cx="66" cy="124" r="10" fill="#fbbf24" fill-opacity=".3"/>
    <path d="M58 120l16 4-2 8-15-3z" fill="#fff7d6"/>

    {{-- قاعدة --}}
    <rect x="50" y="138" width="88" height="8" rx="4" fill="#94a3b8"/>
    <path d="M26 150H224" stroke="#ffffff" stroke-opacity=".35" stroke-width="3" stroke-linecap="round"/>
</svg>
