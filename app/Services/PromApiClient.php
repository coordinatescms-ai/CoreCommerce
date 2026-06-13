<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Клієнт для роботи з Prom Public API.
 *
 * Документація: https://public-api.docs.prom.ua/
 *
 * Всі запити йдуть через file_get_contents + stream_context (без curl).
 * Кожна відповідь і помилка логується у storage/logs/prom.log.
 */
class PromApiClient
{
    private const BASE_URL    = 'https://my.prom.ua/api/v1';
    private const TIMEOUT     = 15;
    private const MAX_RETRIES = 2;

    private string $apiKey;
    private string $logFile;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey  = $apiKey ?? trim((string) Setting::get('prom_api_key', ''));
        $this->logFile = dirname(__DIR__, 2) . '/storage/logs/prom.log';
    }

    /**
     * Перевірити чи увімкнена інтеграція з Prom.ua.
     */
    public static function isEnabled(): bool
    {
        return (string) Setting::get('prom_enabled', '0') === '1';
    }

    // =========================================================================
    // Замовлення
    // =========================================================================

    /**
     * Отримати список статусів замовлень (легкий тест з'єднання).
     * GET /orders/status_list
     */
    public function getOrderStatusList(): array
    {
        return $this->request('GET', '/orders/status_list');
    }

    /**
     * Отримати замовлення за його ID на Prom.
     * GET /orders/{id}
     */
    public function getOrder(int $promOrderId): array
    {
        return $this->request('GET', '/orders/' . $promOrderId);
    }

    /**
     * Отримати список замовлень (з фільтрами).
     * GET /orders/list
     *
     * @param array $params  Наприклад: ['status' => 'pending', 'limit' => 100]
     */
    public function getOrders(array $params = []): array
    {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', '/orders/list' . $query);
    }

    /**
     * Оновити статус замовлення на Prom.
     * POST /orders/set_status
     *
     * @param array $ids     Масив ID замовлень на Prom
     * @param string $status Статус: 'pending'|'received'|'delivering'|'delivered'|
     *                               'canceled'|'draft'|'paid'
     */
    public function setOrderStatus(array $ids, string $status): array
    {
        return $this->request('POST', '/orders/set_status', [
            'ids'    => $ids,
            'status' => $status,
        ]);
    }

    /**
     * Передати ТТН Нової Пошти для замовлення.
     * POST /orders/set_delivery_ttn
     *
     * @param int    $promOrderId  ID замовлення на Prom
     * @param string $ttn          Номер ТТН
     * @param string $provider     Провайдер: 'nova_poshta' | 'ukrposhta' | ...
     */
    public function setDeliveryTtn(int $promOrderId, string $ttn, string $provider = 'nova_poshta'): array
    {
        return $this->request('POST', '/orders/set_delivery_ttn', [
            'id'                => $promOrderId,
            'delivery_ttn'      => $ttn,
            'delivery_provider' => $provider,
        ]);
    }

    // =========================================================================
    // Товари — Підхід Б: оновлення через API
    // =========================================================================

    /**
     * Оновити ціну та залишок одного товару на Prom.
     * POST /products/edit
     *
     * @param int        $promProductId  ID товару на Prom
     * @param float|null $price          Нова ціна (null = не змінювати)
     * @param int|null   $quantity       Новий залишок (null = не змінювати)
     */
    public function updateProduct(int $promProductId, ?float $price = null, ?int $quantity = null): array
    {
        $body = ['id' => $promProductId];

        if ($price !== null) {
            $body['price'] = number_format($price, 2, '.', '');
        }
        if ($quantity !== null) {
            $body['presence']          = $quantity > 0 ? 'available' : 'not_available';
            $body['quantity_in_stock'] = $quantity;
        }

        return $this->request('POST', '/products/edit', $body);
    }

    /**
     * Масове оновлення товарів (до 100 позицій за запит).
     * POST /products/edit_group
     *
     * @param array $items  [['id' => promId, 'price' => ..., 'quantity_in_stock' => ...], ...]
     */
    public function updateProductsBatch(array $items): array
    {
        // Prom приймає максимум 100 позицій за раз
        $chunks  = array_chunk($items, 100);
        $results = [];

        foreach ($chunks as $chunk) {
            $results[] = $this->request('POST', '/products/edit_group', ['products' => $chunk]);
        }

        return $results;
    }

    /**
     * Отримати інформацію про товар за ID на Prom.
     * GET /products/{id}
     */
    public function getProduct(int $promProductId): array
    {
        return $this->request('GET', '/products/' . $promProductId);
    }

    // =========================================================================
    // Транспорт
    // =========================================================================

    /**
     * Виконати HTTP-запит до Prom API.
     *
     * @param  string $method  GET | POST
     * @param  string $path    Шлях, наприклад '/orders/status_list'
     * @param  array  $body    Тіло запиту (для POST)
     * @return array  Розібрана JSON-відповідь або ['error' => '...']
     */
    public function request(string $method, string $path, array $body = []): array
    {
        if ($this->apiKey === '') {
            $this->log('ERROR', $method, $path, 'API ключ не налаштований');
            return ['error' => 'API ключ не налаштований у налаштуваннях магазину.'];
        }

        $url     = self::BASE_URL . $path;
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $contextOptions = [
            'http' => [
                'method'        => strtoupper($method),
                'header'        => implode("\r\n", $headers),
                'timeout'       => self::TIMEOUT,
                'ignore_errors' => true, // Отримуємо тіло навіть при 4xx/5xx
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ];

        if (strtoupper($method) === 'POST' && !empty($body)) {
            $contextOptions['http']['content'] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }

        $attempt      = 0;
        $lastError    = '';
        $responseBody = false;
        $httpCode     = 0;

        while ($attempt <= self::MAX_RETRIES) {
            $attempt++;
            $context      = stream_context_create($contextOptions);
            $responseBody = @file_get_contents($url, false, $context);

            // Витягуємо HTTP-код з заголовків відповіді
            $httpCode = $this->extractHttpCode($http_response_header ?? []);

            if ($responseBody !== false) {
                break; // Успішне з'єднання
            }

            $lastError = error_get_last()['message'] ?? 'Невідома помилка мережі';

            if ($attempt <= self::MAX_RETRIES) {
                sleep(1); // Пауза перед повторною спробою
            }
        }

        // Не вдалося з'єднатися взагалі
        if ($responseBody === false) {
            $this->log('NETWORK_ERROR', $method, $path, $lastError, $httpCode);
            return ['error' => 'Помилка мережі: ' . $lastError];
        }

        $decoded = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('JSON_ERROR', $method, $path, $responseBody, $httpCode);
            return ['error' => 'Prom повернув некоректний JSON. Код: ' . $httpCode];
        }

        // Логуємо помилкові відповіді (4xx, 5xx)
        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? $decoded['error'] ?? json_encode($decoded);
            $this->log('API_ERROR', $method, $path, "HTTP {$httpCode}: {$errorMsg}", $httpCode, $body);
            return array_merge($decoded, ['http_code' => $httpCode]);
        }

        $this->log('OK', $method, $path, "HTTP {$httpCode}", $httpCode);
        return $decoded;
    }

    /**
     * Перевірити з'єднання з Prom API.
     * Повертає масив: ['success' => bool, 'http_code' => int, 'message' => string]
     */
    public function testConnection(): array
    {
        if ($this->apiKey === '') {
            return [
                'success'   => false,
                'http_code' => 0,
                'message'   => 'API ключ не введено.',
            ];
        }

        $result = $this->request('GET', '/orders/status_list');

        if (isset($result['error'])) {
            return [
                'success'   => false,
                'http_code' => $result['http_code'] ?? 0,
                'message'   => $result['error'],
            ];
        }

        $httpCode = $result['http_code'] ?? 200;

        if ($httpCode === 401) {
            return [
                'success'   => false,
                'http_code' => 401,
                'message'   => 'Неправильний API ключ. Перевірте правильність копіювання.',
            ];
        }

        return [
            'success'   => true,
            'http_code' => 200,
            'message'   => "З'єднання успішне! Магазин підключено до Prom.ua.",
        ];
    }

    // =========================================================================
    // Логування
    // =========================================================================

    private function log(
        string $level,
        string $method,
        string $path,
        string $message,
        int    $httpCode = 0,
        array  $requestBody = []
    ): void {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf(
            "[%s] [%s] %s %s | HTTP:%d | %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            strtoupper($method),
            $path,
            $httpCode,
            $message,
            !empty($requestBody) ? ' | body:' . json_encode($requestBody, JSON_UNESCAPED_UNICODE) : ''
        );

        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);

        // Ротація: якщо лог більше 5 МБ — обрізаємо старі рядки
        if (file_exists($this->logFile) && filesize($this->logFile) > 5 * 1024 * 1024) {
            $this->rotateLog();
        }
    }

    private function rotateLog(): void
    {
        $lines   = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $keep    = array_slice($lines, -500); // Залишаємо останні 500 рядків
        file_put_contents($this->logFile, implode("\n", $keep) . "\n", LOCK_EX);
    }

    private function extractHttpCode(array $responseHeaders): int
    {
        foreach ($responseHeaders as $header) {
            if (preg_match('#^HTTP/\d+\.\d+\s+(\d+)#', $header, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }
}
