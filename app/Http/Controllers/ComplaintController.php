<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Train;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الشكاوى كبوستات — مجتمع الركّاب. */
class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $complaints = Complaint::with(['user:id,name', 'train:id,number'])
            ->withCount('likers')
            ->latest()
            ->paginate(20);

        // البوستات اللي عملها المستخدم إعجاب (لتلوين القلب).
        $likedIds = $request->user()
            ? \Illuminate\Support\Facades\DB::table('complaint_likes')
                ->where('user_id', $request->user()->id)->pluck('complaint_id')->all()
            : [];

        return view('complaints.index', compact('complaints', 'likedIds'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
            'category' => ['nullable', Rule::in(array_keys(Complaint::CATEGORIES))],
            'train_number' => ['nullable', 'string', 'max:20'],
        ]);

        $trainId = null;
        if (! empty($data['train_number'])) {
            $trainId = Train::where('number', trim($data['train_number']))->value('id');
        }

        Complaint::create([
            'user_id' => $request->user()->id,
            'train_id' => $trainId,
            'category' => $data['category'] ?? 'general',
            'body' => $data['body'],
        ]);

        return redirect()->route('complaints.index')->with('ok', 'اتنشرت شكواك، شكرًا لمشاركتك.');
    }

    public function like(Request $request, Complaint $complaint)
    {
        $user = $request->user();
        $existing = $complaint->likers()->where('user_id', $user->id)->exists();

        if ($existing) {
            $complaint->likers()->detach($user->id);
            $liked = false;
        } else {
            $complaint->likers()->attach($user->id);
            $liked = true;
        }

        $count = $complaint->likers()->count();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'liked' => $liked, 'count' => $count]);
        }

        return redirect()->route('complaints.index');
    }
}
