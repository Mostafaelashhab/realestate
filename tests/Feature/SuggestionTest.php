<?php

use App\Models\Station;
use App\Models\Train;
use App\Models\TrainStop;
use App\Services\NearestStationSuggester;

function makeDisjointLines(): array
{
    // الخط الأول: القاهرة → بنها → طنطا
    $cai = Station::create(['code' => 'CAI', 'name_ar' => 'القاهرة', 'lat' => 30.0626, 'lng' => 31.2497]);
    $bnh = Station::create(['code' => 'BNH', 'name_ar' => 'بنها']); // بلا إحداثيات عمدًا
    $tnt = Station::create(['code' => 'TNT', 'name_ar' => 'طنطا', 'lat' => 30.7865, 'lng' => 31.0004]);

    // الخط الثاني (منفصل): دمنهور → الإسكندرية
    $dmn = Station::create(['code' => 'DMN', 'name_ar' => 'دمنهور', 'lat' => 31.0341, 'lng' => 30.4682]);
    $alx = Station::create(['code' => 'ALX', 'name_ar' => 'الإسكندرية', 'lat' => 31.1934, 'lng' => 29.9056]);

    $line1 = Train::create(['number' => 'L1', 'type' => 'spanish']);
    TrainStop::create(['train_id' => $line1->id, 'station_id' => $cai->id, 'stop_order' => 1, 'departure_time' => '08:00']);
    TrainStop::create(['train_id' => $line1->id, 'station_id' => $bnh->id, 'stop_order' => 2, 'arrival_time' => '08:40', 'departure_time' => '08:42']);
    TrainStop::create(['train_id' => $line1->id, 'station_id' => $tnt->id, 'stop_order' => 3, 'arrival_time' => '09:20']);

    $line2 = Train::create(['number' => 'L2', 'type' => 'spanish']);
    TrainStop::create(['train_id' => $line2->id, 'station_id' => $dmn->id, 'stop_order' => 1, 'departure_time' => '10:00']);
    TrainStop::create(['train_id' => $line2->id, 'station_id' => $alx->id, 'stop_order' => 2, 'arrival_time' => '10:50']);

    return compact('cai', 'bnh', 'tnt', 'dmn', 'alx');
}

it('suggests the nearest reachable alternatives when there is no direct train', function () {
    ['cai' => $cai, 'tnt' => $tnt, 'dmn' => $dmn, 'alx' => $alx] = makeDisjointLines();

    $s = app(NearestStationSuggester::class)->suggest($cai, $alx);

    // بدائل الوصول: المحطات اللي يوصلها قطار من القاهرة (طنطا) — بنها بلا إحداثيات فتُستبعد من الترتيب.
    $destNames = collect($s['destinations'])->pluck('station.name_ar');
    expect($destNames)->toContain('طنطا')
        ->and($destNames)->not->toContain('الإسكندرية'); // الوصول نفسه مستبعد

    // بدائل القيام: المحطات اللي منها قطار للإسكندرية (دمنهور).
    $originNames = collect($s['origins'])->pluck('station.name_ar');
    expect($originNames)->toContain('دمنهور');

    // المسافة محسوبة لأن المرجع له إحداثيات.
    expect($s['destinations'][0]['distance'])->toBeGreaterThan(0);
});

it('returns empty suggestions when stations are unknown to the network', function () {
    $a = Station::create(['code' => 'AAA', 'name_ar' => 'محطة منعزلة أ']);
    $b = Station::create(['code' => 'BBB', 'name_ar' => 'محطة منعزلة ب']);

    $s = app(NearestStationSuggester::class)->suggest($a, $b);

    expect($s['destinations'])->toBeEmpty()
        ->and($s['origins'])->toBeEmpty();
});
