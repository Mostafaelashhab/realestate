<?php

namespace App\Console\Commands;

use App\Models\Station;
use App\Models\Train;
use App\Models\TrainStop;
use App\Services\EgytrainsScraper;
use App\Support\EgyptRailReference;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScrapeEgytrainsCommand extends Command
{
    protected $signature = 'trains:scrape
        {--limit= : عدد القطارات الأقصى}
        {--only= : أرقام قطارات محددة مفصولة بفاصلة}
        {--sleep=250 : توقف بالملي ثانية بين الطلبات}
        {--fresh : حذف القطارات المسحوبة قبل البدء}';

    protected $description = 'سحب مواعيد ومحطات القطارات من egytrains.com';

    public function handle(EgytrainsScraper $scraper): int
    {
        if ($this->option('fresh')) {
            $this->warn('حذف بيانات القطارات الحالية…');
            Train::query()->delete(); // يحذف المحطات الفرعية والدرجات تلقائيًا (cascade)
            Station::whereNotNull('egytrains_id')->delete();
        }

        if ($only = $this->option('only')) {
            $numbers = array_map('intval', explode(',', $only));
        } else {
            $this->info('جلب قائمة القطارات من خريطة الموقع…');
            $numbers = $scraper->trainNumbers();
        }

        if ($limit = $this->option('limit')) {
            $numbers = array_slice($numbers, 0, (int) $limit);
        }

        if (empty($numbers)) {
            $this->error('تعذّر الحصول على أي أرقام قطارات.');

            return self::FAILURE;
        }

        $this->info('عدد القطارات: '.count($numbers));
        $bar = $this->output->createProgressBar(count($numbers));
        $bar->start();

        $saved = 0;
        $failed = 0;
        $sleep = (int) $this->option('sleep') * 1000;

        foreach ($numbers as $number) {
            $data = $scraper->fetchTrain($number);

            if (! $data || empty($data['stops'])) {
                $failed++;
                $bar->advance();
                usleep($sleep);

                continue;
            }

            try {
                DB::transaction(fn () => $this->saveTrain($number, $data));
                $saved++;
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("قطار {$number}: ".$e->getMessage());
            }

            $bar->advance();
            usleep($sleep);
        }

        $bar->finish();
        $this->newLine(2);

        // إزالة المحطات التي لم تعد مرتبطة بأي قطار.
        $pruned = Station::doesntHave('stops')->delete();

        \App\Support\CacheVer::bump('catalog');

        $this->info("تم الحفظ: {$saved} | فشل/تخطّي: {$failed} | محطات محذوفة: {$pruned}");
        $this->line('المحطات: '.Station::count().' | القطارات: '.Train::count());

        return self::SUCCESS;
    }

    private function saveTrain(int $number, array $data): void
    {
        $train = Train::updateOrCreate(
            ['number' => $data['number'] ?: (string) $number],
            [
                'type' => EgyptRailReference::tariffKey($data['type']),
                'type_ar' => $data['type'] ?: null,
                'active' => $data['working'],
                'source' => 'egytrains.com',
                'source_updated_at' => $this->parseDate($data['last_update']),
            ]
        );

        $train->stops()->delete();

        foreach ($data['stops'] as $order => $stop) {
            $station = $this->resolveStation($stop);

            TrainStop::create([
                'train_id' => $train->id,
                'station_id' => $station->id,
                'stop_order' => $order + 1,
                'arrival_time' => $stop['arrival'],
                'departure_time' => $stop['departure'],
                'arrival_day_offset' => $stop['day_offset'],
                'departure_day_offset' => $stop['day_offset'],
                'map_x' => $stop['map_x'],
                'map_y' => $stop['map_y'],
            ]);
        }

    }

    private function resolveStation(array $stop): Station
    {
        $coords = EgyptRailReference::coordsFor($stop['name_ar']);

        $attributes = ['name_ar' => $stop['name_ar']];
        if ($coords) {
            $attributes['lat'] = $coords[0];
            $attributes['lng'] = $coords[1];
        }

        return Station::updateOrCreate(['egytrains_id' => $stop['egytrains_id']], $attributes);
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
