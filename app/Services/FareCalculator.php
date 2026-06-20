<?php

namespace App\Services;

use App\Models\TrainStop;

class FareCalculator
{
    /** المسافة بالكيلومتر بين محطتين (haversine)، أو null لو الإحداثيات ناقصة. */
    public function distanceKm(TrainStop $from, TrainStop $to): ?float
    {
        $a = $from->station;
        $b = $to->station;

        if ($a?->lat === null || $a?->lng === null || $b?->lat === null || $b?->lng === null) {
            return null;
        }

        $earth = 6371; // نصف قطر الأرض بالكيلومتر
        $dLat = deg2rad($b->lat - $a->lat);
        $dLng = deg2rad($b->lng - $a->lng);

        $h = sin($dLat / 2) ** 2
            + cos(deg2rad($a->lat)) * cos(deg2rad($b->lat)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * asin(min(1, sqrt($h))), 1);
    }
}
