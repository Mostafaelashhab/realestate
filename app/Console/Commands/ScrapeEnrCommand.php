<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * هيكل مبدئي لجلب بيانات هيئة السكك الحديدية المصرية.
 *
 * ملاحظة مهمّة: نظام الهيئة (o-city / OBS) محجوب خلف تسجيل دخول + كابتشا،
 * ولا يوجد API عام للمواعيد والأسعار. لذلك لا يمكن عمل scraping مباشر
 * وموثوق دون حساب، ونحن لا ننفّذ تخطّي الكابتشا (مخالف لسياسة الموقع وهشّ).
 *
 * الطريقة الموصى بها لتحديث البيانات: استخدم `php artisan trains:import file.json`
 * بعد تجهيز الملف يدويًا أو من مصدر مسموح به. هذا الأمر تُرك كنقطة بداية
 * لو توفّر لاحقًا مصدر/جلسة مصرّح بها.
 */
class ScrapeEnrCommand extends Command
{
    protected $signature = 'trains:scrape-enr';

    protected $description = 'هيكل مبدئي لجلب بيانات الهيئة (يتطلب مصدرًا مصرّحًا به)';

    public function handle(): int
    {
        $this->warn('موقع هيئة السكك الحديدية محجوب خلف تسجيل دخول + كابتشا، ولا يوفّر API عامًا.');
        $this->line('لا يمكن جلب المواعيد/الأسعار تلقائيًا دون حساب مصرّح به.');
        $this->newLine();
        $this->info('البديل: حدّث البيانات عبر ملف JSON ثم:');
        $this->line('  php artisan trains:import database/data/trains.sample.json');
        $this->newLine();
        $this->comment('عند توفّر مصدر/جلسة مصرّح بها، نفّذ منطق الجلب هنا ثم مرّره إلى ImportTrainsCommand.');

        return self::SUCCESS;
    }
}
