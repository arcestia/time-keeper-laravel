<?php
return [
    'xp_per_level' => 12,
    'xp_per_hour' => 6,
    'xp_per_hour_base' => 12,
    'xp_per_hour_per_level' => 1.5,
    'time_per_level' => 36,
    'time_per_hour' => 15,
    'variance_min' => 0.9,
    'variance_max' => 1.2,
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
