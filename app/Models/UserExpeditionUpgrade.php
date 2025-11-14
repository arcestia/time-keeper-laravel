<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExpeditionUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permanent_slots',
        'temp_slots',
        'temp_expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'permanent_slots' => 'integer',
        'temp_slots' => 'integer',
        'temp_expires_at' => 'datetime',
    ];
}
