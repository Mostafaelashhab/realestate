<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\SeatWatch;
use App\Services\EnrSeats;
use App\Services\WebPushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * مراقبة "نبّهني أول ما يفضى كرسي" (Premium): يفحص الهيئة server-side،
 * ويبعت push أول ما يتوفّر مقعد، مع كتم ٣٠ دقيقة بين التنبيهات لنفس المراقبة.
 */
class SeatsWatchCommand extends Command
{
    protected $signature = 'seats:watch';

    protected $description = 'فحص مراقبات المقاعد (Premium) وإرسال تنبيه عند توفّر مقعد';

    public function handle(EnrSeats $enrSeats, WebPushSender $sender): int
    {
        if (! $sender->configured()) {
            $this->error('مفاتيح VAPID غير مضبوطة.');

            return self::FAILURE;
        }

        // انتهت (فات يومها) → expired.
        SeatWatch::where('status', 'active')
            ->where('service_date', '<', Carbon::today())
            ->update(['status' => 'expired']);

        // المراقبات النشطة لليوم أو الأيام الجاية، مجمّعة بالمسار+اليوم لتفادي تكرار نداء الهيئة.
        $groups = SeatWatch::where('status', 'active')
            ->where('service_date', '>=', Carbon::today())
            ->with('train')
            ->get()
            ->groupBy(fn ($w) => $w->train_number.'|'.$w->from_enr.'|'.$w->to_enr.'|'.$w->service_date->toDateString());

        $sent = 0;

        foreach ($groups as $group) {
            $w = $group->first();
            $result = $enrSeats->available($w->from_enr, $w->to_enr, $w->train_number, $w->service_date->toDateString());

            if (! $result['ok'] || count($result['seats']) === 0) {
                continue; // لسه مكتمل أو تعذّر الجلب
            }

            $count = count($result['seats']);
            $title = "قطار {$w->train_number} — فيه مقاعد فاضية! 🎉";
            $body = "ظهر {$count} مقعد متاح من {$w->from_name} إلى {$w->to_name}. احجز بسرعة.";

            foreach ($group as $watch) {
                // كتم ٣٠ دقيقة بين التنبيهات لنفس المراقبة.
                if ($watch->last_notified_at && $watch->last_notified_at->gt(Carbon::now()->subMinutes(30))) {
                    continue;
                }

                $url = route('trains.show', [
                    'train' => $watch->train_id,
                    'from' => $watch->from_enr,
                    'to' => $watch->to_enr,
                    'date' => $watch->service_date->toDateString(),
                ]).'#live';

                $targets = PushSubscription::where('user_id', $watch->user_id)->get();
                if ($targets->isEmpty()) {
                    continue;
                }

                $r = $sender->send($targets, $title, $body, $url);
                $sent += $r['sent'];
                $watch->update(['last_notified_at' => Carbon::now()]);
            }
        }

        $this->info("seats:watch — sent {$sent}");

        return self::SUCCESS;
    }
}
