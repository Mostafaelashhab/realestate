<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketListing extends Model
{
    protected $fillable = [
        'user_id', 'from_station_id', 'to_station_id', 'travel_date',
        'train_number', 'class_ar', 'seats', 'price_egp', 'kind', 'contact', 'note', 'status',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'seats' => 'integer',
        'price_egp' => 'integer',
    ];

    public const KINDS = ['sale' => 'للبيع', 'swap' => 'للتبديل'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'from_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'to_station_id');
    }
}
