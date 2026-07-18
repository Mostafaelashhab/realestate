<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /** لوحة مشرف موحّدة تجمع روابط الأدوات (متاحة لإيميل المشرف فقط عبر middleware admin). */
    public function index()
    {
        return view('admin', [
            'newReports' => Report::where('status', 'new')->count(),
            'totalReports' => Report::count(),
            'activePromos' => Promo::where('active', true)->count(),
            'seatsUsers' => User::where('can_see_seats', true)->count(),
        ]);
    }

    /** إدارة المستخدمين — تفعيل/إلغاء إظهار المقاعد لكل حساب. */
    public function users(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $users = User::when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByDesc('can_see_seats')->latest()
            ->paginate(30)->withQueryString();

        return view('admin-users', compact('users', 'q'));
    }

    public function toggleSeats(User $user)
    {
        $user->update(['can_see_seats' => ! $user->can_see_seats]);

        return back()->with('ok', $user->can_see_seats ? "اتفعّلت المقاعد لـ {$user->name}" : "اتلغت المقاعد لـ {$user->name}");
    }
}
