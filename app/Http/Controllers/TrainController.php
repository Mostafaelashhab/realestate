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

    public function show(Train $train, Request $request, TrainPositionEstimator $estimator)
    {
        $train->load(['stops.station']);
        $position = $estimator->estimate($train);

        // لو جاي من بحث بمحطتين، نعرض سعر نفس الجزء؛ غير كده مسار القطار الكامل.
        $fromStop = $request->filled('from') ? $train->stops->firstWhere('station_id', $request->integer('from')) : null;
        $toStop = $request->filled('to') ? $train->stops->firstWhere('station_id', $request->integer('to')) : null;
        $validSegment = $fromStop && $toStop && $fromStop->stop_order < $toStop->stop_order;

        $origin = $validSegment ? $fromStop->station : $train->stops->first()?->station;
        $terminal = $validSegment ? $toStop->station : $train->stops->last()?->station;

        // جدول المحطات: من محطة الركوب لمحطة النزول لو جاي من بحث، وإلا المسار كامل.
        $scheduleStops = $validSegment
            ? $train->stops->whereBetween('stop_order', [$fromStop->stop_order, $toStop->stop_order])->values()
            : $train->stops;

        // الأسعار الرسمية لكامل مسار القطار (إن وُجدت من الاستيراد).
        $fares = ($origin && $terminal)
            ? Fare::where('train_id', $train->id)
                ->where('from_station_id', $origin->id)
                ->where('to_station_id', $terminal->id)
                ->orderBy('price_piasters')
                ->get()
            : collect();

        return view('trains.show', compact('train', 'position', 'fares', 'origin', 'terminal', 'scheduleStops', 'validSegment'));
    }

    /** نقطة JSON لتحديث موقع القطار دوريًا من الواجهة. */
    public function position(Train $train, TrainPositionEstimator $estimator)
    {
        return response()->json($estimator->estimate($train));
    }
}
