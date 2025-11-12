<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'captured_at',
        'reserve_seconds',
        'total_wallet_seconds',
        'total_bank_seconds',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'reserve_seconds' => 'integer',
        'total_wallet_seconds' => 'integer',
        'total_bank_seconds' => 'integer',
    ];
}
