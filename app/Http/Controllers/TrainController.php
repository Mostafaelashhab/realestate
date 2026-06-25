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

        // كل محطات القطار اللي ليها كود ENR (لاختيار محطة قيام/وصول مختلفة عند الكراسي).
        $routeStops = $train->stops
            ->filter(fn ($s) => $s->station?->enr_id)
            ->sortBy('stop_order')
            ->map(fn ($s) => [
                'enr' => (string) $s->station->enr_id,
                'name' => $s->station->name_ar,
                'order' => $s->stop_order,
            ])
            ->values();

        // كل أسعار القطار تُكاش مرة واحدة (تتغيّر فقط عند الاستيراد) ونشتق منها.
        $trainFares = \Illuminate\Support\Facades\Cache::remember(
            \App\Support\CacheVer::key('catalog', "train:{$train->id}:fares"),
            now()->addHours(12),
            fn () => Fare::where('train_id', $train->id)->orderBy('price_piasters')->get()
        );

        // الأسعار الرسمية للمسار المعروض.
        $fares = ($origin && $terminal)
            ? $trainFares->where('from_station_id', $origin->id)->where('to_station_id', $terminal->id)->values()
            : collect();

        // سعر التذكرة من كل محطة حتى الوجهة (أرخص درجة) — لعرضه جنب كل محطة في الجدول.
        $stationFares = $terminal
            ? $trainFares->where('to_station_id', $terminal->id)
                ->groupBy('from_station_id')
                ->map(fn ($group) => (int) round($group->first()->price))
            : collect();

        $liveStatus = \App\Models\TrainStatusReport::summaryFor($train->id);

        // تنبيهات المستخدم المفعّلة لهذا القطار/المسار (لإظهار حالة "مفعّل" بدل زر التفعيل).
        $myReminder = null;
        $myStandingAlert = null;
        if ($userId = $request->user()?->id) {
            $myReminder = \App\Models\TrainReminder::where('user_id', $userId)
                ->where('train_id', $train->id)->where('from_station_id', $origin?->id)
                ->where('status', 'active')->first();
            $myStandingAlert = \App\Models\StandingAlert::where('user_id', $userId)
                ->where('train_id', $train->id)->where('from_station_id', $origin?->id)
                ->where('to_station_id', $terminal?->id)->where('status', 'active')->first();
        }

        return view('trains.show', compact(
            'train', 'fares', 'origin', 'terminal', 'scheduleStops', 'validSegment',
            'depart', 'arrive', 'duration', 'boardingAlternatives', 'routeStops', 'stationFares', 'liveStatus',
            'myReminder', 'myStandingAlert'
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
