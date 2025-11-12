<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expedition extends Model
{
    use HasFactory;

    protected $fillable = [
        'level','name','description','min_duration_seconds','max_duration_seconds','cost_seconds','energy_cost_pct'
    ];

    protected $casts = [
        'level' => 'integer',
        'min_duration_seconds' => 'integer',
        'max_duration_seconds' => 'integer',
        'cost_seconds' => 'integer',
        'energy_cost_pct' => 'integer',
    ];
}
