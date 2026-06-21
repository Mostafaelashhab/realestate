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

    /** قائمة تنبيهات الجهاز الحالي (حسب اشتراك الإشعارات). */
    public function mine(Request $request)
    {
        $endpoint = $request->input('endpoint');
        $sub = $endpoint ? \App\Models\PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->first() : null;
        if (! $sub) {
            return response()->json(['alerts' => []]);
        }

        $alerts = StandingAlert::where('push_subscription_id', $sub->id)
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
                'status' => $a->status,
                'status_label' => $a->status === 'notified' ? 'تم التنبيه' : 'مفعّل',
            ]);

        return response()->json(['alerts' => $alerts]);
    }

    /** إلغاء تنبيه (يتأكد إنه لنفس الجهاز عبر الـ endpoint). */
    public function cancel(Request $request, StandingAlert $alert)
    {
        $endpoint = $request->input('endpoint');
        $sub = $alert->pushSubscription;
        abort_if(! $endpoint || ! $sub || ! hash_equals($sub->endpoint_hash, hash('sha256', $endpoint)), 403);

        $alert->update(['status' => 'cancelled']);

        return response()->json(['ok' => true]);
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
