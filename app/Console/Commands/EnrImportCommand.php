<?php

namespace App\Console\Commands;

use App\Services\EnrImporter;
use Illuminate\Console\Command;

/**
 * يستورد بيانات هيئة السكك الحديدية الرسمية من ردود الـ API المحفوظة من المتصفح:
 *  - ملف محطات (serviceTripPoints/stations): أسماء وأكواد رسمية + اسم الحجز.
 *  - ملف بحث (tickets/search): الأسعار والدرجات والنوع الرسمي لمسار محدد.
 *
 * يكتشف نوع كل ملف تلقائيًا من بنيته.
 */
class EnrImportCommand extends Command
{
    protected $signature = 'enr:import {files* : مسارات ملفات JSON}';

    protected $description = 'استيراد المحطات والأسعار الرسمية من ردود نظام الحجز المحفوظة';

    public function handle(EnrImporter $importer): int
    {
        foreach ($this->argument('files') as $path) {
            if (! is_file($path)) {
                $this->error("غير موجود: {$path}");

                continue;
            }

            $data = json_decode((string) file_get_contents($path), true);
            if (! is_array($data)) {
                $this->error("JSON غير صالح: {$path}");

                continue;
            }

            $result = $importer->importAuto($data);

            match ($result['type']) {
                'stations' => $this->info("محطات رسمية: مربوطة {$result['linked']} | جديدة {$result['created']}"),
                'search' => $this->info("أسعار رسمية: محفوظة {$result['saved']} | تخطّي {$result['skipped']}"),
                default => $this->warn("نوع غير معروف: {$path}"),
            };
        }

        return self::SUCCESS;
    }
}
