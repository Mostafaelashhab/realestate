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

    /** أسعار الدرجات الحيّة من نظام الهيئة لقطعة (قيام → نزول) — JSON. */
    public function prices(Request $request, Train $train, \App\Services\EnrSeats $enr)
    {
        $data = $request->validate([
            'from' => ['required', 'string', 'max:40'],
            'to' => ['required', 'string', 'max:40'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $result = $enr->prices(
            $data['from'], $data['to'], (string) $train->number,
            $data['date'] ?? now()->toDateString()
        );

        return response()->json($result);
    }

    /** صفحة اكتشاف: أعلى القطارات تقييمًا من آراء الركّاب. */
    public function top()
    {
        $trains = \Illuminate\Support\Facades\Cache::remember(
            \App\Support\CacheVer::key('catalog', 'trains:top-rated'),
            now()->addHours(6),
            fn () => Train::query()
                ->where('active', true)
                ->whereHas('reviews')
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take(30)
                ->get()
                ->map(fn ($t) => [
                    'number' => (string) $t->number,
                    'type' => $t->type_label,
                    'avg' => round((float) $t->reviews_avg_rating, 1),
                    'count' => (int) $t->reviews_count,
                    'url' => route('trains.show', $t),
                ])
                ->all()
        );

        return view('trains.top', compact('trains'));
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
        $reliability = \App\Models\TrainStatusReport::reliabilityFor($train->id);

        // قطارات تانية على نفس المسار (بتعدّي على محطة القيام قبل محطة الوصول) — للتنقّل والاكتشاف.
        $sameRouteTrains = ($origin && $terminal)
            ? \Illuminate\Support\Facades\Cache::remember(
                \App\Support\CacheVer::key('catalog', "route:{$origin->id}:{$terminal->id}:trains"),
                now()->addHours(6),
                fn () => $this->sameRouteTrains($origin->id, $terminal->id, $train->id)
            )
            : [];

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

        // آراء الركّاب (مجتمع القطر).
        $reviews = \App\Models\TrainReview::where('train_id', $train->id)
            ->with('user:id,name')->latest()->take(20)->get();
        $reviewsAvg = round((float) $reviews->avg('rating'), 1);
        $reviewsCount = $reviews->count();
        $myReview = $request->user()
            ? $reviews->firstWhere('user_id', $request->user()->id)
            : null;

        return view('trains.show', compact(
            'train', 'fares', 'origin', 'terminal', 'scheduleStops', 'validSegment',
            'depart', 'arrive', 'duration', 'boardingAlternatives', 'routeStops', 'stationFares', 'liveStatus',
            'reliability', 'sameRouteTrains',
            'myReminder', 'myStandingAlert', 'reviews', 'reviewsAvg', 'reviewsCount', 'myReview'
        ));
    }

    /**
     * قطارات تعمل على نفس القطعة (origin → terminal) عدا القطر الحالي، مرتّبة بميعاد القيام.
     * تُرجَع كمصفوفات جاهزة للعرض (نكاش المصفوفات لا الموديلات).
     *
     * @return array<int, array{number:string, type:string, depart:?string, arrive:?string, price:?int, url:string}>
     */
    private function sameRouteTrains(int $originId, int $terminalId, int $excludeTrainId): array
    {
        // محطات القطارات عند محطة الوصول (train_id => stop_order).
        $terminalStops = \App\Models\TrainStop::where('station_id', $terminalId)
            ->pluck('stop_order', 'train_id');

        // محطات القطارات عند محطة القيام — نبقّي اللي القيام فيها قبل الوصول.
        $originStops = \App\Models\TrainStop::where('station_id', $originId)
            ->get(['train_id', 'arrival_time', 'departure_time'])
            ->filter(fn ($o) => $o->train_id !== $excludeTrainId
                && isset($terminalStops[$o->train_id]));

        if ($originStops->isEmpty()) {
            return [];
        }

        $trainIds = $originStops->pluck('train_id')->all();

        // القطارات الفعّالة فقط.
        $trains = Train::whereIn('id', $trainIds)->where('active', true)->get()->keyBy('id');

        // وصول كل قطار عند محطة الوصول (train_id => arrival_time).
        $terminalArrivals = \App\Models\TrainStop::where('station_id', $terminalId)
            ->whereIn('train_id', $trainIds)
            ->pluck('arrival_time', 'train_id');

        // أرخص سعر لكل قطار على نفس القطعة.
        $prices = Fare::whereIn('train_id', $trainIds)
            ->where('from_station_id', $originId)->where('to_station_id', $terminalId)
            ->orderBy('price_piasters')->get()
            ->groupBy('train_id')->map(fn ($g) => (int) round($g->first()->price));

        $rows = $originStops
            ->filter(fn ($o) => $trains->has($o->train_id))
            ->map(function ($o) use ($trains, $terminalArrivals, $prices, $originId, $terminalId) {
                $t = $trains[$o->train_id];
                $depRaw = $o->departure_time ?? $o->arrival_time;

                return [
                    'number' => (string) $t->number,
                    'type' => $t->type_label,
                    'depart' => \App\Support\Format::time($depRaw),
                    'depart_sort' => $depRaw ? substr($depRaw, 0, 5) : '99:99',
                    'arrive' => \App\Support\Format::time($terminalArrivals[$o->train_id] ?? null),
                    'price' => $prices[$o->train_id] ?? null,
                    'url' => route('trains.show', $t) . "?from={$originId}&to={$terminalId}",
                ];
            })
            ->sortBy('depart_sort')
            ->take(8)
            ->map(fn ($r) => \Illuminate\Support\Arr::except($r, 'depart_sort'))
            ->values()
            ->all();

        return $rows;
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
