<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'side', // 'a' or 'b'
        'type', // 'item_inventory'|'item_storage'|'time_token'|'time_balance'
        'payload', // json
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function trade(): BelongsTo { return $this->belongsTo(Trade::class); }
}
