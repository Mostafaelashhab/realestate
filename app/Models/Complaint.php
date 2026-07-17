<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Complaint extends Model
{
    protected $fillable = ['user_id', 'train_id', 'category', 'body'];

    /** فئات الشكاوى. */
    public const CATEGORIES = [
        'general' => 'عامة',
        'delay' => 'تأخير',
        'cleanliness' => 'نظافة',
        'crowding' => 'زحام',
        'staff' => 'معاملة',
        'other' => 'أخرى',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'complaint_likes')->withTimestamps();
    }
}
