<?php

namespace App\Services;

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** يبني نتائج القطارات بين محطتين — يُستخدم في البحث وصفحات المسارات (SEO). */
class RouteFinder
{
    public function __construct(private FareCalculator $distances) {}

    /** @return Collection<int, array> نتائج مرتّبة بوقت القيام. */
    public function results(Station $from, Station $to, Carbon $date): Collection
    {
        $trains = Train::whereHas('stops', fn ($q) => $q->where('station_id', $from->id))
            ->whereHas('stops', fn ($q) => $q->where('station_id', $to->id))
            ->with('stops.station')
            ->get();

        $faresByTrain = Fare::where('from_station_id', $from->id)
            ->where('to_station_id', $to->id)
            ->get()
            ->groupBy('train_id');

        return $trains->map(function (Train $train) use ($from, $to, $date, $faresByTrain) {
            $fromStop = $train->stops->firstWhere('station_id', $from->id);
            $toStop = $train->stops->firstWhere('station_id', $to->id);

            if (! $fromStop || ! $toStop || $fromStop->stop_order >= $toStop->stop_order) {
                return null;
            }
            if (! $train->runsOnDay($date->dayOfWeek)) {
                return null;
            }

            $depart = $fromStop->departure_time ?? $fromStop->arrival_time;
            $arrive = $toStop->arrival_time ?? $toStop->departure_time;

            $trainFares = ($faresByTrain->get($train->id) ?? collect())
                ->sortBy('price_piasters')
                ->map(fn (Fare $f) => ['label' => $f->class_ar, 'price' => $f->price])
                ->values()
                ->all();

            $officialDistance = optional($faresByTrain->get($train->id)?->first())->distance_km;
            $distance = $officialDistance ?? $this->distances->distanceKm($fromStop, $toStop);

            return [
                'train' => $train,
                'depart' => $depart,
                'arrive' => $arrive,
                'duration' => $this->duration($depart, $toStop->arrival_day_offset - $fromStop->departure_day_offset, $arrive),
                'distance' => $distance !== null ? round($distance) : null,
                'fares' => $trainFares,
            ];
        })->filter()->sortBy('depart')->values();
    }

    /** ملخّص للمسار (لمقدمة صفحة المسار + FAQ + سكيمَا). */
    public function summary(Collection $results): array
    {
        if ($results->isEmpty()) {
            return ['count' => 0, 'first' => null, 'last' => null, 'min_price' => null, 'max_price' => null, 'distance' => null];
        }

        $prices = $results->flatMap(fn ($r) => collect($r['fares'])->pluck('price'))->filter()->values();

        return [
            'count' => $results->count(),
            'first' => $results->first()['depart'],
            'last' => $results->last()['depart'],
            'min_price' => $prices->min(),
            'max_price' => $prices->max(),
            'distance' => $results->pluck('distance')->filter()->max(),
        ];
    }

    public function duration(?string $depart, int $dayDiff, ?string $arrive): ?string
    {
        if (! $depart || ! $arrive) {
            return null;
        }

        $d = Carbon::parse($depart);
        $a = Carbon::parse($arrive)->addDays(max(0, $dayDiff));
        $minutes = $d->diffInMinutes($a);

        return sprintf('%dس %02dد', intdiv($minutes, 60), $minutes % 60);
    }
}
