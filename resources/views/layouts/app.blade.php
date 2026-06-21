<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0b6340">
    <title>@yield('title', 'قطارات مصر') — قطارات مصر</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="قطارات مصر">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen">
    <div class="app-shell w-full mx-auto max-w-xl min-h-screen bg-slate-100 flex flex-col relative shadow-xl">

        {{-- شريط علوي --}}
        <header class="sticky top-0 z-30 bg-linear-to-l from-rail-800 to-rail-600 text-white">
            <div class="px-4 pb-3 flex items-center gap-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
                @php $isHome = request()->routeIs('home'); @endphp
                @unless ($isHome)
                    <a href="{{ url()->previous() }}"
                        class="shrink-0 w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 transition">
                        <x-icon name="chevron-right" class="w-5 h-5" />
                    </a>
                @endunless
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-lg">
                    <x-icon name="train" class="w-7 h-7" />
                    <span>قطارات مصر</span>
                </a>
                <span class="ms-auto text-[11px] bg-white/15 rounded-full px-2.5 py-1">@yield('badge', 'مصر')</span>
            </div>
        </header>

        {{-- المحتوى --}}
        <main class="flex-1 px-4 py-4 pb-28">
            @yield('content')


        </main>

        {{-- تبويبات سفلية --}}
        @php
            $tabs = [
                ['route' => 'home', 'icon' => 'home', 'label' => 'الرئيسية', 'on' => request()->routeIs('home') || request()->routeIs('search') || request()->routeIs('trains.show')],
                ['route' => 'fines', 'icon' => 'scale', 'label' => 'الغرامات', 'on' => request()->routeIs('fines')],
                ['route' => 'report', 'icon' => 'flag', 'label' => 'بلّغ عن خطأ', 'on' => request()->routeIs('report')],
            ];
        @endphp
        <nav class="fixed bottom-0 inset-x-0 z-30">
            <div
                class="mx-auto max-w-xl bg-white/95 backdrop-blur border-t border-slate-200 px-2 pb-[env(safe-area-inset-bottom)]">
                <div class="grid grid-cols-3">
                    @foreach ($tabs as $tab)
                        <a href="{{ route($tab['route']) }}"
                            class="flex flex-col items-center gap-1 py-2.5 rounded-xl transition {{ $tab['on'] ? 'text-rail-700' : 'text-slate-400 hover:text-slate-600' }}">
                            <x-icon :name="$tab['icon']" class="w-6 h-6 {{ $tab['on'] ? '' : 'opacity-70' }}" />
                            <span class="text-[11px] font-bold">{{ $tab['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    </div>

    @include('partials.pwa')
</body>

</html>