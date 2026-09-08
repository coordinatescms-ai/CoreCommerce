<?php

function loadEnv(string $path): bool
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }

    return true;
}

// Завантажуємо .env з кореня проєкту.
// Якщо файл відсутній — копіюємо .env.example → .env (щоб не треба було
// вручну копіювати файл через хостинг-панель чи FTP), і показуємо зрозуміле
// повідомлення про встановлення замість криптичної PHP-помилки.
$envFile     = __DIR__ . '/../.env';
$exampleFile = __DIR__ . '/../.env.example';

if (!file_exists($envFile) && file_exists($exampleFile) && is_writable(dirname($envFile))) {
    copy($exampleFile, $envFile);
}

if (!loadEnv($envFile)) {
    $exampleExists = file_exists($exampleFile);
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<title>CoreCommerce — Setup Required</title>
<style>
  body { font-family: sans-serif; background: #f5f5f5; display: flex;
         justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
  .box { background: #fff; border-left: 4px solid #e74c3c; padding: 2rem 2.5rem;
         border-radius: 6px; box-shadow: 0 2px 12px rgba(0,0,0,.1); max-width: 560px; }
  h1 { margin: 0 0 .75rem; font-size: 1.3rem; color: #c0392b; }
  code { background: #f0f0f0; padding: .15rem .4rem; border-radius: 3px;
         font-size: .9rem; }
  ol { padding-left: 1.2rem; line-height: 1.9; }
</style>
</head><body><div class="box">
  <h1>⚙️ Setup Required</h1>
  <p>The configuration file <code>.env</code> was not found, and it could not
     be created automatically (the project folder is not writable).</p>
  <ol>' . ($exampleExists
    ? '<li>Copy <code>.env.example</code> → <code>.env</code></li>'
    : '<li>Create the file <code>.env</code> in the project root</li>') . '
    <li>Fill in your database credentials (<code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>)</li>
    <li>Set <code>APP_URL</code> to your domain</li>
    <li>Reload the page</li>
  </ol>
  <p>See <code>INSTALL.md</code> for the full installation guide.</p>
</div></body></html>';
    exit;
}

// Значення-заглушки з .env.example — якщо .env щойно автоматично скопійований
// (або людина забула відредагувати вручну скопійований файл), ці плейсхолдери
// й досі там. Підключатись з ними до БД немає сенсу — покажемо той самий
// зрозумілий екран, а не сирий PDO-виняток "Access denied for user
// 'your_database_user'@'localhost'".
$placeholders = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'your_database_name',
    'DB_USER' => 'your_database_user',
    'DB_PASS' => 'your_database_password',
];

$stillPlaceholder = ($_ENV['DB_NAME'] ?? '') === $placeholders['DB_NAME']
    || ($_ENV['DB_USER'] ?? '') === $placeholders['DB_USER']
    || ($_ENV['DB_PASS'] ?? '') === $placeholders['DB_PASS'];

if ($stillPlaceholder) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<title>CoreCommerce — Setup Required</title>
<style>
  body { font-family: sans-serif; background: #f5f5f5; display: flex;
         justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
  .box { background: #fff; border-left: 4px solid #e74c3c; padding: 2rem 2.5rem;
         border-radius: 6px; box-shadow: 0 2px 12px rgba(0,0,0,.1); max-width: 560px; }
  h1 { margin: 0 0 .75rem; font-size: 1.3rem; color: #c0392b; }
  code { background: #f0f0f0; padding: .15rem .4rem; border-radius: 3px; font-size: .9rem; }
</style>
</head><body><div class="box">
  <h1>⚙️ Setup Required</h1>
  <p>We created <code>.env</code> for you from <code>.env.example</code>,
     but it still contains placeholder values.</p>
  <p>Open <code>.env</code> in the project root and fill in your real
     database credentials (<code>DB_NAME</code>, <code>DB_USER</code>,
     <code>DB_PASS</code>), then reload the page.</p>
  <p>See <code>INSTALL.md</code> for the full installation guide.</p>
</div></body></html>';
    exit;
}

// Перевіряємо що обов'язкові змінні заповнені
foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $required) {
    if (empty($_ENV[$required])) {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<title>CoreCommerce — Configuration Error</title>
<style>
  body { font-family: sans-serif; background: #f5f5f5; display: flex;
         justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
  .box { background: #fff; border-left: 4px solid #e67e22; padding: 2rem 2.5rem;
         border-radius: 6px; box-shadow: 0 2px 12px rgba(0,0,0,.1); max-width: 560px; }
  h1 { margin: 0 0 .75rem; font-size: 1.3rem; color: #d35400; }
  code { background: #f0f0f0; padding: .15rem .4rem; border-radius: 3px; font-size: .9rem; }
</style>
</head><body><div class="box">
  <h1>⚠️ Configuration Error</h1>
  <p>The required variable <code>' . htmlspecialchars($required) . '</code>
     is missing or empty in your <code>.env</code> file.</p>
  <p>Please check <code>.env</code> and make sure all database settings are filled in.</p>
</div></body></html>';
        exit;
    }
}

return [
    'dsn'  => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_NAME']
    ),
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASS'] ?? '',
];