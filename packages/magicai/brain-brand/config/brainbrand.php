<?php

return [
    'enabled' => env('BRAINBRAND_ENABLED', false),
    'daily_sweep' => [
        'enabled' => env('BRAINBRAND_DAILY_SWEEP', false),
        'schedule' => '0 3 * * *',
    ],
];


