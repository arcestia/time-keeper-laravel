<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class PremiumSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $day = 86400; $hour = 3600; $year = 365 * $day;
        $thresholds = [
            1 => 1 * $hour,
            2 => 1 * $day,
            3 => 3 * $day,
            4 => 7 * $day,
            5 => 14 * $day,
            6 => 30 * $day,
            7 => 60 * $day,
            8 => 90 * $day,
            9 => 180 * $day,
            10 => 270 * $day,
            11 => 365 * $day,
            12 => (int)round(1.5 * 365 * $day),
            13 => 2 * 365 * $day,
            14 => 3 * 365 * $day,
            15 => 4 * 365 * $day,
            16 => 5 * 365 * $day,
            17 => 6 * 365 * $day,
            18 => (int)round(7.5 * 365 * $day),
            19 => 9 * 365 * $day,
            20 => 10 * $year,
        ];
        Setting::set('premium.tiers.thresholds_seconds', $thresholds);

        // Default ratio: 1 premium second costs 3 bank seconds
        Setting::set('premium.price_ratio', [ 'premium' => 1, 'bank' => 3 ]);

        // Minimum premium purchase: 1 hour
        Setting::set('premium.min_purchase_seconds', 3600);
    }
}
