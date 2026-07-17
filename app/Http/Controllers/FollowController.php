<?php

namespace App\Http\Controllers;

use App\Models\Train;
use App\Models\TrainFollow;
use Illuminate\Http\Request;

/** متابعة القطارات لتلقّي إشعارات نشاطها. */
class FollowController extends Controller
{
    public function toggle(Request $request, Train $train)
    {
        $uid = $request->user()->id;
        $existing = TrainFollow::where('user_id', $uid)->where('train_id', $train->id)->first();

        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            TrainFollow::create(['user_id' => $uid, 'train_id' => $train->id]);
            $following = true;
        }

        return response()->json([
            'ok' => true,
            'following' => $following,
            'count' => TrainFollow::where('train_id', $train->id)->count(),
        ]);
    }
}
