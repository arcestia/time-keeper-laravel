<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'key','name','type','description','price_seconds','quantity','restore_food','restore_water','restore_energy','is_active'
    ];

    protected $casts = [
        'price_seconds' => 'integer',
        'quantity' => 'integer',
        'restore_food' => 'integer',
        'restore_water' => 'integer',
        'restore_energy' => 'integer',
        'is_active' => 'boolean',
    ];
}
