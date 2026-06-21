<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\Station;
use App\Models\Train;
use App\Models\TrainReminder;
use App\Support\Format;
use Illuminate\Http\Request;

class TrainReminderController extends Controller
{
    public function store(Request $request, Train $train)
    {
        $train->load('stops.station');

        $data = $request->validate([
            'from_station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'lead_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            'endpoint' => ['required', 'string'],
        ]);

        $sub = PushSubscription::where('endpoint_hash', hash('sha256', $data['endpoint']))->first();
        if (! $sub) {
            return response()->json(['error' => 'لازم تفعّل الإشعارات أولًا.'], 422);
        }

        // محطة الركوب: المختارة أو أول محطة في المسار.
        $fromId = $data['from_station_id'] ?? $train->stops->first()?->station_id;
        $stop = $train->stops->firstWhere('station_id', $fromId);
        if (! $stop || ! ($stop->departure_time ?? $stop->arrival_time)) {
            return response()->json(['error' => 'مفيش ميعاد قيام لهذه المحطة.'], 422);
        }

        TrainReminder::updateOrCreate(
            ['push_subscription_id' => $sub->id, 'train_id' => $train->id, 'from_station_id' => $fromId],
            ['lead_minutes' => $data['lead_minutes'] ?? 60, 'status' => 'active', 'notified_for' => null]
        );

        $time = Format::time($stop->departure_time ?? $stop->arrival_time);

        return response()->json([
            'ok' => true,
            'message' => "هنبّهك قبل قيام القطار من {$stop->station->name_ar} الساعة {$time}.",
        ]);
    }

    public function cancel(Request $request, TrainReminder $reminder)
    {
        $endpoint = $request->input('endpoint');
        $sub = $reminder->pushSubscription;
        abort_if(! $endpoint || ! $sub || ! hash_equals($sub->endpoint_hash, hash('sha256', $endpoint)), 403);

        $reminder->update(['status' => 'cancelled']);

        return response()->json(['ok' => true]);
    }
}
