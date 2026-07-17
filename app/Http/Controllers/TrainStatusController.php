<?php

namespace App\Http\Controllers;

use App\Models\Train;
use App\Models\TrainStatusReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** بلاغات الركّاب عن حالة القطر (في الموعد / متأخر / ملغي) — مجتمع القطر. */
class TrainStatusController extends Controller
{
    public function store(Request $request, Train $train)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(TrainStatusReport::STATUSES)],
            'delay_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        // بلاغ واحد لكل مستخدم لكل قطر — يُحدَّث بدل ما يتكرر.
        $report = $train->statusReports()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'status' => $data['status'],
                'delay_minutes' => $data['status'] === 'delayed' ? ($data['delay_minutes'] ?? null) : null,
                'note' => $data['note'] ?? null,
            ],
        );
        // نحدّث وقت البلاغ ليُحسب كأحدث بلاغ في نافذة "فين القطر دلوقتي".
        $report->forceFill(['created_at' => now()])->save();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'شكرًا! بلاغك اتسجّل.',
                'live' => TrainStatusReport::summaryFor($train->id),
            ]);
        }

        return redirect()->back()->with('status_ok', 'شكرًا! بلاغك اتسجّل.')->withFragment('status');
    }
}
