<?php

return [
    'google' => [
        'enabled' => $_ENV['GOOGLE_AUTH_ENABLED'] ?? '0',
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect' => $_ENV['GOOGLE_REDIRECT_URL'] ?? '',
    ],
    'facebook' => [
        'enabled' => $_ENV['FACEBOOK_AUTH_ENABLED'] ?? '0',
        'client_id' => $_ENV['FACEBOOK_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'] ?? '',
        'redirect' => $_ENV['FACEBOOK_REDIRECT_URL'] ?? '',
    ],
];
