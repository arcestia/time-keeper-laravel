<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAutoRations extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','food_enabled','water_enabled','food_threshold','water_threshold','access_until','food_item_keys','water_item_keys'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'food_enabled' => 'boolean',
        'water_enabled' => 'boolean',
        'food_threshold' => 'integer',
        'water_threshold' => 'integer',
        'access_until' => 'datetime',
        'food_item_keys' => 'array',
        'water_item_keys' => 'array',
    ];
}
