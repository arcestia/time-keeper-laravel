<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expedition;

class ExpeditionsSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            1 => [300, 900, 3600, 5],           // 5-15m, cost 1h, energy 5%
            2 => [1200, 3600, 10800, 8],       // 20m-1h, cost 3h, energy 8%
            3 => [3600, 43200, 43200, 12],     // 1h-12h, cost 12h, energy 12%
            4 => [46800, 172800, 86400, 16],   // 13h-2d, cost 1d, energy 16%
            5 => [172800, 604800, 259200, 20], // 2d-7d, cost 3d, energy 20%
        ];
        foreach ($levels as $level => [$min, $max, $cost, $energy]) {
            for ($i = 1; $i <= 20; $i++) {
                Expedition::updateOrCreate(
                    [ 'level' => $level, 'name' => "Expedition L{$level}-#{$i}" ],
                    [
                        'description' => 'A daring expedition of level ' . $level,
                        'min_duration_seconds' => $min,
                        'max_duration_seconds' => $max,
                        'cost_seconds' => $cost,
                        'energy_cost_pct' => $energy,
                    ]
                );
            }
        }
    }
}
