<?php

namespace App\Http\Controllers;

use App\Models\Train;
use App\Models\TrainReview;
use Illuminate\Http\Request;

class TrainReviewController extends Controller
{
    /** إضافة/تحديث تقييم المستخدم لقطار. */
    public function store(Request $request, Train $train)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $review = TrainReview::updateOrCreate(
            ['train_id' => $train->id, 'user_id' => $request->user()->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        // نبّه المتابعين بتقييم جديد (مش التعديل).
        if ($review->wasRecentlyCreated) {
            \App\Models\AppNotification::notifyTrainFollowers(
                $train,
                "تقييم جديد لقطار {$train->number}",
                "راكب قيّمه {$review->rating}/5" . ($review->comment ? ": {$review->comment}" : ''),
                route('trains.show', $train) . '#reviews',
                $request->user()->id,
            );
        }

        if ($request->wantsJson()) {
            $ratings = TrainReview::where('train_id', $train->id)->pluck('rating');

            return response()->json([
                'ok' => true,
                'message' => 'شكرًا! رأيك اتسجّل.',
                'avg' => round((float) $ratings->avg(), 1),
                'count' => $ratings->count(),
                'review' => [
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => $request->user()->name,
                    'ago' => $review->created_at->diffForHumans(),
                ],
            ]);
        }

        return redirect()->back()->with('review_ok', 'شكرًا! رأيك اتسجّل.')->withFragment('reviews');
    }
}
