<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Train;
use App\Support\CacheVer;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /** خريطة الموقع: الصفحات الثابتة + القطارات + المحطات + صفحات المسارات (SEO). */
    public function index()
    {
        $urls = Cache::remember(CacheVer::key('catalog', 'sitemap'), now()->addHours(24), function () {
            $urls = [
                ['loc' => route('home'), 'priority' => '1.0', 'freq' => 'daily'],
                ['loc' => route('fines'), 'priority' => '0.4', 'freq' => 'monthly'],
                ['loc' => route('report'), 'priority' => '0.3', 'freq' => 'monthly'],
            ];

            foreach (Train::orderBy('id')->pluck('id') as $id) {
                $urls[] = ['loc' => route('trains.show', $id), 'priority' => '0.7', 'freq' => 'daily'];
            }

            foreach (Station::whereHas('stops')->orderBy('id')->pluck('slug') as $slug) {
                $urls[] = ['loc' => route('stations.show', $slug), 'priority' => '0.6', 'freq' => 'daily'];
            }

            // صفحات المسارات: زوج (القيام، الوصول) لكل قطار + الاتجاه العكسي، بدون تكرار.
            $seen = [];
            Train::with(['stops.station'])->get()->each(function (Train $t) use (&$urls, &$seen) {
                $from = $t->stops->first()?->station;
                $to = $t->stops->last()?->station;
                if (! $from || ! $to || ! $from->slug || ! $to->slug || $from->id === $to->id) {
                    return;
                }
                foreach ([[$from, $to], [$to, $from]] as [$a, $b]) {
                    $key = $a->slug.'|'.$b->slug;
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $urls[] = ['loc' => route('route', ['from' => $a->slug, 'to' => $b->slug]), 'priority' => '0.8', 'freq' => 'daily'];
                }
            });

            return $urls;
        });

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
