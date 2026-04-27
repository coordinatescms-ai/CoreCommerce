<?php
session_start();

// 1. Ініціалізація автозавантажувача
require __DIR__.'/../vendor/autoload.php';

use App\Core\Routing\Router;
use App\Core\Http\Request;
use App\Core\Database\DB;
use App\Core\Plugin\PluginManager;
use App\Models\User;
use App\Models\Setting;

// 2. Встановлення з'єднання з базою даних
$config = require __DIR__.'/../config/database.php';
DB::connect($config['dsn'], $config['user'], $config['pass']);

// 2.1 Встановлення часового поясу сайту з налаштувань
$siteTimezone = trim((string) Setting::get('site_timezone', 'UTC'));
if ($siteTimezone === '' || !in_array($siteTimezone, timezone_identifiers_list(), true)) {
    $siteTimezone = 'UTC';
}
date_default_timezone_set($siteTimezone);

// 3. Ініціалізація CSRF токена
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// 4. Механізм "Запам'ятати мене"
if (empty($_SESSION['user']) && !empty($_COOKIE['remember_token']) && !empty($_COOKIE['user_id'])) {
    $user_id = (int)$_COOKIE['user_id'];
    $token = $_COOKIE['remember_token'];
    
    // Перевірити токен в базі даних (verifyRememberToken тепер повертає масив користувача або null)
    $user = User::verifyRememberToken($user_id, $token);
    
    if ($user && $user['is_active']) {
        // Відновити сесію
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'] ?? 'customer',
        ];
        
        // Оновити час останнього входу
        User::updateLastLogin($user['id']);
        
        // Перегенерувати ID сесії для безпеки
        session_regenerate_id(true);
    } else {
        // Якщо токен невірний або користувач неактивний, видалити недійсні кукі
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
    }
}

// 5. Завантаження плагінів
PluginManager::load();

// 6. Ініціалізація роутера та завантаження маршрутів
$router = new Router();
require __DIR__.'/../routes/web.php';
require __DIR__.'/../app/helpers.php';

// 7. Обробка запиту
$request = Request::capture();
echo $router->dispatch($request->method(), $request->uri());
