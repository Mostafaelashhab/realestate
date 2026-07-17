<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    protected $fillable = [
        'user_id', 'train_id', 'category', 'body',
        'from_station_id', 'to_station_id', 'travel_date', 'price_egp', 'contact',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'price_egp' => 'integer',
    ];

    /** أنواع البوستات في مجتمع الركّاب (بتحدّد شكل البوست في الفيد). */
    public const CATEGORIES = [
        'general' => 'عام',
        'question' => 'سؤال',
        'complaint' => 'شكوى',
        'experience' => 'تجربة',
        'news' => 'خبر',
        'warning' => 'تنبيه',
        'ticket' => 'سوق تذاكر',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(ComplaintComment::class)->latest();
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }

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
