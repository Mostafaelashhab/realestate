<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    protected $fillable = [
        'egytrains_id', 'enr_id', 'name_ar', 'name_en', 'code', 'station_code',
        'booking_name', 'governorate', 'lat', 'lng',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function stops(): HasMany
    {
        return $this->hasMany(TrainStop::class);
    }

    public function getNameAttribute(): string
    {
        return $this->name_ar;
    }
}
