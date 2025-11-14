<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserXpBoost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bonus_percent',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'bonus_percent' => 'float',
        'expires_at' => 'datetime',
    ];
}
