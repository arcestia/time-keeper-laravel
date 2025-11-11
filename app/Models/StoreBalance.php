<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBalance extends Model
{
    protected $table = 'store_balance';

    public $timestamps = false;

    protected $fillable = [
        'balance_seconds',
    ];

    protected $casts = [
        'balance_seconds' => 'integer',
    ];
}
