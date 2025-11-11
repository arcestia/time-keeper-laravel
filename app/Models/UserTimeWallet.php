<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTimeWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'available_seconds',
        'last_applied_at',
        'is_active',
        'drain_rate',
    ];

    protected $casts = [
        'last_applied_at' => 'datetime',
        'is_active' => 'boolean',
        'drain_rate' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
