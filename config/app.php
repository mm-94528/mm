<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Modular Scaffold',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] === 'true',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    
    'providers' => [
        // Core providers
    ],
    
    'middleware' => [
        'global' => [
            \App\Core\Middleware\CsrfMiddleware::class,
        ],
        'web' => [
            // Web middleware
        ],
        'api' => [
            // API middleware
        ]
    ]
];