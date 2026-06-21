<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'type', 'train_number', 'message', 'contact', 'status',
    ];

    public const TYPES = [
        'schedule' => 'ميعاد غلط',
        'price' => 'سعر غلط',
        'other' => 'مشكلة أخرى',
    ];
}
