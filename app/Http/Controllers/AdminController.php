<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Report;

class AdminController extends Controller
{
    /** لوحة مشرف موحّدة تجمع روابط الأدوات (خلف رمز المزامنة). */
    public function index(string $token)
    {
        $expected = config('enr.sync_token');
        abort_if(! $expected || ! hash_equals($expected, $token), 404);

        return view('admin', [
            'token' => $token,
            'newReports' => Report::where('status', 'new')->count(),
            'totalReports' => Report::count(),
            'activePromos' => Promo::where('active', true)->count(),
        ]);
    }
}
