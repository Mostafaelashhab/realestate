<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Station;
use App\Models\Train;
use App\Support\CacheVer;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        // بيانات شبه ثابتة — تُكاش ٢٤ ساعة وتُبطَّل عند تحديث الجداول/الأسعار.
        $stations = Cache::remember(CacheVer::key('catalog', 'home:stations'), now()->addHours(24),
            fn () => Station::orderBy('name_ar')->get());

        $trainCount = Cache::remember(CacheVer::key('catalog', 'home:count'), now()->addHours(24),
            fn () => Train::count());

        $popular = Cache::remember(CacheVer::key('catalog', 'home:popular'), now()->addHours(24), function () use ($stations) {
            $byName = fn (string $n) => $stations->first(fn ($s) => str_contains($s->name_ar, $n));

            return collect([
                ['القاهره', 'الاسكندريه'], ['القاهره', 'اسوان'], ['القاهره', 'الاقصر'],
                ['القاهره', 'المنصوره'], ['القاهره', 'طنطا'], ['الاسكندريه', 'اسوان'],
            ])->map(function ($pair) use ($byName) {
                $from = $byName($pair[0]);
                $to = $byName($pair[1]);

                return ($from && $to) ? ['from' => $from, 'to' => $to] : null;
            })->filter()->values();
        });

        // رحلة مميّزة حقيقية لأشهر مسار (تُعرض في كارت «رحلتك القادمة» افتراضيًا).
        $featured = Cache::remember(CacheVer::key('catalog', 'home:featured'), now()->addHours(24), function () use ($popular) {
            $p = $popular->first();
            if (! $p) {
                return null;
            }
            $fromId = $p['from']->id;
            $toId = $p['to']->id;

            $train = Train::whereHas('stops', fn ($q) => $q->where('station_id', $fromId))
                ->whereHas('stops', fn ($q) => $q->where('station_id', $toId))
                ->with(['stops' => fn ($q) => $q->whereIn('station_id', [$fromId, $toId])])
                ->get()
                ->first(function ($t) use ($fromId, $toId) {
                    $f = $t->stops->firstWhere('station_id', $fromId);
                    $to = $t->stops->firstWhere('station_id', $toId);

                    return $f && $to && $f->stop_order < $to->stop_order;
                });

            if (! $train) {
                return null;
            }

            $f = $train->stops->firstWhere('station_id', $fromId);
            $t = $train->stops->firstWhere('station_id', $toId);
            $depart = $f->departure_time ?? $f->arrival_time;
            $arrive = $t->arrival_time ?? $t->departure_time;
            $dur = null;
            if ($depart && $arrive) {
                $mins = \Illuminate\Support\Carbon::parse($depart)->diffInMinutes(\Illuminate\Support\Carbon::parse($arrive));
                $dur = sprintf('%d س %02d د', intdiv($mins, 60), $mins % 60);
            }

            return [
                'number' => $train->number,
                'from' => $p['from']->name_ar,
                'to' => $p['to']->name_ar,
                'ftime' => \App\Support\Format::time($depart),
                'ttime' => \App\Support\Format::time($arrive),
                'dur' => $dur,
                'url' => route('route', ['from' => $p['from']->slug, 'to' => $p['to']->slug]),
            ];
        });

        // العروض ديناميكية — لا تُكاش طويلًا.
        $promos = Promo::active()->get();

        return view('home', compact('stations', 'trainCount', 'popular', 'promos', 'featured'));
    }
}
