<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static $table = 'settings';

    // =========================================================================
    // РОБОТА З ТАБЛИЦЕЮ settings
    // =========================================================================

    public static function get($key, $default = null)
    {
        $result = self::query("SELECT `value` FROM settings WHERE `key` = ?", [$key]);
        // Якщо query повертає масив, беремо перший елемент
        return !empty($result) ? $result[0]['value'] : $default;
    }

    public static function getAllGrouped()
    {
        // Прибираємо ->fetchAll(), бо він вже всередині self::query
        $settings = self::query("SELECT * FROM settings ORDER BY `group`, `key` ASC");
        
        $grouped = [];
        if (is_array($settings)) {
            foreach ($settings as $setting) {
                $grouped[$setting['group']][] = $setting;
            }
        }
        return $grouped;
    }

    public static function setWithMeta(string $key, $value, string $group = 'general', string $type = 'text')
    {
        $exists = self::query("SELECT `key` FROM settings WHERE `key` = ?", [$key]);

        if (!empty($exists)) {
            return self::execute(
                "UPDATE settings SET `value` = ?, `group` = ?, `type` = ?, `updated_at` = NOW() WHERE `key` = ?",
                [(string) $value, $group, $type, $key]
            );
        }

        return self::execute(
            "INSERT INTO settings (`key`, `value`, `group`, `type`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$key, (string) $value, $group, $type]
        );
    }

    // =========================================================================
    // РОБОТА З ТАБЛИЦЕЮ shop_methods
    // =========================================================================

    public static function getShopMethods(string $type)
    {
        // query вже повертає готовий масив
        return self::query(
            "SELECT * FROM shop_methods WHERE `type` = ? ORDER BY `sort_order` ASC",
            [$type]
        );
    }

    public static function updateShopMethod(int $id, array $data)
    {
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 0;
        $isTestMode = isset($data['is_test_mode']) ? (int)$data['is_test_mode'] : 0;
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
        
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;

        $settingsJson = null;
        if (isset($data['settings']) && is_array($data['settings'])) {
            $settingsJson = json_encode($data['settings'], JSON_UNESCAPED_UNICODE);
        }

        return self::execute(
            "UPDATE shop_methods SET `name` = ?, `description` = ?, `is_active` = ?, `is_test_mode` = ?, `sort_order` = ?, `settings` = ?, `updated_at` = NOW() WHERE `id` = ?",
            [$name, $description, $isActive, $isTestMode, $sortOrder, $settingsJson, $id]
        );
    }

    /**
     * Отримати кількість замовлень
     * 
     * @return int
     */
    public static function count_order()
    {
        $result = self::query("SELECT COUNT(*) as count FROM orders");
        return !empty($result) ? $result[0]['count'] : 0;
    }

    /**
     * Отримати кількість товару
     * 
     * @return int
     */
    public static function count_products()
    {
        $result = self::query("SELECT COUNT(*) as count FROM products");
        return !empty($result) ? $result[0]['count'] : 0;
    }

        /**
     * Отримати суми замовлень зі статусом 'completed'
     * 
     * @return int
     */
    public static function total_sales()
    {
        $result = self::query("SELECT SUM(total) as total_sum FROM orders WHERE 
            status = ?", ['completed']);

        //return $result['total_sum'] ?? 0;
        return !empty($result) ? $result[0]['total_sum'] : 0;
    }

    /**
     * Отримати 5 замовлень зі статусом 'new'
     * 
     * @return int
     */
    public static function order_new()
    {
        $result = self::query(
            "SELECT id, customer_name, created_at, total, status 
            FROM orders 
            WHERE status = ? 
            ORDER BY created_at DESC 
            LIMIT 5", ['new']);
        return !empty($result) ? $result[0]['total_sum'] : 0;
    }
}



