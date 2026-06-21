<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainReminder extends Model
{
    protected $fillable = [
        'push_subscription_id', 'train_id', 'from_station_id', 'lead_minutes', 'notified_for', 'status',
    ];

    protected $casts = [
        'lead_minutes' => 'integer',
        'notified_for' => 'date',
    ];

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function pushSubscription(): BelongsTo
    {
        return $this->belongsTo(PushSubscription::class);
    }
}
