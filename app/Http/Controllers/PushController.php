<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushSender;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request, WebPushSender $sender)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'train_number' => ['nullable', 'string', 'max:20'],
        ]);

        $sub = PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $request->user()?->id,
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'train_number' => $data['train_number'] ?? null,
            ]
        );

        // إشعار ترحيب فوري عند أول اشتراك — تأكيد للمستخدم إن الإشعارات شغّالة.
        // أي فشل في الإرسال مايكسرش حفظ الاشتراك نفسه.
        if ($sub->wasRecentlyCreated) {
            try {
                $sender->send(collect([$sub]), 'تم تفعيل الإشعارات ✓', 'هنبّهك بمواعيد قطارك والمقاعد المتاحة.', '/');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $endpoint = $request->input('endpoint');
        if ($endpoint) {
            PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->delete();
        }

        return response()->json(['ok' => true]);
    }
}
