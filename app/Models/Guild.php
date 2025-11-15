<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_user_id',
        'is_locked',
        'is_private',
        'level',
        'xp',
        'total_xp',
        'next_xp',
    ];

    protected $casts = [
        'owner_user_id' => 'integer',
        'is_locked' => 'boolean',
        'is_private' => 'boolean',
        'level' => 'integer',
        'xp' => 'integer',
        'total_xp' => 'integer',
        'next_xp' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GuildMember::class);
    }
}
