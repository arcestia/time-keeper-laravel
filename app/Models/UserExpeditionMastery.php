<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExpeditionMastery extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','level','xp','total_xp'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'level' => 'integer',
        'xp' => 'integer',
        'total_xp' => 'integer',
    ];
}
