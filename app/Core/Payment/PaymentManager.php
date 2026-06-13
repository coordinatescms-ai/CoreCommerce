<?php

namespace App\Core\Payment;

use App\Core\Database\DB;

/**
 * Реєстр платіжних шлюзів.
 *
 * Плагін реєструє свій шлюз через:
 *   PaymentManager::register(new MyGateway());
 *
 * Ядро отримує шлюз через:
 *   PaymentManager::get('liqpay')
 */
class PaymentManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private static array $gateways = [];

    /**
     * Зареєструвати платіжний шлюз.
     * Викликається плагіном у методі register().
     */
    public static function register(PaymentGatewayInterface $gateway): void
    {
        self::$gateways[$gateway->getName()] = $gateway;
    }

    /**
     * Отримати шлюз за іменем.
     * Повертає null якщо шлюз не зареєстрований.
     */
    public static function get(string $name): ?PaymentGatewayInterface
    {
        return self::$gateways[$name] ?? null;
    }

    /**
     * Список усіх зареєстрованих шлюзів.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public static function all(): array
    {
        return self::$gateways;
    }

    /**
     * Чи є хоч один зареєстрований шлюз.
     */
    public static function hasAny(): bool
    {
        return !empty(self::$gateways);
    }

    /**
     * Знайти шлюз для конкретного payment_method_code замовлення.
     *
     * Спочатку шукає пряму відповідність по імені шлюзу,
     * потім — по коду методу оплати з shop_methods.
     */
    public static function findForOrder(string $paymentMethodCode): ?PaymentGatewayInterface
    {
        // Пряма відповідність: код методу = ім'я шлюзу
        if (isset(self::$gateways[$paymentMethodCode])) {
            return self::$gateways[$paymentMethodCode];
        }

        // Шукаємо в shop_methods чи є прив'язка gateway_name
        $row = DB::query(
            "SELECT settings FROM shop_methods WHERE code = ? AND type = 'payment' AND is_active = 1 LIMIT 1",
            [$paymentMethodCode]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $settings = json_decode((string)($row['settings'] ?? '{}'), true);
            $gatewayName = $settings['gateway_name'] ?? '';
            if ($gatewayName !== '' && isset(self::$gateways[$gatewayName])) {
                return self::$gateways[$gatewayName];
            }
        }

        return null;
    }
}
