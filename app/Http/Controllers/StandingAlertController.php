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
                'user_id' => $request->user()?->id,
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

    /** قائمة تنبيهات المستخدم (بحسابه لو مسجّل، وإلا حسب اشتراك الجهاز). */
    public function mine(Request $request)
    {
        $userId = $request->user()?->id;
        if ($userId) {
            $scope = fn ($q) => $q->where('user_id', $userId);
        } else {
            $endpoint = $request->input('endpoint');
            $sub = $endpoint ? \App\Models\PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->first() : null;
            if (! $sub) {
                return response()->json(['seatAlerts' => [], 'reminders' => []]);
            }
            $scope = fn ($q) => $q->where('push_subscription_id', $sub->id);
        }

        $seatAlerts = $scope(StandingAlert::query())
            ->whereIn('status', ['active', 'notified'])
            ->where('depart_at', '>=', Carbon::now()->subDay())
            ->with(['train', 'fromStation', 'toStation'])
            ->orderBy('depart_at')
            ->get()
            ->map(fn (StandingAlert $a) => [
                'id' => $a->id,
                'train' => $a->train?->number,
                'train_id' => $a->train_id,
                'from_id' => $a->from_station_id,
                'to_id' => $a->to_station_id,
                'from' => $a->fromStation?->name_ar,
                'to' => $a->toStation?->name_ar,
                'when' => $a->depart_at->translatedFormat('l j F').' — '.Format::time($a->depart_at),
                'status_label' => $a->status === 'notified' ? 'تم التنبيه' : 'مفعّل',
            ]);

        $reminders = $scope(\App\Models\TrainReminder::query())
            ->where('status', 'active')
            ->with(['train.stops.station', 'fromStation'])
            ->get()
            ->map(function (\App\Models\TrainReminder $r) {
                $fromId = $r->from_station_id ?? $r->train?->stops->first()?->station_id;
                $stop = $r->train?->stops->firstWhere('station_id', $fromId);
                $time = Format::time($stop?->departure_time ?? $stop?->arrival_time);

                return [
                    'id' => $r->id,
                    'train' => $r->train?->number,
                    'train_id' => $r->train_id,
                    'from_id' => $fromId,
                    'from' => $stop?->station?->name_ar,
                    'time' => $time,
                    'lead' => $r->lead_minutes,
                ];
            });

        return response()->json(['seatAlerts' => $seatAlerts, 'reminders' => $reminders]);
    }

    /** إلغاء تنبيه (يملكه المستخدم بحسابه أو بجهازه عبر الـ endpoint). */
    public function cancel(Request $request, StandingAlert $alert)
    {
        abort_unless($this->owns($request, $alert), 403);
        $alert->update(['status' => 'cancelled']);

        return response()->json(['ok' => true]);
    }

    private function owns(Request $request, $model): bool
    {
        $userId = $request->user()?->id;
        if ($userId && $model->user_id === $userId) {
            return true;
        }
        $endpoint = $request->input('endpoint');
        $sub = $model->pushSubscription;

        return $endpoint && $sub && hash_equals($sub->endpoint_hash, hash('sha256', $endpoint));
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
