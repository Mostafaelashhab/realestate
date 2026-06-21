<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Train;
use App\Services\NearestStationSuggester;
use App\Services\RouteFinder;
use App\Support\CacheVer;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** صفحة المسار (SEO): رابط دائم بالـ slug + محتوى مفهرس + FAQ + روابط داخلية. */
class RouteController extends Controller
{
    public function show(Station $from, Station $to, Request $request, RouteFinder $finder, NearestStationSuggester $suggester)
    {
        abort_if($from->id === $to->id, 404);

        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        // نتائج المسار شبه ثابتة (تتغيّر مع الأسعار/الجداول فقط) — تُكاش ساعتين لكل (مسار+يوم).
        $results = Cache::remember(
            CacheVer::key('catalog', "route:{$from->id}:{$to->id}:{$date->toDateString()}"),
            now()->addHours(2),
            fn () => $finder->results($from, $to, $date)
        );
        $summary = $finder->summary($results);
        $suggestions = $results->isEmpty() ? $suggester->suggest($from, $to) : null;
        $stations = Station::orderBy('name_ar')->get();

        $faqs = $this->faqs($from, $to, $summary, $results);
        $related = $this->related($from, $to);
        $onRoute = $this->stationsOnRoute($results, $from, $to);

        return view('route', compact(
            'from', 'to', 'date', 'results', 'summary', 'suggestions', 'stations',
            'faqs', 'related', 'onRoute'
        ));
    }

    /** أسئلة شائعة (تظهر للمستخدم + كـ FAQPage schema). */
    private function faqs(Station $from, Station $to, array $summary, $results): array
    {
        if (! $summary['count']) {
            return [[
                'q' => "هل يوجد قطار مباشر من {$from->name_ar} إلى {$to->name_ar}؟",
                'a' => "لا يوجد حاليًا قطار مباشر بين {$from->name_ar} و{$to->name_ar} ضمن البيانات المتاحة. جرّب أقرب محطة بديلة.",
            ]];
        }

        $durations = $results->pluck('duration')->filter()->values();
        $faqs = [
            ['q' => "كام قطار من {$from->name_ar} إلى {$to->name_ar} في اليوم؟",
                'a' => "يوجد {$summary['count']} قطار يوميًا من {$from->name_ar} إلى {$to->name_ar}."],
            ['q' => "إمتى أول وآخر قطار من {$from->name_ar} إلى {$to->name_ar}؟",
                'a' => 'أول قطار الساعة '.Format::time($summary['first']).' وآخر قطار الساعة '.Format::time($summary['last']).'.'],
        ];

        if ($summary['min_price']) {
            $price = $summary['min_price'] == $summary['max_price']
                ? number_format($summary['min_price']).' ج.م'
                : 'من '.number_format($summary['min_price']).' إلى '.number_format($summary['max_price']).' ج.م';
            $faqs[] = ['q' => "سعر تذكرة القطار من {$from->name_ar} إلى {$to->name_ar}؟",
                'a' => "تبدأ أسعار التذاكر الرسمية {$price} حسب درجة القطار."];
        }
        if ($durations->isNotEmpty()) {
            $faqs[] = ['q' => "مدة رحلة القطار من {$from->name_ar} إلى {$to->name_ar}؟",
                'a' => "مدة الرحلة تقريبًا {$durations->first()}."];
        }

        return $faqs;
    }

    /** روابط داخلية: الاتجاه العكسي + وجهات أخرى من محطة القيام. */
    private function related(Station $from, Station $to): array
    {
        $terminals = Train::whereHas('stops', fn ($q) => $q->where('station_id', $from->id))
            ->with('stops.station')
            ->get()
            ->map(fn ($t) => $t->stops->last()?->station)
            ->filter(fn ($s) => $s && $s->id !== $from->id && $s->id !== $to->id)
            ->unique('id')
            ->take(8)
            ->values();

        return ['reverse' => $to, 'destinations' => $terminals];
    }

    /** المحطات على المسار بين القيام والوصول (لربطها داخليًا). */
    private function stationsOnRoute($results, Station $from, Station $to)
    {
        $train = $results->first()['train'] ?? null;
        if (! $train) {
            return collect();
        }

        $fromOrder = $train->stops->firstWhere('station_id', $from->id)?->stop_order;
        $toOrder = $train->stops->firstWhere('station_id', $to->id)?->stop_order;
        if ($fromOrder === null || $toOrder === null) {
            return collect();
        }

        return $train->stops
            ->filter(fn ($s) => $s->stop_order > $fromOrder && $s->stop_order < $toOrder)
            ->map(fn ($s) => $s->station)
            ->filter()
            ->values();
    }
}
