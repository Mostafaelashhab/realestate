<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $uid = $request->user()->id;
        $notifications = AppNotification::where('user_id', $uid)->latest()->paginate(30);

        // تعليم الكل كمقروء بعد الفتح.
        AppNotification::where('user_id', $uid)->whereNull('read_at')->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    public function unread(Request $request)
    {
        return response()->json(['count' => AppNotification::unreadCount($request->user()->id)]);
    }
}
