<?php

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;

it('imports official stations and real fares from saved api responses', function () {
    $stationsFile = storage_path('app/enr_stations.json');
    $searchFile = storage_path('app/enr_search_banha_cairo.json');

    if (! is_file($stationsFile) || ! is_file($searchFile)) {
        $this->markTestSkipped('ملفات ENR الرسمية غير موجودة.');
    }

    // قطار 936 موجود في جدولنا (مواعيد من egytrains) قبل استيراد الأسعار.
    $train = Train::create(['number' => '936', 'type' => 'مكيف']);

    $this->artisan('enr:import', ['files' => [$stationsFile]])->assertSuccessful();

    // المحطات الرسمية تحمل اسم الحجز والكود.
    $cairo = Station::where('booking_name', 'CAIRO')->first();
    $banha = Station::where('booking_name', 'BANHA')->first();
    expect($cairo)->not->toBeNull()->and($banha)->not->toBeNull();

    $this->artisan('enr:import', ['files' => [$searchFile]])->assertSuccessful();

    // السعر الرسمي للقطار 936 (بنها ← القاهرة) أولى مكيفة = 90 ج.م
    $fare = Fare::where('train_id', $train->id)
        ->where('from_station_id', $banha->id)
        ->where('to_station_id', $cairo->id)
        ->where('class_ar', 'أولى مكيفة')
        ->first();

    expect($fare)->not->toBeNull()
        ->and($fare->price)->toBe(90.0)
        ->and($fare->distance_km)->toBe(45);
});
