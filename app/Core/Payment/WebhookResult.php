<?php

namespace App\Core\Payment;

/**
 * Результат обробки вебхука від платіжного сервісу.
 *
 * Плагін повертає цей об'єкт після верифікації підпису та обробки події.
 * Ядро використовує його щоб оновити статус замовлення і відповісти платіжному сервісу.
 */
class WebhookResult
{
    private function __construct(
        public readonly bool   $success,    // чи оплата пройшла успішно
        public readonly int    $orderId,    // наш internal order.id
        public readonly string $status,     // новий статус замовлення: 'completed' | 'cancelled' | ...
        public readonly string $message,    // опис для логу
        public readonly string $responseBody, // що відправити у відповідь платіжному сервісу (якщо потрібно)
        public readonly int    $httpCode,   // HTTP-код відповіді платіжному сервісу
    ) {}

    /**
     * Оплата успішна — замовлення виконано.
     */
    public static function paid(int $orderId, string $message = '', string $responseBody = 'OK', int $httpCode = 200): self
    {
        return new self(true, $orderId, 'completed', $message, $responseBody, $httpCode);
    }

    /**
     * Оплата відхилена або скасована.
     */
    public static function failed(int $orderId, string $message = '', string $responseBody = 'OK', int $httpCode = 200): self
    {
        return new self(false, $orderId, 'cancelled', $message, $responseBody, $httpCode);
    }

    /**
     * Помилка верифікації підпису або некоректні дані.
     */
    public static function invalid(string $message = 'Invalid signature', int $httpCode = 400): self
    {
        return new self(false, 0, '', $message, $message, $httpCode);
    }
}
