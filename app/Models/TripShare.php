<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TripShare extends Model
{
    protected $fillable = [
        'token', 'owner_token', 'train_number', 'from_name', 'to_name', 'eta',
        'to_lat', 'to_lng', 'last_lat', 'last_lng', 'last_speed', 'last_at', 'expires_at',
    ];

    protected $casts = [
        'to_lat' => 'float',
        'to_lng' => 'float',
        'last_lat' => 'float',
        'last_lng' => 'float',
        'last_speed' => 'float',
        'last_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
