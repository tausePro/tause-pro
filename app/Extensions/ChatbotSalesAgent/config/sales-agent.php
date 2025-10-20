<?php

return [
    'enabled' => env('SALES_AGENT_ENABLED', true),
    
    'woocommerce' => [
        'timeout' => 30,
    ],
    
    'wompi' => [
        'environments' => [
            'test' => 'https://sandbox.wompi.co/v1',
            'production' => 'https://production.wompi.co/v1',
        ],
    ],
];

