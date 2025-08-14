<?php

// Add this to config/services.php

return [
    // ... existing services

    'wompi' => [
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'environment' => env('WOMPI_ENVIRONMENT', 'sandbox'), // sandbox or production
        'webhook_secret' => env('WOMPI_WEBHOOK_SECRET'),
    ],
];