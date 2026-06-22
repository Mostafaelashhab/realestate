<?php

namespace App\Console\Commands;

use App\Models\TrainReminder;
use App\Services\WebPushSender;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Console\Command;

/** يذكّر المشتركين قبل ميعاد قيام قطارهم (من محطتهم) — يُشغَّل عبر cron كل دقيقة. */
class PushRemindCommand extends Command
{
    protected $signature = 'push:remind';

    protected $description = 'إرسال تذكير قبل ميعاد قيام القطار للمشتركين';

    public function handle(WebPushSender $sender): int
    {
        if (! $sender->configured()) {
            $this->error('مفاتيح VAPID غير مضبوطة.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $today = $now->toDateString();
        $sent = 0;

        $reminders = TrainReminder::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('notified_for')->orWhere('notified_for', '!=', $today))
            ->with(['train.stops.station', 'pushSubscription'])
            ->get();

        foreach ($reminders as $r) {
            $train = $r->train;
            if (! $train || ! $train->runsOnDay($now->dayOfWeek)) {
                continue;
            }

            // نبعت لكل أجهزة المستخدم (بحسابه)، أو للاشتراك المربوط لو ضيف.
            $targets = $r->user_id
                ? \App\Models\PushSubscription::where('user_id', $r->user_id)->get()
                : collect();
            if ($targets->isEmpty() && $r->pushSubscription) {
                $targets = collect([$r->pushSubscription]);
            }
            if ($targets->isEmpty()) {
                continue;
            }

            $fromId = $r->from_station_id ?? $train->stops->first()?->station_id;
            $stop = $train->stops->firstWhere('station_id', $fromId);
            $dep = $stop?->departure_time ?? $stop?->arrival_time;
            if (! $stop || ! $dep) {
                continue;
            }

            $departAt = $now->copy()->startOfDay()->setTimeFromTimeString($dep)->addDays((int) ($stop->departure_day_offset ?? 0));
            $leadStart = $departAt->copy()->subMinutes($r->lead_minutes);

            // داخل نافذة التذكير فقط: [قبل القيام بـ lead_minutes ، حتى القيام]
            if ($now->lt($leadStart) || $now->gt($departAt)) {
                continue;
            }

            $station = $stop->station?->name_ar ?? '';
            $res = $sender->send(
                $targets,
                "قطار {$train->number} قرّب يقوم",
                "القيام من {$station} الساعة ".Format::time($dep),
                route('trains.show', $train),
            );
            $sent += $res['sent'];
            $r->update(['notified_for' => $today]);
        }

        $this->info("تم إرسال {$sent} تذكير.");

        return self::SUCCESS;
    }
}
