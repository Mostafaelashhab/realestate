<?php

use App\Services\EgytrainsScraper;
use App\Support\EgyptRailReference;
use App\Support\Format;

it('formats times as 12-hour arabic', function () {
    expect(Format::time('09:05:00'))->toBe('9:05 ص')
        ->and(Format::time('17:30'))->toBe('5:30 م')
        ->and(Format::time('00:15'))->toBe('12:15 ص')
        ->and(Format::time('12:00'))->toBe('12:00 م')
        ->and(Format::time(null))->toBeNull();
});

it('parses a real egytrains train page', function () {
    $html = file_get_contents(base_path('tests/Fixtures/egytrains_train1.html'));

    $data = app(EgytrainsScraper::class)->parseTrainHtml($html);

    expect($data)->not->toBeNull()
        ->and($data['number'])->toBe('1')
        ->and($data['type'])->toBe('روسي')
        ->and($data['working'])->toBeTrue()
        ->and($data['stops'])->toHaveCount(14);

    $first = $data['stops'][0];
    expect($first['name_ar'])->toBe('القاهرة')
        ->and($first['departure'])->toBe('03:00')
        ->and($first['egytrains_id'])->toBe(0)
        ->and($first['day_offset'])->toBe(0);

    // كل المحطات لها ترتيب ومعرّف
    foreach ($data['stops'] as $stop) {
        expect($stop)->toHaveKeys(['egytrains_id', 'name_ar', 'arrival', 'departure', 'day_offset', 'map_x', 'map_y']);
    }
});

it('matches station coords despite hamza spelling variants', function () {
    // egytrains يكتب الأسماء بدون همزة (اسوان/الاسكندريه)
    expect(EgyptRailReference::coordsFor('اسوان'))->not->toBeNull()
        ->and(EgyptRailReference::coordsFor('الاسكندريه'))->not->toBeNull()
        ->and(EgyptRailReference::coordsFor('الاقصر'))->not->toBeNull();
});

it('builds the official booking url from station codes', function () {
    $url = EgyptRailReference::bookingUrl('CAIRO', 'ASWAN', '2026-06-22');
    expect($url)->toContain('from=CAIRO')->toContain('to=ASWAN')->toContain('departure=2026-06-22');

    // بدون رموز: يفتح صفحة الحجز بدون تحديد محطة
    expect(EgyptRailReference::bookingUrl(null, null, '2026-06-22'))
        ->not->toContain('from=');
});

it('maps arabic train types to a type key', function () {
    expect(EgyptRailReference::tariffKey('روسي'))->toBe('روسي')
        ->and(EgyptRailReference::tariffKey('مكيف اسباني'))->toBe('إسباني')
        ->and(EgyptRailReference::tariffKey('قطار غير معروف'))->toBe('مختلط');
});
