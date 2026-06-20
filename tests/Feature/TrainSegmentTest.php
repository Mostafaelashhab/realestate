<?php

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;
use App\Models\TrainStop;

function segmentTrain(): array
{
    $mansoura = Station::create(['name_ar' => 'المنصورة']);
    $banha = Station::create(['name_ar' => 'بنها']);
    $cairo = Station::create(['name_ar' => 'القاهرة']);
    $train = Train::create(['number' => '948', 'type' => 'مكيف']);

    TrainStop::create(['train_id' => $train->id, 'station_id' => $mansoura->id, 'stop_order' => 1, 'departure_time' => '05:35']);
    TrainStop::create(['train_id' => $train->id, 'station_id' => $banha->id, 'stop_order' => 2, 'arrival_time' => '07:53', 'departure_time' => '08:00']);
    TrainStop::create(['train_id' => $train->id, 'station_id' => $cairo->id, 'stop_order' => 3, 'arrival_time' => '08:45']);

    Fare::create(['train_id' => $train->id, 'from_station_id' => $banha->id, 'to_station_id' => $cairo->id, 'class_code' => 'AC3', 'class_ar' => 'ثالثة مكيفة', 'price_piasters' => 3000]);
    Fare::create(['train_id' => $train->id, 'from_station_id' => $mansoura->id, 'to_station_id' => $cairo->id, 'class_code' => 'AC3', 'class_ar' => 'ثالثة مكيفة', 'price_piasters' => 5000]);

    return compact('train', 'banha', 'cairo');
}

it('shows the searched segment fare, not the full route', function () {
    ['train' => $train, 'banha' => $banha, 'cairo' => $cairo] = segmentTrain();

    $this->get(route('trains.show', ['train' => $train, 'from' => $banha->id, 'to' => $cairo->id]))
        ->assertOk()
        ->assertSee('30 ج.م')   // بنها ← القاهرة
        ->assertDontSee('50 ج.م');
});

it('falls back to the full route fare when no segment is given', function () {
    ['train' => $train] = segmentTrain();

    $this->get(route('trains.show', $train))
        ->assertOk()
        ->assertSee('50 ج.م');  // المنصورة ← القاهرة
});
