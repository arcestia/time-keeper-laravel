<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTimeToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','color','quantity'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'quantity' => 'integer',
    ];
}
