<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_a_id',
        'user_b_id',
        'status',
        'a_accepted',
        'b_accepted',
        'canceled_by',
        'finalized_at',
        'canceled_at',
        'expires_at',
    ];

    protected $casts = [
        'a_accepted' => 'boolean',
        'b_accepted' => 'boolean',
        'finalized_at' => 'datetime',
        'canceled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function userA(): BelongsTo { return $this->belongsTo(User::class, 'user_a_id'); }
    public function userB(): BelongsTo { return $this->belongsTo(User::class, 'user_b_id'); }
    public function lines(): HasMany { return $this->hasMany(TradeLine::class); }
}
