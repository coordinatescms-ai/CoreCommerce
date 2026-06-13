<?php

namespace App\Core\Payment\Gateways;

use App\Core\Payment\PaymentGatewayInterface;
use App\Core\Payment\PaymentResult;
use App\Core\Payment\WebhookResult;

/**
 * Вбудований шлюз "Оплата при отриманні" (Cash on Delivery).
 *
 * Не потребує зовнішніх API — одразу повертає thank_you.
 * Реєструється ядром автоматично, плагін не потрібен.
 */
class CodGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'cod';
    }

    public function getLabel(): string
    {
        return 'Оплата при отриманні';
    }

    public function initiate(int $orderId, float $amount, array $meta = []): PaymentResult
    {
        // Нічого не робимо — просто показуємо сторінку подяки
        return PaymentResult::thankYou(
            'Дякуємо за замовлення! Оплата відбудеться при отриманні.',
            ['order_id' => $orderId]
        );
    }

    public function handleWebhook(array $postData, string $rawBody, array $headers): WebhookResult
    {
        // COD не має вебхуків
        return WebhookResult::invalid('COD gateway does not support webhooks.');
    }

    public function getSettingsSchema(): array
    {
        return []; // Немає налаштувань
    }
}
