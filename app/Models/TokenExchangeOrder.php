<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenExchangeOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','side','color','price_per_unit_seconds','qty_total','qty_filled','status','held_seconds','held_tokens'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'price_per_unit_seconds' => 'integer',
        'qty_total' => 'integer',
        'qty_filled' => 'integer',
        'held_seconds' => 'integer',
        'held_tokens' => 'integer',
    ];

    public function scopeOpen($q){ return $q->whereIn('status',[ 'open','partial' ]); }
}
