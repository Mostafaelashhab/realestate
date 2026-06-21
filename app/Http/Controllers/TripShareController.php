<?php

namespace App\Http\Controllers;

use App\Models\TripShare;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * مشاركة الرحلة لحظيًا: المسافر يبثّ موقع جهازه (GPS)، والأهل يتابعوه عبر رابط عام.
 * - token: رمز عام للمشاهدة (يُشارَك).
 * - owner_token: رمز سري للمالك (لإرسال الموقع والإيقاف).
 */
class TripShareController extends Controller
{
    private const TTL_HOURS = 8;

    public function start(Request $request)
    {
        $data = $request->validate([
            'train_number' => ['nullable', 'string', 'max:20'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'to_name' => ['nullable', 'string', 'max:120'],
            'eta' => ['nullable', 'string', 'max:20'],
            'to_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'to_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $share = TripShare::create([
            ...$data,
            'token' => Str::lower(Str::random(10)),
            'owner_token' => Str::random(40),
            'expires_at' => Carbon::now()->addHours(self::TTL_HOURS),
        ]);

        return response()->json([
            'token' => $share->token,
            'owner_token' => $share->owner_token,
            'url' => route('trip.show', $share->token),
        ]);
    }

    public function ping(Request $request, TripShare $trip)
    {
        $this->authorizeOwner($request, $trip);
        abort_if($trip->isExpired(), 410, 'انتهت المشاركة');

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric'],
        ]);

        $trip->update([
            'last_lat' => $data['lat'],
            'last_lng' => $data['lng'],
            'last_speed' => $data['speed'] ?? null,
            'last_at' => Carbon::now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function stop(Request $request, TripShare $trip)
    {
        $this->authorizeOwner($request, $trip);
        $trip->update(['expires_at' => Carbon::now()]);

        return response()->json(['ok' => true]);
    }

    /** حالة الرحلة للتحديث الدوري من صفحة الأهل (JSON). */
    public function state(TripShare $trip)
    {
        return response()->json([
            'active' => ! $trip->isExpired(),
            'lat' => $trip->last_lat,
            'lng' => $trip->last_lng,
            'speed' => $trip->last_speed,
            'last_at' => $trip->last_at?->toIso8601String(),
            'last_ago' => $trip->last_at?->diffForHumans(),
            'train_number' => $trip->train_number,
            'from_name' => $trip->from_name,
            'to_name' => $trip->to_name,
            'eta' => $trip->eta,
            'to_lat' => $trip->to_lat,
            'to_lng' => $trip->to_lng,
        ]);
    }

    public function show(TripShare $trip)
    {
        return view('trip', ['trip' => $trip]);
    }

    private function authorizeOwner(Request $request, TripShare $trip): void
    {
        $token = $request->input('owner_token');
        abort_if(! $token || ! hash_equals($trip->owner_token, $token), 403);
    }
}
