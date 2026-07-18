<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function create(Request $request)
    {
        // قد يأتي رقم القطار ونوع البلاغ مُمرّرين من صفحة القطار.
        return view('report', [
            'type' => array_key_exists($request->query('type'), Report::TYPES) ? $request->query('type') : 'schedule',
            'trainNumber' => $request->query('train'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(Report::TYPES))],
            'train_number' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:120'],
        ], [], [
            'type' => 'نوع البلاغ',
            'message' => 'وصف المشكلة',
            'contact' => 'وسيلة التواصل',
        ]);

        Report::create($validated);

        return redirect()->route('report')->with('status', 'وصلنا بلاغك، شكرًا لك! هنراجعه ونصلّح المشكلة.');
    }

    /** لوحة المشرف لعرض البلاغات (متاحة لإيميل المشرف فقط عبر middleware admin). */
    public function admin(Request $request)
    {
        $status = $request->query('status');
        $query = Report::query()->latest();
        if (in_array($status, array_keys(Report::STATUSES), true)) {
            $query->where('status', $status);
        }

        return view('reports-admin', [
            'reports' => $query->limit(200)->get(),
            'activeStatus' => $status,
            'counts' => Report::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'total' => Report::count(),
        ]);
    }

    public function updateStatus(Report $report, Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Report::STATUSES))],
        ]);

        $report->update($validated);

        return back()->with('status', 'تم تحديث حالة البلاغ.');
    }
}
