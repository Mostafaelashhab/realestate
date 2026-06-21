<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\StandingAlert;
use App\Models\Station;
use App\Models\Train;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StandingAlertController extends Controller
{
    public function store(Request $request, Train $train)
    {
        $train->load('stops');

        $data = $request->validate([
            'from_station_id' => ['required', 'integer', 'exists:stations,id'],
            'to_station_id' => ['required', 'integer', 'different:from_station_id', 'exists:stations,id'],
            'endpoint' => ['required', 'string'],
        ]);

        $fromStop = $train->stops->firstWhere('station_id', (int) $data['from_station_id']);
        $toStop = $train->stops->firstWhere('station_id', (int) $data['to_station_id']);

        if (! $fromStop || ! $toStop || $fromStop->stop_order >= $toStop->stop_order) {
            return response()->json(['error' => 'المحطتان غير صحيحتين على هذا القطار.'], 422);
        }

        $sub = PushSubscription::where('endpoint_hash', hash('sha256', $data['endpoint']))->first();
        if (! $sub) {
            return response()->json(['error' => 'لازم تفعّل الإشعارات أولًا.'], 422);
        }

        // أقرب قيام قادم من محطة الركوب (اليوم أو أقرب يوم يعمل فيه القطار).
        $dep = $fromStop->departure_time ?? $fromStop->arrival_time;
        if (! $dep) {
            return response()->json(['error' => 'لا يوجد ميعاد قيام لهذه المحطة.'], 422);
        }

        [$serviceDate, $departAt] = $this->nextDeparture($train, $dep, (int) ($fromStop->departure_day_offset ?? 0));
        if (! $departAt) {
            return response()->json(['error' => 'مفيش رحلة قادمة قريبة لهذا القطار.'], 422);
        }

        StandingAlert::updateOrCreate(
            [
                'train_id' => $train->id,
                'from_station_id' => $data['from_station_id'],
                'push_subscription_id' => $sub->id,
                'service_date' => $serviceDate->toDateString(),
            ],
            [
                'to_station_id' => $data['to_station_id'],
                'depart_at' => $departAt,
                'status' => 'active',
                'notified_at' => null,
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'هنبّهك بالمقاعد المتاحة قبل قيام القطار من محطتك الساعة '.Format::time($dep).'.',
        ]);
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} [service_date, depart_at] */
    private function nextDeparture(Train $train, string $time, int $dayOffset): array
    {
        $now = Carbon::now();
        for ($i = 0; $i < 7; $i++) {
            $day = $now->copy()->startOfDay()->addDays($i);
            if (! $train->runsOnDay($day->dayOfWeek)) {
                continue;
            }
            $departAt = $day->copy()->setTimeFromTimeString($time)->addDays($dayOffset);
            if ($departAt->gt($now)) {
                return [$day, $departAt];
            }
        }

        return [null, null];
    }
}
