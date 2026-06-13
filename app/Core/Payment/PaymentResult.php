<?php

namespace App\Core\Payment;

/**
 * Результат ініціації платежу, який плагін повертає ядру.
 *
 * Ядро дивиться на $action і реагує відповідно:
 *
 *  'redirect' — перенаправити користувача на $url (зовнішня платіжна сторінка)
 *  'render'   — відобразити HTML з $html (форма або кнопка всередині сайту)
 *  'thank_you'— одразу показати сторінку подяки (оплата при отриманні / безкоштовно)
 *  'error'    — показати повідомлення про помилку
 */
class PaymentResult
{
    private function __construct(
        public readonly string  $action,  // redirect | render | thank_you | error
        public readonly string  $url,     // для redirect
        public readonly string  $html,    // для render
        public readonly string  $message, // для error або thank_you
        public readonly array   $extra,   // будь-які додаткові дані для плагіна
    ) {}

    /**
     * Перенаправити на зовнішню платіжну сторінку.
     */
    public static function redirect(string $url, array $extra = []): self
    {
        return new self('redirect', $url, '', '', $extra);
    }

    /**
     * Відрендерити HTML-форму або кнопку прямо на сторінці.
     */
    public static function render(string $html, array $extra = []): self
    {
        return new self('render', '', $html, '', $extra);
    }

    /**
     * Одразу показати сторінку подяки (без переходу на платіжну систему).
     * Використовується для: оплата при отриманні, безкоштовні замовлення.
     */
    public static function thankYou(string $message = '', array $extra = []): self
    {
        return new self('thank_you', '', '', $message, $extra);
    }

    /**
     * Помилка ініціації платежу.
     */
    public static function error(string $message): self
    {
        return new self('error', '', '', $message, []);
    }
}
