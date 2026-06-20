<?php

namespace App\Console\Commands;

use App\Models\Station;
use App\Models\Train;
use App\Models\TrainClass;
use App\Models\TrainStop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * يستورد بيانات القطارات من ملف JSON.
 * الشكل المتوقّع للملف موضّح في database/data/trains.sample.json.
 */
class ImportTrainsCommand extends Command
{
    protected $signature = 'trains:import {file : مسار ملف JSON}';

    protected $description = 'استيراد المحطات والقطارات والمواعيد والأسعار من ملف JSON';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("الملف غير موجود: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            $this->error('تعذّر قراءة JSON. تأكد من صحة الملف.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($data) {
            foreach ($data['stations'] ?? [] as $s) {
                Station::updateOrCreate(
                    ['code' => $s['code']],
                    [
                        'name_ar' => $s['name_ar'],
                        'name_en' => $s['name_en'] ?? null,
                        'governorate' => $s['governorate'] ?? null,
                        'lat' => $s['lat'] ?? null,
                        'lng' => $s['lng'] ?? null,
                    ]
                );
            }

            foreach ($data['trains'] ?? [] as $t) {
                $train = Train::updateOrCreate(
                    ['number' => $t['number']],
                    [
                        'type' => $t['type'] ?? 'ac',
                        'name' => $t['name'] ?? null,
                        'runs_on' => $t['runs_on'] ?? null,
                        'active' => $t['active'] ?? true,
                    ]
                );

                $train->stops()->delete();
                foreach ($t['stops'] ?? [] as $order => $stop) {
                    $station = Station::where('code', $stop['station'])->first();
                    if (! $station) {
                        $this->warn("محطة غير معروفة: {$stop['station']} — تم تخطّيها.");

                        continue;
                    }

                    TrainStop::create([
                        'train_id' => $train->id,
                        'station_id' => $station->id,
                        'stop_order' => $stop['order'] ?? ($order + 1),
                        'arrival_time' => $stop['arrival'] ?? null,
                        'departure_time' => $stop['departure'] ?? null,
                        'arrival_day_offset' => $stop['arrival_day'] ?? 0,
                        'departure_day_offset' => $stop['departure_day'] ?? ($stop['arrival_day'] ?? 0),
                        'distance_km' => $stop['distance_km'] ?? 0,
                    ]);
                }

                $train->classes()->delete();
                foreach ($t['classes'] ?? [] as $c) {
                    TrainClass::create([
                        'train_id' => $train->id,
                        'class_key' => $c['class_key'],
                        'base_fare' => $c['base_fare'] ?? 0,
                        'per_km' => $c['per_km'] ?? 0,
                    ]);
                }
            }
        });

        $this->info('تم الاستيراد بنجاح.');
        $this->line('المحطات: '.Station::count().' | القطارات: '.Train::count());

        return self::SUCCESS;
    }
}
