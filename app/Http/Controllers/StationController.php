<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Support\CacheVer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StationController extends Controller
{
    /** لوحة مواعيد المحطة: القطارات القايمة منها اليوم بترتيب الوقت. */
    public function show(Station $station)
    {
        $today = Carbon::today();

        // لوحة المحطة شبه ثابتة لليوم — تُكاش لساعة لكل (محطة+يوم).
        $departures = Cache::remember(
            CacheVer::key('catalog', "station:{$station->id}:{$today->toDateString()}"),
            now()->addHour(),
            fn () => $station->stops()
                ->whereNotNull('departure_time')
                ->with(['train.stops.station'])
                ->get()
                ->filter(fn ($stop) => $stop->train && $stop->train->runsOnDay($today->dayOfWeek))
                ->map(function ($stop) {
                    $train = $stop->train;
                    $terminalStop = $train->stops->last();
                    if (! $terminalStop || $terminalStop->station_id === $stop->station_id) {
                        return null;
                    }

                    return [
                        'train' => $train,
                        'departure' => $stop->departure_time,
                        'destination' => $terminalStop->station,
                    ];
                })
                ->filter()
                ->sortBy('departure')
                ->values()
        );

        return view('stations.show', compact('station', 'departures'));
    }
}
