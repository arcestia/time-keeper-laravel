<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExpedition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','expedition_id','status','purchased_at','started_at','ends_at','duration_seconds','base_xp','loot'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'expedition_id' => 'integer',
        'duration_seconds' => 'integer',
        'base_xp' => 'integer',
        'purchased_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'loot' => 'array',
    ];

    public function expedition()
    {
        return $this->belongsTo(Expedition::class);
    }
}
