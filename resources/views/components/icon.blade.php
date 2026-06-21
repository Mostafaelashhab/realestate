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
    @endswitch
</svg>
