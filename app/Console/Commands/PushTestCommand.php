<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Services\WebPushSender;
use Illuminate\Console\Command;

/** يرسل إشعار تجربة لكل المشتركين (للتأكد من عمل الإشعارات). */
class PushTestCommand extends Command
{
    protected $signature = 'push:test {message=رسالة تجربة من قطارات مصر}';

    protected $description = 'إرسال إشعار تجربة لكل مشتركي الإشعارات';

    public function handle(WebPushSender $sender): int
    {
        if (! $sender->configured()) {
            $this->error('مفاتيح VAPID غير مضبوطة في .env');

            return self::FAILURE;
        }

        $subs = PushSubscription::all();
        $this->info("إرسال إلى {$subs->count()} مشترك…");

        $r = $sender->send($subs, 'قطارات مصر', $this->argument('message'), '/');
        $this->table(['أُرسل', 'فشل', 'محذوف'], [[$r['sent'], $r['failed'], $r['pruned']]]);

        return self::SUCCESS;
    }
}
