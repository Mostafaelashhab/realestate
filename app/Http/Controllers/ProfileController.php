<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\TrainReview;
use App\Models\TrainStatusReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** بروفايل الراكب + سمعته (نقاط وشارة) من نشاطه في المجتمع. */
class ProfileController extends Controller
{
    /** عتبات الشارات حسب النقاط. */
    private const BADGES = [
        [0, 'راكب جديد', 'bg-slate-100 text-slate-600'],
        [10, 'راكب نشيط', 'bg-sky-100 text-sky-700'],
        [40, 'راكب خبير', 'bg-violet-100 text-violet-700'],
        [100, 'أسطورة المحطة', 'bg-amber-100 text-amber-700'],
    ];

    public function show(User $user)
    {
        $posts = Complaint::where('user_id', $user->id)->count();
        $comments = ComplaintComment::where('user_id', $user->id)->count();
        $reviews = TrainReview::where('user_id', $user->id)->count();
        $reports = TrainStatusReport::where('user_id', $user->id)->count();
        $likesReceived = DB::table('complaint_likes')
            ->join('complaints', 'complaints.id', '=', 'complaint_likes.complaint_id')
            ->where('complaints.user_id', $user->id)->count();

        // النقاط: مساهمات النقاش والتقييم أعلى وزنًا.
        $points = $posts * 3 + $comments * 1 + $reviews * 3 + $reports * 2 + $likesReceived * 1;

        $badge = self::BADGES[0];
        foreach (self::BADGES as $b) {
            if ($points >= $b[0]) {
                $badge = $b;
            }
        }

        $stats = [
            ['label' => 'بوست', 'value' => $posts],
            ['label' => 'ردّ', 'value' => $comments],
            ['label' => 'تقييم', 'value' => $reviews],
            ['label' => 'بلاغ', 'value' => $reports],
        ];

        $recentPosts = Complaint::where('user_id', $user->id)
            ->withCount(['likers', 'comments'])->with('train:id,number')
            ->latest()->take(10)->get();

        $recentReviews = TrainReview::where('user_id', $user->id)
            ->with('train:id,number')->latest()->take(10)->get();

        return view('profile.show', compact('user', 'points', 'badge', 'stats', 'likesReceived', 'recentPosts', 'recentReviews'));
    }
}
