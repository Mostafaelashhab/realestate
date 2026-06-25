<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** اشتراك "نبّهني أول ما يفضى كرسي" — يراقبه السيرفر ويبعت push عند توفّر مقعد. */
class SeatWatch extends Model
{
    protected $fillable = [
        'user_id', 'train_id', 'train_number', 'from_enr', 'to_enr',
        'from_name', 'to_name', 'service_date', 'status', 'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function train(): BelongsTo
    {
        return $this->belongsTo(Train::class);
    }
}
