<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'base_balance_seconds',
        'last_applied_at',
        'drain_rate',
        'is_active',
        'passcode_hash',
        'passcode_set_at',
    ];

    protected $casts = [
        'last_applied_at' => 'datetime',
        'is_active' => 'boolean',
        'drain_rate' => 'decimal:3',
        'passcode_set_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(TimeLedger::class);
    }
}
