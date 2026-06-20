<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fare extends Model
{
    protected $fillable = [
        'train_id', 'from_station_id', 'to_station_id',
        'class_code', 'class_ar', 'price_piasters', 'currency', 'distance_km',
    ];

    protected $casts = [
        'price_piasters' => 'integer',
        'distance_km' => 'integer',
    ];

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    /** السعر بالجنيه. */
    public function getPriceAttribute(): float
    {
        return $this->price_piasters / 100;
    }
}
