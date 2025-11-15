<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildJoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'guild_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
