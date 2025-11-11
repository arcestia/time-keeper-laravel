<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobCatalog;

class JobsSeeder extends Seeder
{
    public function run(): void
    {
        $jobs = [
            [
                'key' => 'barista_shift',
                'name' => 'Barista Shift',
                'description' => 'Pull espresso shots and serve customers.',
                'duration_seconds' => 600,     // 10m
                'reward_seconds' => 900,       // +15m
                'cooldown_seconds' => 1800,    // 30m
                'energy_cost' => 8,
                'is_active' => true,
            ],
            [
                'key' => 'delivery_run',
                'name' => 'Delivery Run',
                'description' => 'Deliver packages across town.',
                'duration_seconds' => 1200,    // 20m
                'reward_seconds' => 1800,      // +30m
                'cooldown_seconds' => 2700,    // 45m
                'energy_cost' => 12,
                'is_active' => true,
            ],
            [
                'key' => 'software_sprint',
                'name' => 'Software Sprint',
                'description' => 'Fix bugs and push a release.',
                'duration_seconds' => 1800,    // 30m
                'reward_seconds' => 2700,      // +45m
                'cooldown_seconds' => 3600,    // 1h
                'energy_cost' => 15,
                'is_active' => true,
            ],
            [
                'key' => 'night_shift',
                'name' => 'Night Shift',
                'description' => 'Overnight responsibilities with fewer interruptions.',
                'duration_seconds' => 3600,    // 1h
                'reward_seconds' => 5400,      // +1.5h
                'cooldown_seconds' => 7200,    // 2h
                'energy_cost' => 25,
                'is_active' => true,
            ],
            [
                'key' => 'research_study',
                'name' => 'Research Study',
                'description' => 'Participate in a controlled study.',
                'duration_seconds' => 900,     // 15m
                'reward_seconds' => 1500,      // +25m
                'cooldown_seconds' => 3600,    // 1h
                'energy_cost' => 10,
                'is_active' => true,
            ],
            [
                'key' => 'construction_shift',
                'name' => 'Construction Shift',
                'description' => 'Assist with building tasks.',
                'duration_seconds' => 2700,    // 45m
                'reward_seconds' => 3600,      // +1h
                'cooldown_seconds' => 5400,    // 1.5h
                'energy_cost' => 18,
                'is_active' => true,
            ],
            [
                'key' => 'farm_harvest',
                'name' => 'Farm Harvest',
                'description' => 'Harvest crops and load crates.',
                'duration_seconds' => 2400,    // 40m
                'reward_seconds' => 3000,      // +50m
                'cooldown_seconds' => 3600,    // 1h
                'energy_cost' => 16,
                'is_active' => true,
            ],
            [
                'key' => 'security_patrol',
                'name' => 'Security Patrol',
                'description' => 'Patrol designated areas and file a report.',
                'duration_seconds' => 1800,    // 30m
                'reward_seconds' => 2400,      // +40m
                'cooldown_seconds' => 3600,    // 1h
                'energy_cost' => 14,
                'is_active' => true,
            ],
            [
                'key' => 'medical_oncall',
                'name' => 'Medical On-call',
                'description' => 'Assist the medical team during peak hours.',
                'duration_seconds' => 5400,    // 1.5h
                'reward_seconds' => 8100,      // +2h15m
                'cooldown_seconds' => 10800,   // 3h
                'energy_cost' => 30,
                'is_active' => true,
            ],
            [
                'key' => 'creative_gig',
                'name' => 'Creative Gig',
                'description' => 'Design assets for a campaign.',
                'duration_seconds' => 2100,    // 35m
                'reward_seconds' => 3000,      // +50m
                'cooldown_seconds' => 3600,    // 1h
                'energy_cost' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($jobs as $j) {
            JobCatalog::updateOrCreate(['key' => $j['key']], $j);
        }
    }
}
