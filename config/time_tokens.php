<?php
return [
    // Token values in seconds
    'values' => [
        'red' => 604800,          // 1 week (7 days)
        'blue' => 2592000,        // 1 month (30 days)
        'green' => 31536000,      // 1 year (365 days)
        'yellow' => 315360000,    // 10 years (1 decade)
        'black' => 3153600000,    // 100 years (1 century)
    ],
    // Corresponding store item keys (for inventory/storage)
    'store_item_keys' => [
        'red' => 'token.red',
        'blue' => 'token.blue',
        'green' => 'token.green',
        'yellow' => 'token.yellow',
        'black' => 'token.black',
    ],
];
