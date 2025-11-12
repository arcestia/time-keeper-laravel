<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'energy',
        'food',
        'water',
        'leisure',
        'health',
    ];

    protected $casts = [
        'energy' => 'integer',
        'food' => 'integer',
        'water' => 'integer',
        'leisure' => 'integer',
        'health' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clamp(int $capPercent = 100)
    {
        $cap = max(100, (int)$capPercent);
        $this->energy = max(0, min($cap, (int)$this->energy));
        $this->food = max(0, min($cap, (int)$this->food));
        $this->water = max(0, min($cap, (int)$this->water));
        $this->leisure = max(0, min($cap, (int)$this->leisure));
        $this->health = max(0, min($cap, (int)$this->health));
        return $this;
    }
}
