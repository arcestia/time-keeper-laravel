<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCatalog extends Model
{
    use HasFactory;

    protected $table = 'jobs_catalog';

    protected $fillable = [
        'key', 'name', 'description', 'duration_seconds', 'reward_seconds', 'cooldown_seconds', 'energy_cost', 'is_active'
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'reward_seconds' => 'integer',
        'cooldown_seconds' => 'integer',
        'energy_cost' => 'integer',
        'is_active' => 'boolean',
    ];
}
