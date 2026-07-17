{{-- العروض/البانرات (السكربت في home-interactive) --}}
@if ($promos->isNotEmpty())
    @php
        $promoStyles = [
            'rail' => 'bg-rail-50 text-rail-900 ring-rail-200',
            'amber' => 'bg-amber-50 text-amber-900 ring-amber-200',
            'sky' => 'bg-sky-50 text-sky-900 ring-sky-200',
        ];
    @endphp
    <div class="relative z-10 space-y-2 mb-4">
        @foreach ($promos as $promo)
            <div class="promo-banner rounded-2xl ring-1 p-3 flex items-center gap-3 {{ $promoStyles[$promo->variant] ?? $promoStyles['rail'] }}"
                data-promo="{{ $promo->id }}" hidden>
                @if ($promo->url)
                    <a href="{{ $promo->url }}" target="_blank" rel="noopener" class="flex-1 min-w-0">
                        <p class="font-bold text-sm">{{ $promo->title }}</p>
                        @if ($promo->body)<p class="text-xs mt-0.5 opacity-80">{{ $promo->body }}</p>@endif
                    </a>
                @else
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm">{{ $promo->title }}</p>
                        @if ($promo->body)<p class="text-xs mt-0.5 opacity-80">{{ $promo->body }}</p>@endif
                    </div>
                @endif
                <button type="button" class="promo-dismiss w-7 h-7 grid place-items-center rounded-lg hover:bg-black/5 shrink-0" aria-label="إغلاق">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        @endforeach
    </div>
    <script>
        (() => {
            const KEY = 'qm:promo-dismissed';
            let dismissed = [];
            try { dismissed = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) {}
            document.querySelectorAll('.promo-banner').forEach(el => {
                const id = el.dataset.promo;
                if (dismissed.includes(id)) return;
                el.hidden = false;
                el.querySelector('.promo-dismiss')?.addEventListener('click', () => {
                    el.hidden = true;
                    dismissed.push(id);
                    try { localStorage.setItem(KEY, JSON.stringify(dismissed.slice(-50))); } catch (e) {}
                });
            });
        })();
    </script>
@endif
