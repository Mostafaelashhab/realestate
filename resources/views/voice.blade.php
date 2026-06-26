@extends('layouts.app')

@section('title', 'البحث بصوتك')
@section('bare', '1')

@section('content')
    <div class="relative min-h-screen overflow-hidden bg-linear-to-b from-rail-950 via-rail-900 to-rail-950 text-white flex flex-col px-6 pt-[max(1rem,env(safe-area-inset-top))] pb-10">
        {{-- زخرفة توهّج --}}
        <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-80 h-80 rounded-full bg-rail-500/20 blur-3xl"></div>

        {{-- الشريط العلوي --}}
        <div class="relative flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-sm font-bold text-white/70 hover:text-white transition">إلغاء</a>
            <h1 class="font-extrabold">البحث بصوتك</h1>
            <span class="w-10"></span>
        </div>

        {{-- المايك + الحلقات --}}
        <div class="relative flex-1 flex flex-col items-center justify-center">
            <div class="relative grid place-items-center">
                {{-- موجات صوت جانبية --}}
                <div id="bars-r" class="absolute right-full me-4 vwave h-12 text-rail-400/70 opacity-0 transition-opacity">
                    @for ($i = 0; $i < 6; $i++)<i class="vbar" style="animation-delay:{{ $i * .12 }}s"></i>@endfor
                </div>
                <div id="bars-l" class="absolute left-full ms-4 vwave h-12 text-rail-400/70 opacity-0 transition-opacity">
                    @for ($i = 0; $i < 6; $i++)<i class="vbar" style="animation-delay:{{ $i * .12 }}s"></i>@endfor
                </div>

                {{-- حلقات متوهّجة --}}
                <span id="ring1" class="absolute w-44 h-44 rounded-full border border-rail-400/30"></span>
                <span id="ring2" class="absolute w-60 h-60 rounded-full border border-rail-400/15"></span>
                <span id="ping" class="absolute w-32 h-32 rounded-full bg-rail-500/30 hidden animate-ping"></span>

                <button id="mic" type="button" aria-label="ابدأ التحدّث"
                    class="relative w-28 h-28 rounded-full grid place-items-center bg-linear-to-br from-rail-400 to-rail-600 text-white shadow-2xl shadow-rail-500/40 active:scale-95 transition focus:outline-none focus-visible:ring-4 focus-visible:ring-rail-300/40">
                    <svg viewBox="0 0 24 24" class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 19v3"/>
                    </svg>
                </button>
            </div>

            <p class="mt-10 text-lg font-bold">اسأل عن أي محطة أو موعد</p>
            <p id="status" class="mt-1 text-sm text-white/60 min-h-5">جرّب تقول:</p>

            {{-- أمثلة --}}
            <div class="mt-5 w-full max-w-sm space-y-2.5">
                @foreach (['من القاهرة لطنطا', 'قطار 1914', 'الإسكندرية'] as $eg)
                    <button type="button" data-q="{{ $eg }}"
                        class="voice-eg w-full text-center bg-white/5 hover:bg-white/10 ring-1 ring-white/10 rounded-2xl px-4 py-3 text-sm font-medium transition active:scale-[.98]">
                        {{ $eg }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- موجة سفلية + حالة --}}
        <div class="relative text-center">
            <div id="footwave" class="vwave h-6 justify-center text-rail-400/50 mb-3">
                @for ($i = 0; $i < 18; $i++)<i class="vbar" style="animation-delay:{{ ($i % 6) * .1 }}s"></i>@endfor
            </div>
            <p id="foot" class="text-sm text-white/50">اضغط المايك واتكلم</p>
        </div>
    </div>

    <script>
        (() => {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            const mic = document.getElementById('mic');
            const ping = document.getElementById('ping');
            const status = document.getElementById('status');
            const foot = document.getElementById('foot');
            const barsL = document.getElementById('bars-l');
            const barsR = document.getElementById('bars-r');
            const VOICE_URL = @json(route('voice'));
            const buzz = (ms) => { try { navigator.vibrate?.(ms); } catch (e) {} };
            const go = (q) => { buzz(15); foot.textContent = `بدوّرلك على «${q}»…`; location.href = VOICE_URL + '?q=' + encodeURIComponent(q); };
            const listening = (on) => {
                ping.classList.toggle('hidden', !on);
                barsL.style.opacity = barsR.style.opacity = on ? '1' : '0';
                mic.classList.toggle('animate-pulse', on);
            };

            document.querySelectorAll('.voice-eg').forEach(b => b.addEventListener('click', () => go(b.dataset.q)));

            if (!SR) {
                foot.textContent = 'متصفّحك مش بيدعم الصوت — اختار مثال فوق';
                mic.disabled = true; mic.classList.add('opacity-60');
                return;
            }

            let busy = false;
            mic.addEventListener('click', () => {
                if (busy) return;
                busy = true; buzz(20);
                let rec;
                try { rec = new SR(); } catch (e) { busy = false; return; }
                rec.lang = 'ar-EG'; rec.interimResults = false; rec.maxAlternatives = 1;
                listening(true);
                foot.textContent = 'نستمع لك…';
                rec.onresult = (e) => { const t = (e.results[0][0].transcript || '').trim(); if (t) go(t); else foot.textContent = 'متسمعتش حاجة، حاول تاني'; };
                rec.onerror = (e) => {
                    const map = { 'not-allowed': 'لازم تسمح بالمايك', 'service-not-allowed': 'فعّل الإملاء من إعدادات الجهاز', 'no-speech': 'متسمعتش صوت — اتكلم على طول', 'audio-capture': 'مفيش مايك متاح', 'network': 'النت ضعيف، حاول تاني' };
                    foot.textContent = map[e.error] ?? 'حاول تاني';
                };
                rec.onend = () => { busy = false; listening(false); };
                try { rec.start(); } catch (e) { busy = false; listening(false); }
            });
        })();
    </script>
@endsection
