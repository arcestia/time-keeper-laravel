<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premium extends Model
{
    use HasFactory;

    protected $table = 'premiums';

    protected $fillable = [
        'user_id',
        'premium_expires_at',
        'premium_seconds_accumulated',
        'lifetime',
        'weekly_heal_used',
        'weekly_heal_reset_at',
    ];

    protected $casts = [
        'premium_expires_at' => 'datetime',
        'premium_seconds_accumulated' => 'integer',
        'lifetime' => 'boolean',
        'weekly_heal_used' => 'integer',
        'weekly_heal_reset_at' => 'datetime',
    ];
}
