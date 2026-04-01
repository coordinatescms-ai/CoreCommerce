<?php
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

use App\Models\User;

// Перевірити, чи користувач вже авторизований
// Але якщо кукі існують, відновити сесію
if (empty($_SESSION['user']) && !empty($_COOKIE['remember_token']) && !empty($_COOKIE['user_id'])) {
    require __DIR__.'/../vendor/autoload.php';
    
    $user_id = (int)$_COOKIE['user_id'];
    $token = $_COOKIE['remember_token'];
    
    // Перевірити токен в базі даних
    if (User::verifyRememberToken($user_id, $token)) {
        $user = User::findById($user_id);
        if ($user && $user['is_active']) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
            ];
        }
    }
}

session_regenerate_id(true);
require __DIR__.'/../vendor/autoload.php';

use App\Core\Routing\Router;
use App\Core\Http\Request;
use App\Core\Database\DB;
use App\Core\Plugin\PluginManager;

$config=require __DIR__.'/../config/database.php';
DB::connect($config['dsn'],$config['user'],$config['pass']);

PluginManager::load();

$router=new Router();
require __DIR__.'/../routes/web.php';
require __DIR__.'/../app/helpers.php';

$request=Request::capture();
echo $router->dispatch($request->method(),$request->uri());
