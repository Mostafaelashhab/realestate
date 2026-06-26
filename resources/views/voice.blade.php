@extends('layouts.app')

@section('title', 'المساعد الصوتي')

@section('content')
    <div class="min-h-[68vh] flex flex-col items-center justify-center text-center px-2">
        <h1 class="text-2xl font-extrabold mb-1">قول رايح فين</h1>
        <p class="text-sm text-slate-500 mb-10 leading-relaxed">اضغط المايك واتكلم — هنلاقيلك القطر أو المحطة في لحظات.</p>

        {{-- المايك مع توهّج --}}
        <button id="mic" type="button" aria-label="ابدأ التحدّث"
            class="relative w-32 h-32 rounded-full grid place-items-center bg-linear-to-br from-rail-500 to-rail-700 text-white shadow-xl shadow-rail-600/40 active:scale-95 transition focus:outline-none focus-visible:ring-4 focus-visible:ring-rail-500/30">
            <span id="glow" class="absolute inset-0 rounded-full bg-rail-400/50 animate-ping hidden" aria-hidden="true"></span>
            <svg viewBox="0 0 24 24" class="relative w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 19v3"/>
            </svg>
        </button>

        {{-- موجة الصوت --}}
        <div id="wave" class="vwave text-rail-600 mt-6 h-5 hidden">
            <i class="vbar" style="animation-delay:0s"></i><i class="vbar" style="animation-delay:.15s"></i><i class="vbar" style="animation-delay:.3s"></i><i class="vbar" style="animation-delay:.15s"></i><i class="vbar" style="animation-delay:0s"></i>
        </div>

        <p id="status" class="text-sm font-bold text-slate-600 mt-6 min-h-[1.5rem]">اضغط المايك واتكلم</p>

        {{-- أمثلة --}}
        <div class="mt-10 w-full max-w-sm">
            <p class="text-xs text-slate-400 mb-3">جرّب تقول</p>
            <div class="flex flex-wrap justify-center gap-2">
                @foreach (['من القاهرة لطنطا', 'الإسكندرية', 'قطار 1914', 'بنها'] as $eg)
                    <button type="button" data-q="{{ $eg }}"
                        class="voice-eg inline-flex items-center gap-1.5 bg-white ring-1 ring-slate-200 hover:ring-rail-300 active:scale-95 rounded-full px-3.5 py-2 text-sm transition">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-rail-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 19v3"/></svg>
                        {{ $eg }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        (() => {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            const mic = document.getElementById('mic');
            const glow = document.getElementById('glow');
            const wave = document.getElementById('wave');
            const status = document.getElementById('status');
            const VOICE_URL = @json(route('voice'));
            const go = (q) => { status.textContent = `بدوّرلك على «${q}»…`; location.href = VOICE_URL + '?q=' + encodeURIComponent(q); };

            document.querySelectorAll('.voice-eg').forEach(b => b.addEventListener('click', () => go(b.dataset.q)));

            if (!SR) {
                status.textContent = 'متصفّحك مش بيدعم البحث الصوتي — اختار من فوق أو من الرئيسية.';
                mic.disabled = true; mic.classList.add('opacity-60');
                return;
            }

            let busy = false;
            mic.addEventListener('click', () => {
                if (busy) return;
                busy = true;
                let rec;
                try { rec = new SR(); } catch (e) { busy = false; return; }
                rec.lang = 'ar-EG'; rec.interimResults = false; rec.maxAlternatives = 1;
                glow.classList.remove('hidden'); wave.classList.remove('hidden');
                status.textContent = 'بسمعك… اتكلم دلوقتي';
                rec.onresult = (e) => { const t = (e.results[0][0].transcript || '').trim(); if (t) go(t); else status.textContent = 'متسمعتش حاجة، حاول تاني'; };
                rec.onerror = (e) => {
                    const map = { 'not-allowed': 'لازم تسمح بالمايك 🎤', 'service-not-allowed': 'فعّل الإملاء من إعدادات الجهاز', 'no-speech': 'متسمعتش صوت — اضغط واتكلم على طول', 'audio-capture': 'مفيش مايك متاح', 'network': 'النت ضعيف، حاول تاني' };
                    status.textContent = map[e.error] ?? 'حاول تاني';
                };
                rec.onend = () => { busy = false; glow.classList.add('hidden'); wave.classList.add('hidden'); };
                try { rec.start(); } catch (e) { busy = false; glow.classList.add('hidden'); wave.classList.add('hidden'); }
            });
        })();
    </script>
@endsection
