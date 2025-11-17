<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLifetimeUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','stats_cap_steps','extra_expedition_slots','unlimited_energy'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'stats_cap_steps' => 'integer',
        'extra_expedition_slots' => 'integer',
        'unlimited_energy' => 'boolean',
    ];
}
