<?php

namespace App\Console\Commands;

use App\Models\StandingAlert;
use App\Services\EnrSeats;
use App\Services\WebPushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** تنبيه الركّاب الواقفين بالمقاعد المتاحة قبل قيام القطار بـ ٥ دقائق (cron كل دقيقة). */
class AlertsScanCommand extends Command
{
    protected $signature = 'alerts:scan';

    protected $description = 'فحص تنبيهات الركّاب الواقفين وإرسال المقاعد المتاحة قبل القيام';

    public function handle(EnrSeats $enrSeats, WebPushSender $sender): int
    {
        if (! $sender->configured()) {
            $this->error('مفاتيح VAPID غير مضبوطة.');

            return self::FAILURE;
        }

        // انتهت مهلتها (فات ميعاد القيام) → expired.
        StandingAlert::where('status', 'active')->where('depart_at', '<', Carbon::now())->update(['status' => 'expired']);

        $due = StandingAlert::where('status', 'active')
            ->whereBetween('depart_at', [Carbon::now(), Carbon::now()->addMinutes(5)])
            ->with(['train', 'fromStation', 'toStation', 'pushSubscription'])
            ->get()
            ->groupBy(fn ($a) => $a->train_id.'-'.$a->from_station_id.'-'.$a->to_station_id);

        $sent = 0;

        foreach ($due as $group) {
            $a = $group->first();
            $body = $this->bodyFor($enrSeats, $a);
            $title = "قطار {$a->train->number} — مقاعد متاحة؟";
            $url = route('trains.show', ['train' => $a->train, 'from' => $a->from_station_id, 'to' => $a->to_station_id]);

            foreach ($group as $alert) {
                // نبعت لكل أجهزة المستخدم (بحسابه)، أو للاشتراك المربوط لو ضيف.
                $targets = $alert->user_id
                    ? \App\Models\PushSubscription::where('user_id', $alert->user_id)->get()
                    : collect();
                if ($targets->isEmpty() && $alert->pushSubscription) {
                    $targets = collect([$alert->pushSubscription]);
                }
                if ($targets->isEmpty()) {
                    continue;
                }
                $r = $sender->send($targets, $title, $body, $url);
                $sent += $r['sent'];
            }

            StandingAlert::whereIn('id', $group->pluck('id'))->update(['status' => 'notified', 'notified_at' => Carbon::now()]);
        }

        $this->info("تنبيهات الركّاب الواقفين: {$sent} إشعار.");

        return self::SUCCESS;
    }

    private function bodyFor(EnrSeats $enrSeats, StandingAlert $a): string
    {
        $fromEnr = $a->fromStation?->enr_id;
        $toEnr = $a->toStation?->enr_id;
        if (! $fromEnr || ! $toEnr) {
            return 'القطار قرّب يقوم من محطتك — افتح صفحة القطار وشوف المقاعد المتاحة.';
        }

        $result = $enrSeats->available((string) $fromEnr, (string) $toEnr, $a->train->number, $a->service_date->toDateString());

        if (! $result['ok']) {
            return 'القطار قرّب يقوم — افتح صفحة القطار وشوف المقاعد المتاحة.';
        }
        if (empty($result['seats'])) {
            return 'كل المقاعد متباعة حاليًا 🤞 — بالتوفيق في رحلتك.';
        }

        $seats = collect($result['seats']);
        $list = $seats->take(4)->map(fn ($s) => "عربة {$s['coach']} مقعد {$s['number']}")->implode(' · ');
        $more = $seats->count() > 4 ? ' +'.($seats->count() - 4).' غيرهم' : '';

        return "مقاعد متاحة دلوقتي: {$list}{$more}";
    }
}
