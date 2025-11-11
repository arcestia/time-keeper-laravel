<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeKeeperReserve extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance_seconds',
    ];
}
