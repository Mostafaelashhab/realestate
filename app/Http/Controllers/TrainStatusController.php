<?php

namespace App\Http\Controllers;

use App\Models\Train;
use App\Models\TrainStatusReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainStatusController extends Controller
{
    public function store(Request $request, Train $train)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(TrainStatusReport::STATUSES)],
            'delay_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        // التأخير مطلوب فقط مع حالة "متأخر"
        $train->statusReports()->create([
            'status' => $data['status'],
            'delay_minutes' => $data['status'] === 'delayed' ? ($data['delay_minutes'] ?? null) : null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(TrainStatusReport::summaryFor($train->id) ?? ['count' => 0]);
    }

    public function show(Train $train)
    {
        return response()->json(TrainStatusReport::summaryFor($train->id) ?? ['count' => 0]);
    }
}
