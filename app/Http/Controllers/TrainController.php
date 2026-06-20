<?php

namespace App\Http\Controllers;

use App\Models\Fare;
use App\Models\Train;
use App\Services\TrainPositionEstimator;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    /** البحث برقم القطار: يوجّه مباشرة لصفحة القطار. */
    public function lookup(Request $request)
    {
        $validated = $request->validate(['number' => ['required', 'string', 'max:20']]);
        $number = trim($validated['number']);

        $train = Train::where('number', $number)->first();

        if (! $train) {
            return redirect()->route('home')->withErrors(['number' => "مفيش قطار برقم {$number}."]);
        }

        return redirect()->route('trains.show', $train);
    }

    public function show(Train $train, TrainPositionEstimator $estimator)
    {
        $train->load(['stops.station']);
        $position = $estimator->estimate($train);

        $origin = $train->stops->first()?->station;
        $terminal = $train->stops->last()?->station;

        // الأسعار الرسمية لكامل مسار القطار (إن وُجدت من الاستيراد).
        $fares = ($origin && $terminal)
            ? Fare::where('train_id', $train->id)
                ->where('from_station_id', $origin->id)
                ->where('to_station_id', $terminal->id)
                ->orderBy('price_piasters')
                ->get()
            : collect();

        return view('trains.show', compact('train', 'position', 'fares', 'origin', 'terminal'));
    }

    /** نقطة JSON لتحديث موقع القطار دوريًا من الواجهة. */
    public function position(Train $train, TrainPositionEstimator $estimator)
    {
        return response()->json($estimator->estimate($train));
    }
}
