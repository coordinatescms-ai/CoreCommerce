<?php
/**
 * Обробник AJAX-запитів для віджету "Замовити дзвінок"
 */

// Встановлюємо заголовки для JSON-відповіді
header('Content-Type: application/json; charset=utf-8');

// Обробляємо тільки POST-запити
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Отримуємо JSON-дані з тіла запиту
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Отримаємо мову з запиту (за замовчуванням 'uk')
$lang = $data['lang'] ?? 'uk';
if (!in_array($lang, ['uk', 'en'])) {
    $lang = 'en';
}

// Завантажуємо мовний файл
$langFile = __DIR__ . '/lang/' . $lang . '.json';
$translations = [];

if (file_exists($langFile)) {
    $translations = json_decode(file_get_contents($langFile), true) ?? [];
}

// ВАЖЛИВО: цей файл виконується через require всередині методу
// Router::servePluginFile() (щоб плагіни могли мати власні AJAX-ендпоінти
// без реєстрації маршруту для кожного плагіна). Це означає, що змінні,
// оголошені тут, живуть у ЛОКАЛЬНІЙ області видимості цього методу,
// а НЕ в глобальній. Раніше тут була `function t() { global $translations; ... }`
// — `global` шукає змінну в глобальній області, якої тут ніколи не було,
// тож переклад завжди мовчки повертав $default (англійський текст),
// незалежно від фактично обраної мови. Лист завжди йшов англійською.
// Замикання, що безпосередньо захоплює $translations через use(),
// коректно працює незалежно від того, з якого контексту підключений файл.
$t = function ($key, $default = '') use ($translations) {
    return $translations[$key] ?? $default;
};

// Очищення даних від XSS та ін'єкцій
function sanitize($value) {
    $value = trim($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return $value;
}

// Захист від CSRF / автоматизованого спаму: цей ендпоінт раніше не мав жодної
// перевірки і був відкритий для будь-якого POST-запиту ззовні (без сесії, без
// токена) — тобто будь-хто міг завалити поштову скриньку власника магазину
// автоматизованими запитами. Перевіряємо той самий сесійний токен, що й
// решта проєкту (App\Core\Http\Csrf), але читаємо його з JSON-тіла запиту,
// а не з $_POST, оскільки віджет надсилає дані через fetch()+JSON.
require_once dirname(__DIR__, 2) . '/app/Core/Http/Csrf.php';
$sessionToken = \App\Core\Http\Csrf::token();
$requestToken = is_string($data['csrf'] ?? null) ? $data['csrf'] : '';
if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
    http_response_code(419);
    echo json_encode([
        'success' => false,
        'message' => $t('csrf_error', 'Your session has expired, please refresh the page and try again.'),
    ]);
    exit;
}

// Валідація полів
$name = sanitize($data['name'] ?? '');
$phone = sanitize($data['phone'] ?? '');

if (empty($name) || empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => $t('error_message', 'Please fill in all required fields.')
    ]);
    exit;
}

// Валідація телефону (мінімум 10 цифр)
$phoneDigits = preg_replace('/[^0-9]/', '', $phone);
if (strlen($phoneDigits) < 10) {
    echo json_encode([
        'success' => false,
        'message' => $t('validation_error', 'Please fill in all fields correctly.')
    ]);
    exit;
}

// Валідація імені (мінімум 2 символи)
if (mb_strlen($name) < 2) {
    echo json_encode([
        'success' => false,
        'message' => $t('validation_error', 'Please fill in all fields correctly.')
    ]);
    exit;
}

// Підключення до бази даних для отримання email для відправки
require_once dirname(__DIR__, 2) . '/app/Core/Database/DB.php';
require_once dirname(__DIR__, 2) . '/app/Core/Mail/MailService.php';

try {
    $contactEmail = \App\Core\Database\DB::query(
        "SELECT value FROM settings WHERE `key` = 'contact_email' LIMIT 1"
    )->fetch(\PDO::FETCH_ASSOC);
    
    $toEmail = $contactEmail['value'] ?? '';
    
    if (empty($toEmail)) {
        echo json_encode([
            'success' => false,
            'message' => $t('server_error', 'Server error. Please try again later.')
        ]);
        exit;
    }
    
    // Формуємо лист
    $subject = $t('email_subject', 'New callback request');
    $bodyTemplate = $t('email_body', "Name: {name}\nPhone: {phone}\nDate: {date}");
    
    $body = str_replace(
        ['{name}', '{phone}', '{date}'],
        [$name, $phone, date('Y-m-d H:i')],
        $bodyTemplate
    );
    
    // Відправка email через MailService (SMTP)
    $mailService = new \App\Core\Mail\MailService();
    $result = $mailService->sendWithDiagnostics($toEmail, $subject, nl2br($body));
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $t('success_message', 'Thank you! We will call you back shortly.')
        ]);
    } else {
        error_log('Callback Widget MailService Error: ' . $result['error']);
        echo json_encode([
            'success' => false,
            'message' => $t('server_error', 'Server error. Please try again later.')
        ]);
    }
    
} catch (Exception $e) {
    error_log('Callback Widget Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $t('server_error', 'Server error. Please try again later.')
    ]);
}
