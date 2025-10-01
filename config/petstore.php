<?php


return [
    'base_url' => env('PETSTORE_BASE_URL', 'https://petstore.swagger.io/v2'),
    'timeout' => (int) env('PETSTORE_TIMEOUT', 5),
    'retry' => [
        'max' => (int) env('PETSTORE_RETRY_MAX', 2),
        'delay_ms' => (int) env('PETSTORE_RETRY_DELAY_MS', 250),
    ],
];