<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLedger extends Model
{
    use HasFactory;

    protected $table = 'time_ledger';

    protected $fillable = [
        'time_account_id',
        'type',
        'amount_seconds',
        'reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TimeAccount::class, 'time_account_id');
    }
}
