<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0b6340">
    <title>@yield('title', 'حسابك') — قطارات مصر</title>

    <link rel="icon" href="/favicon.ico?v=7" sizes="any">
    <link rel="icon" href="/icons/favicon-32.png?v=7" sizes="32x32" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen">
    <div class="mx-auto max-w-md min-h-screen flex flex-col bg-slate-100">

        {{-- رأس متدرّج + رسمة قطار --}}
        <div class="relative overflow-hidden bg-linear-to-br from-rail-800 via-rail-700 to-rail-600 text-white px-6 pb-16 pt-[max(1.5rem,env(safe-area-inset-top))] rounded-b-[2.5rem] shadow-xl shadow-rail-800/25">
            {{-- زخرفة قضبان --}}
            <svg class="absolute -top-8 -start-10 w-44 h-44 text-white/10" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M20 0v100M40 0v100M60 0v100M80 0v100"/>
                <path d="M0 30h100M0 55h100M0 80h100" stroke-dasharray="6 8"/>
            </svg>

            <div class="relative">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-extrabold text-lg">
                    <x-icon name="train" class="w-7 h-7"/> قطارات مصر
                </a>

                {{-- رسمة القطار --}}
                <svg viewBox="0 0 260 130" class="w-56 mx-auto mt-4" fill="none" aria-hidden="true">
                    <g stroke="#ffffff" stroke-opacity=".3" stroke-width="3.5" stroke-linecap="round">
                        <path d="M6 50h28"/><path d="M0 66h22"/><path d="M10 82h24"/>
                    </g>
                    <ellipse cx="140" cy="112" rx="92" ry="7" fill="#000" fill-opacity=".12"/>
                    <rect x="58" y="28" width="160" height="10" rx="5" fill="#fff" fill-opacity=".85"/>
                    <rect x="42" y="34" width="190" height="62" rx="18" fill="#fff"/>
                    <g fill="#0a4a31">
                        <rect x="70" y="46" width="26" height="22" rx="6"/>
                        <rect x="104" y="46" width="26" height="22" rx="6"/>
                        <rect x="138" y="46" width="26" height="22" rx="6"/>
                        <rect x="172" y="46" width="40" height="22" rx="6"/>
                    </g>
                    <rect x="42" y="80" width="190" height="6" fill="#0a4a31" fill-opacity=".12"/>
                    <circle cx="52" cy="84" r="5" fill="#f59e0b"/>
                    <g fill="#0a4a31"><circle cx="80" cy="100" r="11"/><circle cx="190" cy="100" r="11"/></g>
                    <g fill="#fff"><circle cx="80" cy="100" r="4"/><circle cx="190" cy="100" r="4"/></g>
                    <rect x="20" y="114" width="220" height="4" rx="2" fill="#fff" fill-opacity=".4"/>
                </svg>
            </div>
        </div>

        {{-- المحتوى يطفو فوق الرأس --}}
        <main class="flex-1 px-5 -mt-10 pb-10">
            <div class="max-w-sm mx-auto">
                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
