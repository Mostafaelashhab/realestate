<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainFollow extends Model
{
    protected $fillable = ['user_id', 'train_id'];
}
