<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenExchangeTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'buy_order_id','sell_order_id','color','price_per_unit_seconds','qty','taker_user_id','maker_user_id','fee_seconds'
    ];

    protected $casts = [
        'buy_order_id' => 'integer',
        'sell_order_id' => 'integer',
        'price_per_unit_seconds' => 'integer',
        'qty' => 'integer',
        'taker_user_id' => 'integer',
        'maker_user_id' => 'integer',
        'fee_seconds' => 'integer',
    ];
}
