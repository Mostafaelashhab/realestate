<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint', 'endpoint_hash', 'p256dh', 'auth', 'train_number', 'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];
}
