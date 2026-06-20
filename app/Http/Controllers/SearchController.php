<?php

namespace App\Http\Controllers;

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;
use App\Services\FareCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, FareCalculator $distances)
    {
        // البحث برقم القطار: يتخطّى التحقق من المحطتين ويوجّه لصفحة القطار.
        if (filled($request->input('number'))) {
            $number = trim((string) $request->input('number'));
            $train = Train::where('number', $number)->first();

            return $train
                ? redirect()->route('trains.show', $train)
                : redirect()->route('home')->withErrors(['number' => "مفيش قطار برقم {$number}."]);
        }

        $validated = $request->validate([
            'from' => ['required', 'exists:stations,id'],
            'to' => ['required', 'different:from', 'exists:stations,id'],
            'date' => ['nullable', 'date'],
        ]);

        $fromId = (int) $validated['from'];
        $toId = (int) $validated['to'];
        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : Carbon::today();

        $from = Station::findOrFail($fromId);
        $to = Station::findOrFail($toId);

        $trains = Train::whereHas('stops', fn ($q) => $q->where('station_id', $fromId))
            ->whereHas('stops', fn ($q) => $q->where('station_id', $toId))
            ->with('stops.station')
            ->get();

        // الأسعار الرسمية لهذا المسار، مجمّعة حسب القطار.
        $faresByTrain = Fare::where('from_station_id', $fromId)
            ->where('to_station_id', $toId)
            ->get()
            ->groupBy('train_id');

        $results = $trains
            ->map(function (Train $train) use ($fromId, $toId, $date, $distances, $faresByTrain) {
                $fromStop = $train->stops->firstWhere('station_id', $fromId);
                $toStop = $train->stops->firstWhere('station_id', $toId);

                // لازم تكون محطة القيام قبل محطة الوصول على مسار القطار.
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
                $distance = $officialDistance ?? $distances->distanceKm($fromStop, $toStop);

                return [
                    'train' => $train,
                    'depart' => $depart,
                    'arrive' => $arrive,
                    'duration' => $this->duration($depart, $toStop->arrival_day_offset - $fromStop->departure_day_offset, $arrive),
                    'distance' => $distance !== null ? round($distance) : null,
                    'fares' => $trainFares,
                ];
            })
            ->filter()
            ->sortBy('depart')
            ->values();

        $stations = Station::orderBy('name_ar')->get();

        return view('search', compact('results', 'from', 'to', 'date', 'stations'));
    }

    private function duration(?string $depart, int $dayDiff, ?string $arrive): ?string
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
