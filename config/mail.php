<?php

// Значення з .env мають пріоритет — вони вже завантажені в $_ENV через config/database.php.
// Якщо поштова конфігурація у вкладці "Пошта" адмінки ще не заповнена,
// MailService використовує ці значення як fallback.

return [
    'host'       => $_ENV['MAIL_HOST']            ?? '',
    'port'       => (int) ($_ENV['MAIL_PORT']     ?? 587),
    'username'   => $_ENV['MAIL_USER']            ?? '',
    'password'   => $_ENV['MAIL_PASS']            ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION']      ?? 'tls',
    'from_email' => $_ENV['MAIL_FROM_ADDRESS']    ?? '',
    'from_name'  => $_ENV['MAIL_FROM_NAME']       ?? '',
];
