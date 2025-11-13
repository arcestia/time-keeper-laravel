<?php
return [
    // Back-compat flat requirement (used if xp_progressive is false)
    'xp_per_level' => 120,
    // Progressive XP requirement per level
    'xp_progressive' => true,
    'xp_per_level_base' => 120,        // XP needed for level 1 -> 2
    'xp_per_level_increment' => 6,     // Additional XP added per further level
    'max_level' => 100,
    // Per-level XP bonus applied to expedition rewards
    'xp_bonus_per_level_pct' => 2.0,
    // Extra active expedition slots gained every N levels
    'slots_every_levels' => 5,
    'slots_per_unlock' => 1,
    'max_extra_slots' => 25,
];
