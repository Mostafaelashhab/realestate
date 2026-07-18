<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** إدارة العروض/البانرات (متاحة لإيميل المشرف فقط عبر middleware admin). */
class PromoController extends Controller
{
    public function admin()
    {
        return view('promos-admin', [
            'promos' => Promo::orderBy('sort')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'url', 'max:300'],
            'variant' => ['required', Rule::in(array_keys(Promo::VARIANTS))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $validated['active'] = true;
        $validated['sort'] = $validated['sort'] ?? 0;

        Promo::create($validated);

        return back()->with('status', 'تم إضافة العرض.');
    }

    public function toggle(Promo $promo)
    {
        $promo->update(['active' => ! $promo->active]);

        return back()->with('status', 'تم تحديث حالة العرض.');
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();

        return back()->with('status', 'تم حذف العرض.');
    }
}
