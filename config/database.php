<?php 
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ігноруємо коментарі
        if (strpos(trim($line), '#') === 0) continue;

        // Розбиваємо рядок на ключ і значення
        list($name, $value) = explode('=', $line, 2);
        
        $name = trim($name);
        $value = trim($value);

        // Записуємо в оточення та масив $_ENV
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Викликаємо функцію (переконайтеся, що шлях до .env правильний відносно цієї папки)
loadEnv(__DIR__ . '/../.env'); 

return [
    'dsn'  => "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASS'] ?? '', // краще брати пароль теж з .env
];