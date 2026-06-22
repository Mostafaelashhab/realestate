<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    public function configured(): bool
    {
        return (bool) config('push.vapid_public') && (bool) config('push.vapid_private');
    }

    /**
     * يرسل إشعارًا لمجموعة اشتراكات، ويحذف المنتهية تلقائيًا.
     *
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @return array{sent:int, failed:int, pruned:int}
     */
    public function send(Collection $subscriptions, string $title, string $body, string $url = '/'): array
    {
        if (! $this->configured() || $subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'pruned' => 0];
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('push.subject'),
            'publicKey' => config('push.vapid_public'),
            'privateKey' => config('push.vapid_private'),
        ]]);

        $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url], JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $s) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $s->endpoint,
                        'keys' => ['p256dh' => $s->p256dh, 'auth' => $s->auth],
                    ]),
                    $payload
                );
            } catch (\Throwable $e) {
                // اشتراك تالف (مفاتيح غير صالحة) — نتجاهله بدل ما نكسر الدفعة.
                continue;
            }
        }

        $sent = 0;
        $failed = 0;
        $pruneHashes = [];

        foreach ($webPush->flush() as $report) {
            $endpoint = (string) $report->getRequest()->getUri();
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                if ($report->isSubscriptionExpired()) {
                    $pruneHashes[] = hash('sha256', $endpoint);
                }
            }
        }

        if ($pruneHashes) {
            PushSubscription::whereIn('endpoint_hash', $pruneHashes)->delete();
        }

        return ['sent' => $sent, 'failed' => $failed, 'pruned' => count($pruneHashes)];
    }
}
