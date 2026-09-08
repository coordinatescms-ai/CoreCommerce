<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

const NOVA_POSHTA_API_URL = 'https://api.novaposhta.ua/v2.0/json/';

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database\DB;

$config = require __DIR__ . '/../config/database.php';
DB::connect($config['dsn'], $config['user'], $config['pass']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Захист: перевіряємо що запит йде з нашого сайту
$allowedHosts = [parse_url((string)(getenv('APP_URL') ?: ''), PHP_URL_HOST), $_SERVER['HTTP_HOST'] ?? ''];
$origin = parse_url($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
if ($origin !== null && !in_array($origin, array_filter($allowedHosts), true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Rate limiting: не більше 30 запитів на хвилину на сесію
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
$rlKey = 'np_api_rl';
$rl = $_SESSION[$rlKey] ?? ['count' => 0, 'start' => $now];
if ($now - $rl['start'] > 60) {
    $rl = ['count' => 0, 'start' => $now];
}
$rl['count']++;
$_SESSION[$rlKey] = $rl;
if ($rl['count'] > 30) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Забагато запитів. Спробуйте пізніше.']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '{}', true);

if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$action = (string) ($payload['action'] ?? '');

try {
    $apiKey = loadNovaPoshtaApiKey();
    if ($apiKey === '') {
        throw new RuntimeException('API ключ Нової Пошти не налаштований.');
    }

    if ($action === 'cities') {
        $query = trim((string) ($payload['query'] ?? ''));
        if (mb_strlen($query) < 3) {
            throw new RuntimeException('Мінімум 3 символи для пошуку міста');
        }

        $result = npApiRequest($apiKey, 'Address', 'getCities', ['FindByString' => $query, 'Limit' => 20]);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    if ($action === 'warehouses') {
        $cityRef = trim((string) ($payload['cityRef'] ?? ''));
        if ($cityRef === '') {
            throw new RuntimeException('Необхідно передати cityRef');
        }

        $result = npApiRequest($apiKey, 'Address', 'getWarehouses', ['CityRef' => $cityRef, 'Limit' => 200]);
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    throw new RuntimeException('Unknown action');
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

function npApiRequest(string $apiKey, string $modelName, string $calledMethod, array $methodProperties): array
{
    $requestBody = [
        'apiKey' => $apiKey,
        'modelName' => $modelName,
        'calledMethod' => $calledMethod,
        'methodProperties' => $methodProperties,
    ];

    $ch = curl_init(NOVA_POSHTA_API_URL);

    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($requestBody, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 15,
    ];

    // На Windows (OSPanel) часто відсутній cacert.pem — підключаємо вручну якщо є,
    // або вимикаємо перевірку (прийнятно для серверного запиту до відомого API)
    $cacert = __DIR__ . '/../config/cacert.pem';
    if (file_exists($cacert)) {
        $curlOpts[CURLOPT_CAINFO]         = $cacert;
        $curlOpts[CURLOPT_SSL_VERIFYPEER] = true;
    } else {
        $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
        $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
    }

    curl_setopt_array($ch, $curlOpts);

    $responseBody = curl_exec($ch);

    if ($responseBody === false) {
        $error = curl_error($ch) ?: 'Unknown cURL error';
        curl_close($ch);
        throw new RuntimeException('Nova Poshta API unavailable: ' . $error);
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($responseBody, true);

    if ($httpCode >= 400 || !is_array($decoded)) {
        throw new RuntimeException('Invalid response from Nova Poshta API');
    }

    if (!empty($decoded['errors'])) {
        throw new RuntimeException(implode('; ', (array) $decoded['errors']));
    }

    return $decoded['data'] ?? [];
}

function loadNovaPoshtaApiKey(): string
{
    $method = DB::query(
        "SELECT settings
         FROM shop_methods
         WHERE type = 'shipping' AND code = 'nova_poshta' AND is_active = 1
         ORDER BY sort_order ASC, id ASC
         LIMIT 1"
    )->fetch(\PDO::FETCH_ASSOC);

    if (!$method) {
        return '';
    }

    $settings = json_decode((string) ($method['settings'] ?? ''), true);
    if (!is_array($settings)) {
        return '';
    }

    return trim((string) ($settings['api_key'] ?? ''));
}
