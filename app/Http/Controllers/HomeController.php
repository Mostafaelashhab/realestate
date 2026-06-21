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

        // العروض ديناميكية — لا تُكاش طويلًا.
        $promos = Promo::active()->get();

        return view('home', compact('stations', 'trainCount', 'popular', 'promos'));
    }
}
