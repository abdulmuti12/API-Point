<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3002',
        'http://localhost:8000',
        'https://72.60.78.152',
        'http://127.0.0.1',
        'https://www.casaitalia-living.com',
        'https://www.homelogystyle.com',
        'https://homelogystyle.com',
        'https://polflexoffice.com',
        'https://api.polflexoffice.com',
        'https://acces.casaitalia-living.com',
        'https://acces.homelogystyle.com',
        'https://acces.polflexoffice.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'Origin',
        'Referer',
        'X-Requested-With',
    ],

    'exposed_headers' => [],
    'max_age' => 3600,

    'supports_credentials' => true,
];
