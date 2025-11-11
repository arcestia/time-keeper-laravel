<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedger extends Model
{
    use HasFactory;

    protected $table = 'wallet_ledger';

    protected $fillable = [
        'user_time_wallet_id',
        'type',
        'amount_seconds',
        'from_seconds',
        'to_seconds',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserTimeWallet::class, 'user_time_wallet_id');
    }
}
