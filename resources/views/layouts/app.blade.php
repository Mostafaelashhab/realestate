<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (config('push.vapid_public'))
        <meta name="vapid-key" content="{{ config('push.vapid_public') }}">
    @endif
    <meta name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0b6340">
    <title>@yield('title', 'قطارات مصر') — قطارات مصر</title>

    {{-- Open Graph / المعاينة عند المشاركة --}}
    @php $ogDesc = trim($__env->yieldContent('og_desc', 'مواعيد وأسعار قطارات مصر، والمقاعد المتاحة، في مكان واحد.')); @endphp
    <meta name="description" content="{{ $ogDesc }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="قطارات مصر">
    <meta property="og:locale" content="ar_EG">
    <meta property="og:title" content="@yield('og_title', 'قطارات مصر — مواعيد وأسعار')">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/icons/icon-512.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('og_title', 'قطارات مصر — مواعيد وأسعار')">
    <meta name="twitter:description" content="{{ $ogDesc }}">
    <meta name="twitter:image" content="{{ url('/icons/icon-512.png') }}">


    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico?v=7" sizes="any">
    <link rel="icon" href="/icons/favicon-32.png?v=7" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png?v=7">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="قطارات مصر">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&family=tajawal:400,500,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen">
    {{-- شاشة بداية (تظهر عند فتح التطبيق المثبّت فقط) --}}
    <div id="qm-splash" style="position:fixed;inset:0;z-index:9999;display:grid;place-items:center;background:linear-gradient(135deg,#0b5036,#11a06a);transition:opacity .45s ease">
        <div style="text-align:center;color:#fff">
            <x-train-illustration style="width:170px;height:auto;margin:0 auto;animation:qmBob 1.5s ease-in-out infinite"/>
            <p style="font-weight:800;font-size:1.3rem;margin-top:.5rem">قطارات مصر</p>
            <div style="margin:1.1rem auto 0;width:120px;height:4px;border-radius:9px;background:rgba(255,255,255,.25);overflow:hidden">
                <span style="display:block;height:100%;width:45%;background:#fff;border-radius:9px;animation:qmSlide 1.1s ease-in-out infinite"></span>
            </div>
        </div>
    </div>
    <style>
        @keyframes qmBob { 0%, 100% { transform: translateY(0) } 50% { transform: translateY(-8px) } }
        @keyframes qmSlide { 0% { transform: translateX(160%) } 100% { transform: translateX(-260%) } }
    </style>
    <script>
        (() => {
            const s = document.getElementById('qm-splash');
            const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
            // مايظهرش في المتصفّح، ولا يتكرر في نفس الجلسة (عشان التنقّل بين الصفحات).
            if (!standalone || sessionStorage.getItem('qm:splash')) { s.style.display = 'none'; return; }
            try { sessionStorage.setItem('qm:splash', '1'); } catch (e) {}
            const hide = () => { s.style.opacity = '0'; setTimeout(() => s.remove(), 450); };
            addEventListener('load', () => setTimeout(hide, 600));
            setTimeout(hide, 3000); // أمان
        })();
    </script>

    @php
        $bare = trim($__env->yieldContent('bare')) === '1';
        $hideHeader = $bare || trim($__env->yieldContent('hideHeader')) === '1';
        $dark = trim($__env->yieldContent('dark')) === '1';
    @endphp
    <div class="app-shell w-full mx-auto max-w-xl min-h-screen {{ $dark ? 'bg-rail-950' : 'bg-slate-100' }} flex flex-col relative shadow-xl">

        {{-- شريط علوي --}}
        @unless ($hideHeader)
        <header class="sticky top-0 z-30 bg-linear-to-l from-rail-800 to-rail-600 text-white">
            <div class="px-4 pb-3 flex items-center gap-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-lg">
                    <x-icon name="train" class="w-7 h-7" />
                    <span>قطارات مصر</span>
                </a>

                @auth
                    <form action="{{ route('logout') }}" method="POST" class="ms-auto shrink-0">
                        @csrf
                        <button type="submit" aria-label="تسجيل الخروج" title="خروج ({{ auth()->user()->name }})"
                            class="w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 transition">
                            <x-icon name="logout" class="w-5 h-5" />
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" aria-label="تسجيل الدخول"
                        class="ms-auto shrink-0 w-9 h-9 grid place-items-center rounded-full bg-white/15 hover:bg-white/25 transition">
                        <x-icon name="user" class="w-5 h-5" />
                    </a>
                @endauth
            </div>
        </header>
        @endunless

        {{-- المحتوى --}}
        <main class="flex-1 {{ $bare ? '' : 'px-4 py-4 pb-28' }}">
            @yield('content')
        </main>

        {{-- تبويبات سفلية --}}
        @unless ($bare)
        @php
            $tabs = [
                ['route' => 'home', 'icon' => 'home', 'label' => 'الرئيسية', 'on' => request()->routeIs('home') || request()->routeIs('search') || request()->routeIs('trains.show') || request()->routeIs('route')],
                ['route' => 'favorites', 'icon' => 'heart', 'label' => 'المفضلة', 'on' => request()->routeIs('favorites')],
                ['route' => 'fines', 'icon' => 'scale', 'label' => 'الغرامات', 'on' => request()->routeIs('fines')],
                ['route' => 'report', 'icon' => 'flag', 'label' => 'بلّغ', 'on' => request()->routeIs('report')],
            ];
        @endphp
        <nav class="fixed bottom-0 inset-x-0 z-30">
            <div
                class="mx-auto max-w-xl bg-rail-950/95 backdrop-blur border-t border-white/10 px-2 pb-[env(safe-area-inset-bottom)]">
                <div class="grid grid-cols-5 items-end">
                    @foreach ($tabs as $tab)
                        <a href="{{ route($tab['route']) }}"
                            class="flex flex-col items-center gap-1 py-2.5 rounded-xl transition {{ $tab['on'] ? 'text-rail-300' : 'text-slate-400 hover:text-slate-200' }}">
                            <x-icon :name="$tab['icon']" class="w-6 h-6 {{ $tab['on'] ? '' : 'opacity-80' }}" />
                            <span class="text-[11px] font-bold whitespace-nowrap">{{ $tab['label'] }}</span>
                        </a>

                        {{-- زر البحث الصوتي البارز في النص --}}
                        @if ($loop->index === 1)
                            <div class="flex flex-col items-center">
                                <button id="voice-fab" type="button" aria-label="ابحث بصوتك"
                                    class="-mt-7 w-16 h-16 rounded-full bg-linear-to-br from-rail-400 to-rail-600 text-white shadow-lg shadow-rail-500/50 ring-4 ring-rail-950 grid place-items-center active:scale-95 transition">
                                    <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 19v3"/></svg>
                                </button>
                                <span class="text-[11px] font-bold text-rail-300 -mt-0.5">بحث صوتي</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </nav>
        @endunless
    </div>

    @include('partials.pwa')
</body>

</html>