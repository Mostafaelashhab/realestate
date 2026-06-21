<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Train;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** نقطة دخول البحث (من النموذج) — توجّه للرابط الدائم الصديق للـ SEO. */
    public function index(Request $request)
    {
        // البحث برقم القطار: يوجّه مباشرة لصفحة القطار.
        if (filled($request->input('number'))) {
            $number = trim((string) $request->input('number'));
            $train = Train::where('number', $number)->first();

            return $train
                ? redirect()->route('trains.show', $train)
                : redirect()->route('home')->withErrors(['number' => "مفيش قطار برقم {$number}."]);
        }

        $validated = $request->validate([
            'from' => ['required', 'exists:stations,id'],
            'to' => ['required', 'different:from', 'exists:stations,id'],
            'date' => ['nullable', 'date'],
        ]);

        $from = Station::findOrFail((int) $validated['from']);
        $to = Station::findOrFail((int) $validated['to']);

        // إعادة توجيه (301) للرابط الدائم بالـ slug — رابط واحد قابل للفهرسة والمشاركة.
        return redirect()->route('route', [
            'from' => $from->slug,
            'to' => $to->slug,
            'date' => $request->input('date'),
        ]);
    }
}
