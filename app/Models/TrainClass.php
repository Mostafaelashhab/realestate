<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainClass extends Model
{
    protected $fillable = [
        'train_id', 'class_key', 'base_fare', 'per_km',
    ];

    protected $casts = [
        'base_fare' => 'float',
        'per_km' => 'float',
    ];

    /** أسماء الدرجات بالعربي. */
    public const LABELS = [
        'first_ac' => 'أولى مكيفة',
        'second_ac' => 'ثانية مكيفة',
        'business' => 'بيزنس',
        'economy' => 'اقتصادي',
        'first' => 'أولى',
        'second' => 'ثانية',
        'third' => 'ثالثة',
        'sleeper' => 'عربات النوم',
    ];

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->class_key] ?? $this->class_key;
    }
}
