<?php

return [
    'default_provider' => env('NEURON_DEFAULT_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4'),
        ],
        'anthropic' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet'),
        ],
    ],

    'inspector' => [
        'enabled' => env('INSPECTOR_ENABLED', false),
        'ingestion_key' => env('INSPECTOR_INGESTION_KEY'),
    ],

    'woocommerce' => [
        'url' => env('WOOCOMMERCE_URL'),
        'key' => env('WOOCOMMERCE_KEY'),
        'secret' => env('WOOCOMMERCE_SECRET'),
        'timeout' => 30,
    ],

    'wompi' => [
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'environment' => env('WOMPI_ENVIRONMENT', 'test'),
        'environments' => [
            'test' => 'https://sandbox.wompi.co/v1',
            'production' => 'https://production.wompi.co/v1',
        ],
    ],
];
