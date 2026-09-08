<?php
session_start();

// 1. Версія рушія — береться з config/updater.php, щоб бути єдиним джерелом правди
$_updaterCfg = require __DIR__ . '/../config/updater.php';
define('CORE_VERSION', (string) ($_updaterCfg['current_version'] ?? '1.0.0'));
unset($_updaterCfg);

// 1.1 Ініціалізація автозавантажувача
require __DIR__.'/../vendor/autoload.php';

use App\Core\Routing\Router;
use App\Core\Http\Request;
use App\Core\Database\DB;
use App\Core\Plugin\PluginManager;
use App\Models\User;
use App\Models\Setting;

// 2. Перевірка режиму обслуговування (Maintenance Mode) - тільки файл flag
// Перевірка налаштувань з БД буде пізніше, після підключення БД
if (file_exists(__DIR__ . '/../storage/maintenance.flag') && strpos($_SERVER['REQUEST_URI'], '/admin') === false) {
    http_response_code(503);
    die("<h1>Сайт на оновленні</h1><p>Будь ласка, зачекайте кілька хвилин. Ми оновлюємо систему для вашої зручності.</p>");
}

// 3. Встановлення з'єднання з базою даних
$config = require __DIR__.'/../config/database.php';
DB::connect($config['dsn'], $config['user'], $config['pass']);

// 3.1 Застосування заголовків безпеки: HTTPS-редирект, HSTS, CSP, X-Frame-Options тощо.
// Викликається максимально рано — до будь-якого HTML-виводу, інакше PHP не зможе
// відправити header() або зробити редирект (заголовки вже будуть відправлені).
\App\Services\SecurityHeadersService::apply();

// 3.2 Перевірка статусу магазину з бази даних (після підключення БД)
if (strpos($_SERVER['REQUEST_URI'], '/admin') === false) {
    try {
        $storeStatus = Setting::get('store_status', 'open');
        error_log('Maintenance check: store_status = ' . var_export($storeStatus, true));

        if ($storeStatus === 'closed') {
            $maintenanceMessage = Setting::get('maintenance_message', '');
            $message = $maintenanceMessage ?: trim((string) __('admin_settings_maintenance_message_default'));
            if (empty($message)) {
                $message = "Сайт тимчасово закрито для технічного обслуговування. Будь ласка, зачекайте кілька хвилин.";
            }
            http_response_code(503);
            die("<h1>Сайт на оновленні</h1><p>" . htmlspecialchars($message) . "</p>");
        }
    } catch (\Throwable $e) {
        error_log('Error checking store_status: ' . $e->getMessage());
        // При помилці продовжуємо роботу сайту
    }
}

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

// 5.2 Реєстрація вбудованих платіжних шлюзів
\App\Core\Payment\PaymentManager::register(
    new \App\Core\Payment\Gateways\CodGateway()
);

// 5.1 Інтеграція з Prom.ua (хуки підключаються тільки якщо інтеграція увімкнена)
if (\App\Services\PromApiClient::isEnabled()) {
    // Автоматична постановка товару в чергу при зміні ціни або залишків
    PluginManager::getInstance()->addAction(
        'product.updated',
        static function (int $productId, array $changedFields): void {
            $needsSync = !empty(array_intersect(['price', 'stock_quantity'], $changedFields));
            if ($needsSync) {
                try {
                    $action = match (true) {
                        in_array('price', $changedFields, true) && in_array('stock_quantity', $changedFields, true) => 'both',
                        in_array('price', $changedFields, true) => 'price',
                        default => 'quantity',
                    };
                    (new \App\Services\PromSyncService())->enqueueProducts([$productId], $action);
                } catch (\Throwable $e) {
                    error_log('Prom enqueue error: ' . $e->getMessage());
                }
            }
        }
    );
}

// 6. Ініціалізація роутера та завантаження маршрутів
$router = new Router();
require __DIR__.'/../routes/web.php';
require __DIR__.'/../app/helpers.php';

// Реєструємо хук синхронізації статусів замовлень з Prom.ua
// PaymentWebhookController використовує do_action('order.status_changed', $orderId, $status, $ttn)
add_action('order.status_changed', static function (int $orderId, string $status, string $ttn = ''): void {
    try {
        (new \App\Services\PromStatusService())->onStatusChanged($orderId, $status, $ttn);
    } catch (\Throwable $e) {
        error_log('PromStatusService hook error: ' . $e->getMessage());
    }
});

// 7. Обробка запиту
$request = Request::capture();
$router->dispatch($request->method(), $request->uri());
