<?php

return [
    'host'       => $_ENV['MAIL_HOST'] ?? 'localhost',
    'port'       => $_ENV['MAIL_PORT'] ?? 25,
    'username'   => $_ENV['MAIL_USER'] ?? '',
    'password'   => $_ENV['MAIL_PASS'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'from_email' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'admin@example.com',
    'from_name'  => $_ENV['MAIL_FROM_NAME'] ?? 'My App',
];
