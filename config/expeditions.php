<?php
return [
    'xp_per_level' => 10,
    'xp_per_hour' => 5,
    'xp_per_hour_base' => 10,
    'xp_per_hour_per_level' => 1.2,
    // User-level contribution to expedition XP (medium nerf)
    'xp_per_user_level' => 5,
    'xp_per_hour_per_user_level' => 0.8,
    'time_per_level' => 36,
    'time_per_hour' => 15,
    'variance_min' => 0.9,
    'variance_max' => 1.2,
    // Reward multipliers considering expedition level and costs
    'level_multipliers' => [
        1 => 1.00,
        2 => 1.10,
        3 => 1.25,
        4 => 1.45,
        5 => 1.80,
    ],
    'cost_weight' => 0.00005,       // per second of cost
    'energy_weight' => 0.004,       // per percent energy cost
    'consumable_weight' => 0.03,    // per hour of duration
    // Ensure time rewards are profitable vs cost_seconds
    'time_profit_margin_base' => 0.10, // +10% baseline
    'time_profit_margin_per_level' => 0.03, // +3% per expedition level step
    'time_profit_margin_cap' => 0.50, // cap at +50%
    'qty_per_hour' => 1,
    'qty_max' => 16,
    'level_qty_bands' => [
        1 => [1, 2],
        2 => [2, 3],
        3 => [3, 5],
        4 => [4, 7],
        5 => [5, 9],
    ],
    'multi_item' => [
        1 => ['base' => 1, 'extra' => []],
        2 => ['base' => 1, 'extra' => [[0.30, 1]]],
        3 => ['base' => 1, 'extra' => [[0.40, 1]]],
        4 => ['base' => 1, 'extra' => [[0.50, 1], [0.20, 1]]],
        5 => ['base' => 2, 'extra' => [[0.50, 1]]],
    ],
];
