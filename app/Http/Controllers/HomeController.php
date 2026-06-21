<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Station;
use App\Models\Train;

class HomeController extends Controller
{
    public function index()
    {
        $stations = Station::orderBy('name_ar')->get();
        $trainCount = Train::count();

        // وجهات شائعة (تُعرض كاختصارات بحث سريعة).
        $byName = fn (string $n) => $stations->first(fn ($s) => str_contains($s->name_ar, $n));
        $popular = collect([
            ['القاهره', 'الاسكندريه'],
            ['القاهره', 'اسوان'],
            ['القاهره', 'الاقصر'],
            ['القاهره', 'المنصوره'],
            ['القاهره', 'طنطا'],
            ['الاسكندريه', 'اسوان'],
        ])->map(function ($pair) use ($byName) {
            $from = $byName($pair[0]);
            $to = $byName($pair[1]);

            return ($from && $to) ? ['from' => $from, 'to' => $to] : null;
        })->filter()->values();

        $promos = Promo::active()->get();

        return view('home', compact('stations', 'trainCount', 'popular', 'promos'));
    }
}
