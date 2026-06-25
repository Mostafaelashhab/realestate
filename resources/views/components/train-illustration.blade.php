{{-- رسمة قطار جانبية (أبيض على خلفية خضرا): مقدّمة مائلة + شبابيك + بوجيهات + بانتوغراف + قضبان وفلنكات --}}
<svg viewBox="0 0 280 140" fill="none" aria-hidden="true" {{ $attributes->merge(['class' => 'w-60']) }}>
    {{-- خطوط حركة --}}
    <g stroke="#fff" stroke-opacity=".3" stroke-width="3.5" stroke-linecap="round">
        <path d="M8 56h26"/><path d="M2 72h20"/><path d="M12 88h22"/>
    </g>
    {{-- ظل --}}
    <ellipse cx="150" cy="118" rx="104" ry="6" fill="#000" fill-opacity=".12"/>
    {{-- بانتوغراف --}}
    <g stroke="#fff" stroke-opacity=".75" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M150 24v8M150 32l-14 8M150 32l14 8M132 42h36"/>
    </g>
    {{-- سقف --}}
    <rect x="74" y="34" width="156" height="9" rx="4.5" fill="#fff" fill-opacity=".85"/>
    {{-- جسم القطار بمقدّمة مائلة (الأمام لليسار) --}}
    <path d="M84 40 H224 a16 16 0 0 1 16 16 V96 H50 V64 Q50 48 70 42 Z" fill="#fff"/>
    {{-- زجاج القيادة الأمامي --}}
    <path d="M70 50 Q56 54 54 68 H78 V50 Z" fill="#0a4a31"/>
    {{-- شبابيك الركاب --}}
    <g fill="#0a4a31">
        <rect x="92" y="52" width="28" height="20" rx="5"/>
        <rect x="128" y="52" width="28" height="20" rx="5"/>
        <rect x="164" y="52" width="28" height="20" rx="5"/>
        <rect x="200" y="52" width="28" height="20" rx="5"/>
    </g>
    {{-- خط القاعدة + نور أمامي --}}
    <rect x="50" y="84" width="190" height="6" fill="#0a4a31" fill-opacity=".12"/>
    <circle cx="58" cy="80" r="4.5" fill="#f59e0b"/>
    {{-- بوجيهات (عربتان بأربع عجلات) --}}
    <g fill="#0a4a31">
        <circle cx="88" cy="102" r="9"/><circle cx="114" cy="102" r="9"/>
        <circle cx="188" cy="102" r="9"/><circle cx="214" cy="102" r="9"/>
    </g>
    <g fill="#fff">
        <circle cx="88" cy="102" r="3.5"/><circle cx="114" cy="102" r="3.5"/>
        <circle cx="188" cy="102" r="3.5"/><circle cx="214" cy="102" r="3.5"/>
    </g>
    {{-- القضيب + الفلنكات --}}
    <g stroke="#fff" stroke-opacity=".4" stroke-width="3" stroke-linecap="round"><path d="M28 118h224"/></g>
    <g stroke="#fff" stroke-opacity=".2" stroke-width="3" stroke-linecap="round">
        <path d="M56 114v9"/><path d="M96 114v9"/><path d="M136 114v9"/><path d="M176 114v9"/><path d="M216 114v9"/>
    </g>
</svg>
