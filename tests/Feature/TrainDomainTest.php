<?php

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;
use App\Models\TrainStop;
use App\Services\FareCalculator;

function makeSampleTrain(): Train
{
    $cai = Station::create(['code' => 'CAI', 'name_ar' => 'القاهرة', 'lat' => 30.0626, 'lng' => 31.2497]);
    $tnt = Station::create(['code' => 'TNT', 'name_ar' => 'طنطا', 'lat' => 30.7865, 'lng' => 31.0004]);
    $alx = Station::create(['code' => 'ALX', 'name_ar' => 'الإسكندرية', 'lat' => 31.1934, 'lng' => 29.9056]);

    $train = Train::create(['number' => 'T1', 'type' => 'spanish', 'name' => 'تجريبي']);

    TrainStop::create(['train_id' => $train->id, 'station_id' => $cai->id, 'stop_order' => 1, 'departure_time' => '09:00']);
    TrainStop::create(['train_id' => $train->id, 'station_id' => $tnt->id, 'stop_order' => 2, 'arrival_time' => '09:55', 'departure_time' => '09:58']);
    TrainStop::create(['train_id' => $train->id, 'station_id' => $alx->id, 'stop_order' => 3, 'arrival_time' => '11:30']);

    return $train->load('stops.station');
}

it('computes haversine distance between two stops', function () {
    $train = makeSampleTrain();
    $from = $train->stops->firstWhere('stop_order', 1);
    $to = $train->stops->firstWhere('stop_order', 3);

    expect(app(FareCalculator::class)->distanceKm($from, $to))->toBeGreaterThan(150); // القاهرة-الإسكندرية
});

it('returns null distance when coordinates are missing', function () {
    $train = makeSampleTrain();
    $train->stops->each(fn ($s) => $s->station->update(['lat' => null, 'lng' => null]));
    $train->load('stops.station');

    $from = $train->stops->firstWhere('stop_order', 1);
    $to = $train->stops->firstWhere('stop_order', 3);

    expect(app(FareCalculator::class)->distanceKm($from, $to))->toBeNull();
});

it('stores official fares in piasters and exposes pounds', function () {
    $train = makeSampleTrain();
    $cai = $train->stops->first()->station;
    $alx = $train->stops->last()->station;

    $fare = Fare::create([
        'train_id' => $train->id,
        'from_station_id' => $cai->id,
        'to_station_id' => $alx->id,
        'class_code' => 'AC1',
        'class_ar' => 'أولى مكيفة',
        'price_piasters' => 9000,
    ]);

    expect($fare->price)->toBe(90.0);
});

it('allows a train to stop at the same station twice', function () {
    $a = Station::create(['egytrains_id' => 100, 'name_ar' => 'محطة أ']);
    $b = Station::create(['egytrains_id' => 101, 'name_ar' => 'محطة ب']);
    $train = Train::create(['number' => 'LOOP', 'type' => 'مختلط']);

    TrainStop::create(['train_id' => $train->id, 'station_id' => $a->id, 'stop_order' => 1, 'departure_time' => '08:00']);
    TrainStop::create(['train_id' => $train->id, 'station_id' => $b->id, 'stop_order' => 2, 'arrival_time' => '08:30', 'departure_time' => '08:35']);
    TrainStop::create(['train_id' => $train->id, 'station_id' => $a->id, 'stop_order' => 3, 'arrival_time' => '09:00']);

    expect($train->stops()->count())->toBe(3);
});
