<?php

namespace App\Models;

use App\Services\WebPushSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AppNotification extends Model
{
    protected $fillable = ['user_id', 'icon', 'title', 'body', 'url', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    /**
     * ينبّه متابعي قطر: يسجّل إشعارًا داخل التطبيق + يبعت Web Push لأجهزتهم.
     */
    public static function notifyTrainFollowers(Train $train, string $title, string $body, string $url, ?int $exceptUserId = null): void
    {
        $userIds = TrainFollow::where('train_id', $train->id)
            ->when($exceptUserId, fn ($q) => $q->where('user_id', '!=', $exceptUserId))
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        // إشعارات داخل التطبيق.
        $now = now();
        static::insert($userIds->map(fn ($uid) => [
            'user_id' => $uid, 'icon' => 'train', 'title' => $title, 'body' => $body, 'url' => $url,
            'created_at' => $now, 'updated_at' => $now,
        ])->all());

        // Web Push لأجهزة المتابعين (لو مُهيّأ).
        try {
            $sender = app(WebPushSender::class);
            if ($sender->configured()) {
                $subs = PushSubscription::whereIn('user_id', $userIds)->get();
                if ($subs->isNotEmpty()) {
                    $sender->send($subs, $title, $body, $url);
                }
            }
        } catch (\Throwable $e) {
            // الإشعار الداخلي اتسجّل؛ فشل الـ push مايوقفش العملية.
        }
    }

    /** إشعار لمستخدم واحد (داخل التطبيق + Web Push). */
    public static function notify(int $userId, string $title, string $body, string $url, string $icon = 'bell'): void
    {
        static::create(['user_id' => $userId, 'icon' => $icon, 'title' => $title, 'body' => $body, 'url' => $url]);

        try {
            $sender = app(WebPushSender::class);
            if ($sender->configured()) {
                $subs = PushSubscription::where('user_id', $userId)->get();
                if ($subs->isNotEmpty()) {
                    $sender->send($subs, $title, $body, $url);
                }
            }
        } catch (\Throwable $e) {
            // الإشعار الداخلي اتسجّل؛ فشل الـ push مايوقفش العملية.
        }
    }

    public static function unreadCount(int $userId): int
    {
        return static::where('user_id', $userId)->whereNull('read_at')->count();
    }
}
