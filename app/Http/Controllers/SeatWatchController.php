<?php

namespace App\Http\Controllers;

use App\Models\SeatWatch;
use App\Models\Train;
use Illuminate\Http\Request;

class SeatWatchController extends Controller
{
    /** إنشاء مراقبة مقاعد (مجانية لأي مستخدم مسجّل). */
    public function store(Request $request, Train $train)
    {
        $user = $request->user();

        $data = $request->validate([
            'from_enr' => ['required', 'string', 'max:40'],
            'to_enr' => ['required', 'string', 'max:40'],
            'from_name' => ['required', 'string', 'max:120'],
            'to_name' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $watch = SeatWatch::firstOrCreate([
            'user_id' => $user->id,
            'train_id' => $train->id,
            'from_enr' => $data['from_enr'],
            'to_enr' => $data['to_enr'],
            'service_date' => $data['date'],
            'status' => 'active',
        ], [
            'train_number' => $train->number,
            'from_name' => $data['from_name'],
            'to_name' => $data['to_name'],
        ]);

        return response()->json(['id' => $watch->id, 'status' => 'active']);
    }

    public function cancel(Request $request, SeatWatch $watch)
    {
        abort_unless($watch->user_id === $request->user()->id, 403);
        $watch->update(['status' => 'cancelled']);

        return response()->json(['status' => 'cancelled']);
    }
}
