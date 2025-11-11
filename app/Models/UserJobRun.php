<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserJobRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'job_id', 'started_at', 'completed_at', 'claimed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];
}
