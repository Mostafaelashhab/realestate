<?php

namespace App\Http\Controllers;

use App\Services\EnrImporter;
use Illuminate\Http\Request;

/**
 * التقاط بيانات الهيئة من متصفّحات المستخدمين: لمّا حد يفتح "التوافر اللحظي"،
 * متصفّحه بينده نظام الهيئة ويرجّع كل البيانات (مواعيد + أسعار + مقاعد)، فنستغلّها
 * لتحديث الأسعار والمواعيد عندنا تلقائيًا — بدون نداء للهيئة من السيرفر.
 */
class EnrSnapshotController extends Controller
{
    public function store(Request $request, EnrImporter $importer)
    {
        $payload = $request->json()->all();

        if (! is_array($payload) || ! isset($payload[0]['steps'][0])) {
            return response()->json(['error' => 'بيانات غير صالحة'], 422);
        }

        $result = $importer->importSearch($payload);

        return response()->json([
            'ok' => true,
            'saved' => $result['saved'],
            'times' => $result['times'],
        ]);
    }
}
