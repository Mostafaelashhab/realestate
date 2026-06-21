<?php

namespace App\Services;

use App\Models\Station;
use App\Models\Train;

/**
 * لما مفيش قطار مباشر بين محطتين، نقترح أقرب بديل واقعي:
 * - محطات وصول بديلة: عليها قطار مباشر من محطة القيام، مرتّبة بالأقرب جغرافيًا لمحطة الوصول المطلوبة.
 * - محطات قيام بديلة: منها قطار مباشر لمحطة الوصول، مرتّبة بالأقرب لمحطة القيام المطلوبة.
 * الترتيب بالمسافة (haversine) يحتاج إحداثيات؛ المرشّحون بدون إحداثيات يُستبعدون من الترتيب.
 */
class NearestStationSuggester
{
    private const LIMIT = 4;

    /** @return array{destinations: array<int, array{station: Station, distance: float|null}>, origins: array<int, array{station: Station, distance: float|null}>} */
    public function suggest(Station $from, Station $to): array
    {
        return [
            // محطات وصول بديلة (downstream من القيام) أقرب للوصول المطلوب.
            'destinations' => $this->rank($this->reachableFrom($from), $to, exclude: [$from->id, $to->id]),
            // محطات قيام بديلة (upstream للوصول) أقرب للقيام المطلوب.
            'origins' => $this->rank($this->canReach($to), $from, exclude: [$from->id, $to->id]),
        ];
    }

    /** معرّفات المحطات التي يصلها قطار مباشر بعد محطة القيام. */
    private function reachableFrom(Station $from): array
    {
        $ids = [];

        foreach ($this->trainsThrough($from->id) as $train) {
            $origin = $train->stops->firstWhere('station_id', $from->id);
            foreach ($train->stops as $stop) {
                if ($stop->stop_order > $origin->stop_order) {
                    $ids[$stop->station_id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    /** معرّفات المحطات التي منها قطار مباشر يصل محطة الوصول. */
    private function canReach(Station $to): array
    {
        $ids = [];

        foreach ($this->trainsThrough($to->id) as $train) {
            $terminal = $train->stops->firstWhere('station_id', $to->id);
            foreach ($train->stops as $stop) {
                if ($stop->stop_order < $terminal->stop_order) {
                    $ids[$stop->station_id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private function trainsThrough(int $stationId)
    {
        return Train::whereHas('stops', fn ($q) => $q->where('station_id', $stationId))
            ->with('stops')
            ->get();
    }

    /**
     * يرتّب المرشّحين بالأقرب لمحطة مرجعية. لو المرجع بلا إحداثيات نرجّع أكبر المحطات
     * (التي لها إحداثيات) بدون مسافة كأفضل تخمين.
     *
     * @param  array<int>  $candidateIds
     * @param  array<int>  $exclude
     */
    private function rank(array $candidateIds, Station $reference, array $exclude): array
    {
        $candidateIds = array_values(array_diff($candidateIds, $exclude));
        if (empty($candidateIds)) {
            return [];
        }

        $candidates = Station::whereIn('id', $candidateIds)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        $hasReference = $reference->lat !== null && $reference->lng !== null;

        $ranked = $candidates
            ->map(fn (Station $s) => [
                'station' => $s,
                'distance' => $hasReference ? $this->distanceKm($reference, $s) : null,
            ])
            ->when($hasReference, fn ($c) => $c->sortBy('distance'))
            ->values()
            ->take(self::LIMIT)
            ->all();

        return $ranked;
    }

    /** المسافة بالكيلومتر بين محطتين (haversine). */
    private function distanceKm(Station $a, Station $b): float
    {
        $earth = 6371;
        $dLat = deg2rad($b->lat - $a->lat);
        $dLng = deg2rad($b->lng - $a->lng);

        $h = sin($dLat / 2) ** 2
            + cos(deg2rad($a->lat)) * cos(deg2rad($b->lat)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * asin(min(1, sqrt($h))), 0);
    }
}
