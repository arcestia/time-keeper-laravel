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

    public function clamp()
    {
        $this->energy = max(0, min(100, (int)$this->energy));
        $this->food = max(0, min(100, (int)$this->food));
        $this->water = max(0, min(100, (int)$this->water));
        $this->leisure = max(0, min(100, (int)$this->leisure));
        $this->health = max(0, min(100, (int)$this->health));
        return $this;
    }
}
