<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// تذكير قبل ميعاد القطار (يتطلّب cron يشغّل: php artisan schedule:run كل دقيقة).
Schedule::command('push:remind')->everyMinute()->withoutOverlapping();
// تنبيه الركّاب الواقفين بالمقاعد المتاحة قبل القيام.
Schedule::command('alerts:scan')->everyMinute()->withoutOverlapping();
// مراقبة المقاعد (Premium): تنبيه أول ما يفضى كرسي.
Schedule::command('seats:watch')->everyThreeMinutes()->withoutOverlapping();
