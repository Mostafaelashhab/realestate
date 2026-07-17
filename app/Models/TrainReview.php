<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** تقييم/رأي راكب على قطار (نجوم + تعليق) — أساس مجتمع القطر. */
class TrainReview extends Model
{
    protected $fillable = ['train_id', 'user_id', 'rating', 'comment'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
