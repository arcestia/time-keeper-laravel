<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobCatalog;

class PremiumJobsSeeder extends Seeder
{
    public function run(): void
    {
        $jobs = [
            ['key' => 'pm_runner', 'name' => 'Premium Runner', 'desc' => 'Sprint delivery for VIPs', 'dur' => 900, 'rew' => 2400, 'cd' => 1200, 'energy' => 10],
            ['key' => 'pm_miner', 'name' => 'Premium Miner', 'desc' => 'Deep core mining shift', 'dur' => 1800, 'rew' => 5200, 'cd' => 1800, 'energy' => 15],
            ['key' => 'pm_scientist', 'name' => 'Premium Scientist', 'desc' => 'Research breakthrough session', 'dur' => 2700, 'rew' => 8200, 'cd' => 2400, 'energy' => 20],
            ['key' => 'pm_pilot', 'name' => 'Premium Pilot', 'desc' => 'Shuttle charter flight', 'dur' => 3600, 'rew' => 12000, 'cd' => 3600, 'energy' => 25],
            ['key' => 'pm_architect', 'name' => 'Premium Architect', 'desc' => 'Design elite residence', 'dur' => 5400, 'rew' => 18000, 'cd' => 5400, 'energy' => 30],
            ['key' => 'pm_inventor', 'name' => 'Premium Inventor', 'desc' => 'Prototype a gadget', 'dur' => 4200, 'rew' => 15000, 'cd' => 3600, 'energy' => 28],
            ['key' => 'pm_trader', 'name' => 'Premium Trader', 'desc' => 'High-stakes market deal', 'dur' => 2400, 'rew' => 7000, 'cd' => 2400, 'energy' => 18],
            ['key' => 'pm_conductor', 'name' => 'Premium Conductor', 'desc' => 'Orchestra masterclass', 'dur' => 3000, 'rew' => 9000, 'cd' => 3000, 'energy' => 20],
            ['key' => 'pm_restorer', 'name' => 'Premium Restorer', 'desc' => 'Restore rare artifact', 'dur' => 4800, 'rew' => 16000, 'cd' => 4200, 'energy' => 26],
            ['key' => 'pm_commander', 'name' => 'Premium Commander', 'desc' => 'Lead strategic operation', 'dur' => 6000, 'rew' => 22000, 'cd' => 6000, 'energy' => 35],
        ];

        foreach ($jobs as $j) {
            JobCatalog::updateOrCreate(
                ['key' => $j['key']],
                [
                    'name' => $j['name'],
                    'description' => $j['desc'],
                    'duration_seconds' => $j['dur'],
                    'reward_seconds' => $j['rew'],
                    'cooldown_seconds' => $j['cd'],
                    'energy_cost' => $j['energy'],
                    'is_active' => true,
                    'premium_only' => true,
                ]
            );
        }
    }
}
