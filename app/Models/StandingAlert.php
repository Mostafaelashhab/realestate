<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandingAlert extends Model
{
    protected $fillable = [
        'user_id', 'train_id', 'from_station_id', 'to_station_id', 'push_subscription_id',
        'service_date', 'depart_at', 'status', 'notified_at',
    ];

    protected $casts = [
        'service_date' => 'date',
        'depart_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }

    public function pushSubscription(): BelongsTo
    {
        return $this->belongsTo(PushSubscription::class);
    }
}
