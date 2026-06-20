<?php

namespace App\Services;

use App\Models\Fare;
use App\Models\Station;
use App\Models\Train;
use App\Support\EgyptRailReference;
use Illuminate\Support\Facades\DB;

/**
 * يستورد بيانات هيئة السكك الحديدية الرسمية من ردود الـ API (محطات / بحث رحلات).
 * يُستخدم من أمر الـ console ومن نقطة المزامنة عبر المتصفح.
 */
class EnrImporter
{
    /** @return array{linked:int, created:int} */
    public function importStations(array $stations): array
    {
        $byName = [];
        foreach (Station::all() as $s) {
            $byName[EgyptRailReference::normalize($s->name_ar)] ??= $s;
        }

        $created = 0;
        $linked = 0;

        DB::transaction(function () use ($stations, $byName, &$created, &$linked) {
            foreach ($stations as $st) {
                $ar = $st['localizationMap']['ar'] ?? $st['name'] ?? null;
                if (! $ar) {
                    continue;
                }

                $attrs = [
                    'enr_id' => $st['id'] ?? null,
                    'name_en' => $st['name'] ?? null,
                    'booking_name' => $st['name'] ?? null,
                    'station_code' => $st['params']['station_code'] ?? ($st['description'] ?? null),
                ];

                $existing = $byName[EgyptRailReference::normalize($ar)] ?? null;
                if ($existing) {
                    $existing->fill($attrs + ['name_ar' => $ar])->save();
                    $linked++;
                } else {
                    Station::create($attrs + ['name_ar' => $ar]);
                    $created++;
                }
            }
        });

        return ['linked' => $linked, 'created' => $created];
    }

    /** @return array{saved:int, skipped:int, trains:array<int,string>} */
    public function importSearch(array $items): array
    {
        $classMap = $this->buildClassMap($items);

        $saved = 0;
        $skipped = 0;
        $trains = [];

        DB::transaction(function () use ($items, $classMap, &$saved, &$skipped, &$trains) {
            foreach ($items as $item) {
                $step = $item['steps'][0] ?? null;
                if (! $step) {
                    $skipped++;

                    continue;
                }

                $train = Train::where('number', (string) ($step['train']['name'] ?? ''))->first();
                $route = $step['route'] ?? [];
                $fromEnr = $route[0]['id'] ?? null;
                $toEnr = $route[count($route) - 1]['id'] ?? null;

                $from = $fromEnr ? Station::where('enr_id', $fromEnr)->first() : null;
                $to = $toEnr ? Station::where('enr_id', $toEnr)->first() : null;

                if (! $train || ! $from || ! $to) {
                    $skipped++;

                    continue;
                }

                foreach (($item['classesCostMap'] ?? []) as $classId => $priceP) {
                    $class = $classMap[$classId] ?? ['ar' => 'درجة', 'code' => (string) $classId];

                    Fare::updateOrCreate(
                        [
                            'train_id' => $train->id,
                            'from_station_id' => $from->id,
                            'to_station_id' => $to->id,
                            'class_code' => $class['code'],
                        ],
                        [
                            'class_ar' => $class['ar'],
                            'price_piasters' => (int) $priceP,
                            'currency' => $step['currency'] ?? 'EGP',
                            'distance_km' => $step['totalDistance'] ?? null,
                        ]
                    );
                    $saved++;
                }

                $description = $this->trainField($step['train']['fields'] ?? [], 'enr_train_description');
                if ($description) {
                    $train->update(['official_type' => $description]);
                }

                $trains[$train->id] = $train->number;
            }
        });

        return ['saved' => $saved, 'skipped' => $skipped, 'trains' => array_values($trains)];
    }

    /** يكتشف نوع الرد (محطات/بحث) ويستورده. */
    public function importAuto(array $data): array
    {
        $first = $data[0] ?? $data;

        if (isset($first['localizationMap']) || isset($first['shortName'])) {
            return ['type' => 'stations'] + $this->importStations($data);
        }

        if (isset($first['steps'])) {
            return ['type' => 'search'] + $this->importSearch($data);
        }

        return ['type' => 'unknown'];
    }

    private function trainField(array $fields, string $key): ?string
    {
        foreach ($fields as $field) {
            if (($field['key'] ?? null) === $key) {
                return $field['localizationMap']['ar'] ?? null;
            }
        }

        return null;
    }

    /** @return array<string, array{ar:string, code:string}> */
    private function buildClassMap(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            foreach ($item['steps'][0]['train']['servicePoints'] ?? [] as $sp) {
                $cc = $sp['coachClass'] ?? null;
                if ($cc && isset($cc['id'])) {
                    $map[$cc['id']] = [
                        'ar' => $cc['localizationMap']['ar'] ?? $cc['shortName'] ?? 'درجة',
                        'code' => $cc['shortName'] ?? (string) $cc['id'],
                    ];
                }
            }
        }

        return $map;
    }
}
