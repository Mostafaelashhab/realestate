<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Carbon\Carbon;

class StationController extends Controller
{
    /** لوحة مواعيد المحطة: القطارات القايمة منها اليوم بترتيب الوقت. */
    public function show(Station $station)
    {
        $today = Carbon::today()->dayOfWeek;

        $departures = $station->stops()
            ->whereNotNull('departure_time')
            ->with(['train.stops.station'])
            ->get()
            ->filter(fn ($stop) => $stop->train && $stop->train->runsOnDay($today))
            ->map(function ($stop) {
                $train = $stop->train;
                $terminalStop = $train->stops->last();

                // نتجاهل لو المحطة هي آخر المسار (مفيش وجهة بعدها).
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
            ->values();

        return view('stations.show', compact('station', 'departures'));
    }
}
