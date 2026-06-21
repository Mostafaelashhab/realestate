<?php

namespace App\Http\Controllers;

use App\Models\Fare;
use App\Models\Train;
use Carbon\Carbon;
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

    public function show(Train $train, Request $request)
    {
        $train->load(['stops.station']);

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

        // ملخّص الرحلة (قيام/وصول/مدة) من أول وآخر محطة في النطاق المعروض.
        $boardStop = $scheduleStops->first();
        $alightStop = $scheduleStops->last();
        $depart = $boardStop?->departure_time ?? $boardStop?->arrival_time;
        $arrive = $alightStop?->arrival_time ?? $alightStop?->departure_time;
        $dayDiff = ($alightStop?->arrival_day_offset ?? 0) - ($boardStop?->departure_day_offset ?? 0);
        $duration = $this->duration($depart, $dayDiff, $arrive);

        // محطات قيام أبعد على نفس القطار (أسبق من محطة الركوب) — تُقترح لو مفيش مقاعد.
        $originStop = $origin ? $train->stops->firstWhere('station_id', $origin->id) : null;
        $boardingAlternatives = $originStop
            ? $train->stops
                ->where('stop_order', '<', $originStop->stop_order)
                ->filter(fn ($s) => $s->station?->enr_id)
                ->sortByDesc('stop_order') // الأقرب فالأبعد
                ->map(fn ($s) => ['enr' => (string) $s->station->enr_id, 'name' => $s->station->name_ar])
                ->values()
            : collect();

        // الأسعار الرسمية لكامل مسار القطار (إن وُجدت من الاستيراد).
        $fares = ($origin && $terminal)
            ? Fare::where('train_id', $train->id)
                ->where('from_station_id', $origin->id)
                ->where('to_station_id', $terminal->id)
                ->orderBy('price_piasters')
                ->get()
            : collect();

        // سعر التذكرة من كل محطة حتى الوجهة (أرخص درجة) — لعرضه جنب كل محطة في الجدول.
        $stationFares = $terminal
            ? Fare::where('train_id', $train->id)
                ->where('to_station_id', $terminal->id)
                ->orderBy('price_piasters')
                ->get()
                ->groupBy('from_station_id')
                ->map(fn ($group) => (int) round($group->first()->price))
            : collect();

        return view('trains.show', compact(
            'train', 'fares', 'origin', 'terminal', 'scheduleStops', 'validSegment',
            'depart', 'arrive', 'duration', 'boardingAlternatives', 'stationFares'
        ));
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
