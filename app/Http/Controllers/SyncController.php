<?php

namespace App\Http\Controllers;

use App\Models\Train;
use App\Services\EnrImporter;
use Illuminate\Http\Request;

/**
 * صفحة مزامنة الأسعار الرسمية (خلف رابط سري). المشرف يضغط على كل قطار،
 * فيتم النداء من متصفحه هو لنظام الهيئة (CORS مسموح) ثم يُرسل الرد هنا للاستيراد.
 */
class SyncController extends Controller
{
    public function index(string $token)
    {
        $this->authorizeToken($token);

        $trains = Train::with(['stops.station'])
            ->withCount('fares')
            ->orderBy('number')
            ->get()
            ->map(function (Train $train) {
                $origin = $train->stops->first()?->station;
                $terminal = $train->stops->last()?->station;

                return [
                    'id' => $train->id,
                    'number' => $train->number,
                    'type' => $train->type_label,
                    'from_enr' => $origin?->enr_id,
                    'to_enr' => $terminal?->enr_id,
                    'from_name' => $origin?->name_ar,
                    'to_name' => $terminal?->name_ar,
                    'has_fares' => $train->fares_count > 0,
                ];
            });

        // محطات لها كود ENR (لاستخدامها في فورم إضافة قطار جديد).
        $stations = \App\Models\Station::whereNotNull('enr_id')->orderBy('name_ar')
            ->get(['id', 'name_ar', 'enr_id']);

        return view('sync', [
            'token' => $token,
            'trains' => $trains,
            'stations' => $stations,
            'searchUrl' => config('enr.search_url'),
        ]);
    }

    public function import(Request $request, string $token, EnrImporter $importer)
    {
        $this->authorizeToken($token);

        $payload = $request->json()->all();
        if (! is_array($payload) || ! isset($payload[0]['steps'])) {
            return response()->json(['error' => 'بيانات غير صالحة'], 422);
        }

        return response()->json($importer->importSearch($payload, allowCreate: true));
    }

    private function authorizeToken(string $token): void
    {
        $expected = config('enr.sync_token');
        abort_if(! $expected || ! hash_equals($expected, $token), 404);
    }
}
