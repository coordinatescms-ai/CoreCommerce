<?php

namespace App\Core\Payment;

/**
 * Контракт для будь-якого плагіна платіжної системи.
 *
 * Кожен платіжний плагін реалізує цей інтерфейс.
 * Ядро не знає нічого про конкретну платіжну систему —
 * воно лише викликає методи цього контракту.
 *
 * Приклади плагінів: LiqPay, Stripe, WayForPay, Fondy, MonoPay.
 */
interface PaymentGatewayInterface
{
    /**
     * Унікальний ідентифікатор шлюзу — латиниця+цифри, без пробілів.
     * Використовується як {gateway_name} у webhook URL.
     *
     * Приклади: 'liqpay', 'wayforpay', 'stripe', 'monobank'
     */
    public function getName(): string;

    /**
     * Людська назва для відображення в адмінці та на чекауті.
     *
     * Приклад: 'LiqPay — оплата карткою'
     */
    public function getLabel(): string;

    /**
     * Ініціювати платіж після створення замовлення.
     *
     * Ядро викликає цей метод одразу після INSERT замовлення зі статусом 'pending'.
     * Плагін повертає масив з інструкцією що робити далі.
     *
     * @param  int   $orderId  ID замовлення в нашій БД
     * @param  float $amount   Сума до оплати
     * @param  array $meta     Додаткові дані: email, phone, description тощо
     *
     * @return PaymentResult
     */
    public function initiate(int $orderId, float $amount, array $meta = []): PaymentResult;

    /**
     * Обробити вебхук від платіжного сервісу.
     *
     * Ядро отримує POST на /payment/webhook/{gateway_name}
     * і передає сюди сирі дані запиту.
     * Плагін верифікує підпис, оновлює статус замовлення і повертає результат.
     *
     * @param  array  $postData   $_POST або розібраний JSON
     * @param  string $rawBody    Сире тіло запиту (для верифікації підпису)
     * @param  array  $headers    Заголовки запиту
     *
     * @return WebhookResult
     */
    public function handleWebhook(array $postData, string $rawBody, array $headers): WebhookResult;

    /**
     * Повернути налаштування плагіна для сторінки конфігурації.
     * Формат аналогічний до PluginInterface::getSettingsSchema().
     */
    public function getSettingsSchema(): array;
}
