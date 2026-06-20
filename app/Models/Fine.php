<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    protected $fillable = [
        'title', 'description', 'amount_label', 'category', 'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];
}
