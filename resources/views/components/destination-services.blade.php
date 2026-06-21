@props(['name'])

@php
    $q = urlencode($name);
    $bookingAid = config('affiliate.booking_aid');
    $booking = 'https://www.booking.com/searchresults.ar.html?ss=' . $q . ($bookingAid ? '&aid=' . $bookingAid : '');
    $bus = 'https://go-bus.com/';
    $taxi = 'https://www.uber.com/eg/ar/';

    $links = [
        ['label' => 'فنادق في ' . $name, 'sub' => 'احجز إقامتك', 'icon' => 'pin', 'url' => $booking, 'cls' => 'bg-sky-50 text-sky-700'],
        ['label' => 'أتوبيس بديل', 'sub' => 'لو فاتك القطار', 'icon' => 'arrow-left', 'url' => $bus, 'cls' => 'bg-amber-50 text-amber-700'],
        ['label' => 'تاكسي من المحطة', 'sub' => 'وصلة لبيتك', 'icon' => 'pin', 'url' => $taxi, 'cls' => 'bg-rail-50 text-rail-700'],
    ];
@endphp

<section class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-5 mb-5">
    <h2 class="font-bold mb-1">خدمات في {{ $name }}</h2>
    <p class="text-xs text-slate-400 mb-3">تكمّل رحلتك بعد ما توصل.</p>
    <div class="grid gap-2">
        @foreach ($links as $l)
            <a href="{{ $l['url'] }}" target="_blank" rel="noopener sponsored"
                class="flex items-center gap-3 rounded-2xl ring-1 ring-slate-100 hover:ring-slate-200 p-3 transition">
                <span class="w-10 h-10 grid place-items-center rounded-xl shrink-0 {{ $l['cls'] }}">
                    <x-icon :name="$l['icon']" class="w-5 h-5"/>
                </span>
                <span class="flex-1 min-w-0">
                    <span class="block font-bold text-sm">{{ $l['label'] }}</span>
                    <span class="block text-xs text-slate-500">{{ $l['sub'] }}</span>
                </span>
                <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 shrink-0"/>
            </a>
        @endforeach
    </div>
    <p class="text-[10px] text-slate-300 mt-2">روابط شركاء</p>
</section>
