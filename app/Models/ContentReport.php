<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    protected $fillable = ['user_id', 'complaint_id', 'comment_id', 'reason'];
}
