<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS')),

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
    ],

    'supports_credentials' => false,
];
