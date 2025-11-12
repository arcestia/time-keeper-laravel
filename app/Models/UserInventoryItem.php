<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','store_item_id','quantity'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'store_item_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }
}
