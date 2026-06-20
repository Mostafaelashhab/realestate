<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainStop extends Model
{
    protected $fillable = [
        'train_id', 'station_id', 'stop_order',
        'arrival_time', 'departure_time',
        'arrival_day_offset', 'departure_day_offset', 'distance_km',
        'map_x', 'map_y',
    ];

    protected $casts = [
        'stop_order' => 'integer',
        'arrival_day_offset' => 'integer',
        'departure_day_offset' => 'integer',
        'distance_km' => 'float',
        'map_x' => 'float',
        'map_y' => 'float',
    ];

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
