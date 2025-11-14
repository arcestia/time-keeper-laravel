<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExpeditionUpgradeGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type', // 'permanent' or 'temp'
        'slots',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'slots' => 'integer',
        'expires_at' => 'datetime',
    ];
}
