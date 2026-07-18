@props(['name'])

@php
    // أيقونات SVG داخلية (نمط خطّي 24×24، تتلوّن من لون النص currentColor).
    $stroke = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
@endphp

<svg viewBox="0 0 24 24" aria-hidden="true" {{ $attributes->merge(['class' => 'w-5 h-5']) }}>
    @switch($name)
        @case('train')
            <g {!! $stroke !!}>
                <rect x="5" y="3" width="14" height="13" rx="2"/>
                <path d="M5 11h14M9 3v8m6-8v8"/>
                <circle cx="8.5" cy="13.5" r="0.6" fill="currentColor" stroke="none"/>
                <circle cx="15.5" cy="13.5" r="0.6" fill="currentColor" stroke="none"/>
                <path d="M7 16l-2 4m12-4l2 4"/>
            </g>
            @break

        @case('home')
            <g {!! $stroke !!}>
                <path d="M3 10.5 12 3l9 7.5"/>
                <path d="M5 9.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5"/>
                <path d="M9.5 21v-6h5v6"/>
            </g>
            @break

        @case('scale')
            <g {!! $stroke !!}>
                <path d="M12 3v18M7 21h10"/>
                <path d="M12 5 5 7m7-2 7 2"/>
                <path d="M5 7 2.5 13a3 3 0 0 0 5 0L5 7zm14 0-2.5 6a3 3 0 0 0 5 0L19 7z"/>
            </g>
            @break

        @case('ticket')
            <g {!! $stroke !!}>
                <path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4 2 2 0 0 0 0-4z"/>
                <path d="M13 6v2m0 4v2m0 4v2"/>
            </g>
            @break

        @case('check')
            <g {!! $stroke !!}>
                <path d="M20 6 9 17l-5-5"/>
            </g>
            @break

        @case('pin')
            <g {!! $stroke !!}>
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </g>
            @break

        @case('dot')
            <circle cx="12" cy="12" r="6" fill="currentColor" stroke="none"/>
            @break

        @case('refresh')
            <g {!! $stroke !!}>
                <path d="M21 12a9 9 0 1 1-2.64-6.36M21 4v5h-5"/>
            </g>
            @break

        @case('arrow-left')
            <g {!! $stroke !!}>
                <path d="M19 12H5m6 7-7-7 7-7"/>
            </g>
            @break

        @case('chevron-right')
            <g {!! $stroke !!}>
                <path d="m9 18 6-6-6-6"/>
            </g>
            @break

        @case('whatsapp')
            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.3A10 10 0 1 0 12 2zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5.1-4.5-.1-.2-1.2-1.5-1.2-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.6-.3.3c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.2.5.3.1.3.1.8-.1 1.2z"/>
            @break

        @case('clock')
            <g {!! $stroke !!}>
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 2"/>
            </g>
            @break

        @case('station')
            <g {!! $stroke !!}>
                <path d="M4 21V8l8-5 8 5v13"/>
                <path d="M9 21v-5h6v5M9 11h6"/>
            </g>
            @break

        @case('flag')
            <g {!! $stroke !!}>
                <path d="M5 21V4m0 1h11l-2 4 2 4H5"/>
            </g>
            @break

        @case('send')
            <g {!! $stroke !!}>
                <path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </g>
            @break

        @case('alert')
            <g {!! $stroke !!}>
                <path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
            </g>
            @break

        @case('calendar')
            <g {!! $stroke !!}>
                <rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/>
                <path d="M3 9.5h18M8 2.5v4m8-4v4"/>
            </g>
            @break

        @case('swap')
            <g {!! $stroke !!}>
                <path d="M7 4v16m0 0-3-3m3 3 3-3M17 20V4m0 0-3 3m3-3 3 3"/>
            </g>
            @break

        @case('search')
            <g {!! $stroke !!}>
                <circle cx="11" cy="11" r="7"/>
                <path d="m21 21-4.3-4.3"/>
            </g>
            @break

        @case('star')
            <g {!! $stroke !!}>
                <path d="M12 3.5l2.6 5.3 5.8.9-4.2 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.2-4.1 5.8-.9z"/>
            </g>
            @break

        @case('clock-history')
            <g {!! $stroke !!}>
                <path d="M3 3v5h5"/>
                <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/>
                <path d="M12 7v5l3 2"/>
            </g>
            @break

        @case('user')
            <g {!! $stroke !!}>
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>
            </g>
            @break

        @case('logout')
            <g {!! $stroke !!}>
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <path d="M16 17l5-5-5-5M21 12H9"/>
            </g>
            @break

        @case('share')
            <g {!! $stroke !!}>
                <circle cx="18" cy="5" r="3"/>
                <circle cx="6" cy="12" r="3"/>
                <circle cx="18" cy="19" r="3"/>
                <path d="m8.6 13.5 6.8 4M15.4 6.5 8.6 10.5"/>
            </g>
            @break

        @case('moon')
            <g {!! $stroke !!}>
                <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
            </g>
            @break

        @case('sun')
            <g {!! $stroke !!}>
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4 1.4-1.4"/>
            </g>
            @break

        @case('mic')
            <g {!! $stroke !!}>
                <rect x="9" y="2" width="6" height="12" rx="3"/>
                <path d="M5 10a7 7 0 0 0 14 0M12 19v3"/>
            </g>
            @break

        @case('bell')
            <g {!! $stroke !!}>
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"/>
            </g>
            @break

        @case('heart')
            <g {!! $stroke !!}>
                <path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 0 0-7.1 7.1l1.7 1.7L12 21.5l7.1-7.1 1.7-1.7a5 5 0 0 0 0-7.1z"/>
            </g>
            @break

        @case('more')
            <g {!! $stroke !!}>
                <circle cx="5" cy="12" r="1.4" fill="currentColor" stroke="none"/>
                <circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/>
                <circle cx="19" cy="12" r="1.4" fill="currentColor" stroke="none"/>
            </g>
            @break

        @case('seat')
            <g {!! $stroke !!}>
                <path d="M7 11V7.5A2.5 2.5 0 0 1 9.5 5h5A2.5 2.5 0 0 1 17 7.5V11"/>
                <rect x="4.5" y="11" width="15" height="6" rx="2.5"/>
                <path d="M6.5 17v2.5M17.5 17v2.5"/>
            </g>
            @break
    @endswitch
</svg>
