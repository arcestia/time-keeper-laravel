<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'steps_count', 'expeditions_completed',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'date' => 'date',
        'steps_count' => 'integer',
        'expeditions_completed' => 'integer',
    ];
}
