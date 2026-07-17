<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * يجلب المقاعد غير المباعة (المتاحة) من نظام الهيئة لمسار/قطار/يوم — server-side.
 * يُستخدم في تنبيه "الراكب الواقف". مكاش ٩٠ ثانية لتفادي تكرار النداء.
 */
class EnrSeats
{
    /**
     * @return array{ok:bool, seats:array<int,array{coach:string,number:string}>}
     */
    public function available(string $fromEnr, string $toEnr, string $trainNumber, string $date): array
    {
        $key = "seats:{$trainNumber}:{$fromEnr}:{$toEnr}:{$date}";

        return Cache::remember($key, now()->addSeconds(90), function () use ($fromEnr, $toEnr, $trainNumber, $date) {
            try {
                $res = Http::timeout(15)->acceptJson()->get(config('enr.search_url'), [
                    'from' => $fromEnr,
                    'to' => $toEnr,
                    'transfers' => 'false',
                    'with_reservations' => 'true',
                    'without_reservations' => 'false',
                    'skip_places_information' => 'false',
                    'departureDate' => $date,
                    'project' => 'enr',
                    'trainNumber' => $trainNumber,
                ]);

                if (! $res->ok()) {
                    return ['ok' => false, 'seats' => []];
                }

                $seats = [];
                foreach ($res->json() ?? [] as $item) {
                    foreach ($item['steps'][0]['train']['servicePoints'] ?? [] as $sp) {
                        foreach ($sp['places'] ?? [] as $p) {
                            $isSeat = ($p['params']['kind'] ?? 'seat') === 'seat';
                            $free = ($p['available'] ?? false) && empty($p['sold']) && empty($p['locked']);
                            if ($isSeat && $free) {
                                $seats[] = ['coach' => (string) ($sp['name'] ?? '—'), 'number' => (string) ($p['number'] ?? '')];
                            }
                        }
                    }
                }

                return ['ok' => true, 'seats' => $seats];
            } catch (\Throwable $e) {
                return ['ok' => false, 'seats' => []];
            }
        });
    }

    /**
     * أسعار الدرجات الحيّة من نظام الهيئة لقطعة (محطة قيام → نزول) على قطار معيّن.
     * درجة واحدة لكل صنف بأرخص سعر. مكاش ١٠ دقائق.
     *
     * @return array{ok:bool, classes:array<int,array{name:string, price:int}>}
     */
    public function prices(string $fromEnr, string $toEnr, string $trainNumber, string $date): array
    {
        $key = "prices:{$trainNumber}:{$fromEnr}:{$toEnr}:{$date}";

        return Cache::remember($key, now()->addMinutes(10), function () use ($fromEnr, $toEnr, $trainNumber, $date) {
            try {
                $res = Http::timeout(20)->acceptJson()->get(config('enr.search_url'), [
                    'from' => $fromEnr,
                    'to' => $toEnr,
                    'transfers' => 'false',
                    'with_reservations' => 'true',
                    'without_reservations' => 'false',
                    'skip_places_information' => 'true',
                    'departureDate' => $date,
                    'project' => 'enr',
                    'trainNumber' => $trainNumber,
                ]);

                if (! $res->ok()) {
                    return ['ok' => false, 'classes' => []];
                }

                // أرخص سعر لكل درجة عبر كل الرحلات المطابقة + مسافة/مدة الرحلة.
                $byClass = [];
                $distance = null;
                $durationMin = null;
                foreach ($res->json() ?? [] as $item) {
                    $hadClass = false;
                    foreach ($item['steps'][0]['train']['servicePoints'] ?? [] as $sp) {
                        $cost = (int) ($sp['cost'] ?? 0);
                        if ($cost <= 0) {
                            continue;
                        }
                        $name = (string) ($sp['coachClass']['localizationMap']['ar']
                            ?? $sp['coachClass']['shortName'] ?? 'درجة');
                        $price = (int) round($cost / 100);
                        if (! isset($byClass[$name]) || $price < $byClass[$name]) {
                            $byClass[$name] = $price;
                        }
                        $hadClass = true;
                    }
                    // ناخد المسافة/المدة من أول رحلة فيها درجات.
                    if ($hadClass && $distance === null) {
                        $distance = ((int) ($item['distance'] ?? 0)) ?: null;
                        $durationMin = ((int) ($item['duration'] ?? 0)) ?: null;
                    }
                }

                $classes = [];
                foreach ($byClass as $name => $price) {
                    $classes[] = ['name' => $name, 'price' => $price];
                }
                usort($classes, fn ($a, $b) => $a['price'] <=> $b['price']);

                return [
                    'ok' => true,
                    'classes' => $classes,
                    'distance' => $distance,
                    'duration_min' => $durationMin,
                ];
            } catch (\Throwable $e) {
                return ['ok' => false, 'classes' => []];
            }
        });
    }
}
