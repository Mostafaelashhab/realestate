<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\Train;
use App\Services\WebPushSender;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Console\Command;

/** يذكّر المشتركين قبل ميعاد قيام قطارهم اليوم (يُشغَّل عبر cron كل دقيقة). */
class PushRemindCommand extends Command
{
    protected $signature = 'push:remind {--minutes=60 : كم دقيقة قبل القيام}';

    protected $description = 'إرسال تذكير قبل ميعاد قيام القطار للمشتركين';

    public function handle(WebPushSender $sender): int
    {
        if (! $sender->configured()) {
            $this->error('مفاتيح VAPID غير مضبوطة.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $window = $now->copy()->addMinutes((int) $this->option('minutes'));

        $subs = PushSubscription::whereNotNull('train_number')->get()->groupBy('train_number');
        $totalSent = 0;

        foreach ($subs as $number => $group) {
            $train = Train::with('stops.station')->where('number', $number)->first();
            $origin = $train?->stops->first();
            $dep = $origin?->departure_time;
            if (! $train || ! $dep || ! $train->runsOnDay($now->dayOfWeek)) {
                continue;
            }

            $depAt = $now->copy()->startOfDay()->setTimeFromTimeString($dep);
            if ($depAt->lt($now) || $depAt->gt($window)) {
                continue; // مش داخل نافذة التذكير
            }

            // مش اتبعتله تذكير خلال آخر ٦ ساعات
            $due = $group->filter(fn ($s) => ! $s->notified_at || $s->notified_at->lt($now->copy()->subHours(6)));
            if ($due->isEmpty()) {
                continue;
            }

            $r = $sender->send(
                $due,
                "قطار {$number} قرّب يقوم",
                "القيام من {$origin->station->name_ar} الساعة ".Format::time($dep),
                route('trains.show', $train),
            );
            $totalSent += $r['sent'];
            PushSubscription::whereIn('id', $due->pluck('id'))->update(['notified_at' => $now]);
        }

        $this->info("تم إرسال {$totalSent} تذكير.");

        return self::SUCCESS;
    }
}
