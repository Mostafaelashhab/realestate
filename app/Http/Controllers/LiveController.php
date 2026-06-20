<?php

namespace App\Http\Controllers;

use App\Models\Train;
use App\Services\TrainPositionEstimator;

class LiveController extends Controller
{
    public function index(TrainPositionEstimator $estimator)
    {
        // تحميل كل القطارات بمحطاتها دفعة واحدة لتفادي استعلامات كثيرة.
        $trains = Train::with(['stops' => fn ($q) => $q->with('station')])->get();

        $rows = $trains
            ->map(fn (Train $train) => [
                'train' => $train,
                'position' => $estimator->estimate($train),
            ])
            // نعرض القطارات المتحركة أو الواقفة في محطة الآن فقط.
            ->filter(fn ($row) => in_array($row['position']['status'], ['running', 'at_station'], true))
            ->sortByDesc(fn ($row) => $row['position']['overall_progress'])
            ->values();

        return view('live', ['trains' => $rows, 'total' => $trains->count()]);
    }
}
