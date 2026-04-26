<?php

namespace App\Models;

use App\Core\Database\DB;
use PDO;

class CrmUserService
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        DB::query(
            "CREATE TABLE IF NOT EXISTS crm_user_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                description VARCHAR(255) NOT NULL,
                meta JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_crm_activity_user_created (user_id, created_at),
                CONSTRAINT fk_crm_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        DB::query(
            "CREATE TABLE IF NOT EXISTS crm_user_bonus (
                user_id INT PRIMARY KEY,
                balance INT NOT NULL DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_crm_bonus_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        DB::query(
            "CREATE TABLE IF NOT EXISTS crm_user_subscriptions (
                user_id INT PRIMARY KEY,
                marketing_email TINYINT(1) NOT NULL DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_crm_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        DB::query(
            "CREATE TABLE IF NOT EXISTS crm_user_action_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                admin_id INT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                reason TEXT NOT NULL,
                old_value TEXT NULL,
                new_value TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_crm_audit_user_created (user_id, created_at),
                CONSTRAINT fk_crm_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_crm_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$schemaReady = true;
    }

    public static function getUserStats(int $userId): array
    {
        $row = DB::query(
            "SELECT COUNT(*) AS orders_count,
                    COALESCE(SUM(total), 0) AS ltv,
                    COALESCE(AVG(total), 0) AS average_check,
                    MAX(created_at) AS last_order_at
             FROM orders
             WHERE user_id = ?",
            [$userId]
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'orders_count' => (int) ($row['orders_count'] ?? 0),
            'ltv' => (float) ($row['ltv'] ?? 0),
            'average_check' => (float) ($row['average_check'] ?? 0),
            'last_order_at' => $row['last_order_at'] ?? null,
        ];
    }

    public static function getOrders(int $userId, int $limit = 20): array
    {
        return DB::query(
            "SELECT id, total, status, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getLiveCart(int $userId): array
    {
        return DB::query(
            "SELECT c.id, c.quantity, p.id AS product_id, p.name AS product_name, p.price
             FROM cart c
             INNER JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ?
             ORDER BY c.updated_at DESC, c.id DESC",
            [$userId]
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getWishlist(int $userId): array
    {
        return DB::query(
            "SELECT p.id AS product_id, p.name AS product_name
             FROM favorites f
             INNER JOIN products p ON p.id = f.product_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC",
            [$userId]
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getLocations(int $userId): array
    {
        $rows = DB::query(
            "SELECT delivery_address
             FROM orders
             WHERE user_id = ? AND delivery_address IS NOT NULL AND delivery_address <> ''
             GROUP BY delivery_address
             ORDER BY MAX(created_at) DESC",
            [$userId]
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $addresses = array_values(array_filter(array_map(static fn(array $row): string => trim((string) ($row['delivery_address'] ?? '')), $rows)));

        return [
            'primary' => $addresses[0] ?? null,
            'additional' => count($addresses) > 1 ? array_slice($addresses, 1) : [],
        ];
    }

    public static function recordActivity(int $userId, string $eventType, string $description, array $meta = []): void
    {
        self::ensureSchema();

        DB::query(
            'INSERT INTO crm_user_activity_logs (user_id, event_type, description, meta) VALUES (?, ?, ?, ?)',
            [$userId, $eventType, $description, empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    public static function getActivity(int $userId, int $limit = 30): array
    {
        self::ensureSchema();

        return DB::query(
            "SELECT event_type, description, created_at
             FROM crm_user_activity_logs
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getBonusBalance(int $userId): int
    {
        self::ensureSchema();

        $row = DB::query('SELECT balance FROM crm_user_bonus WHERE user_id = ? LIMIT 1', [$userId])->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) ($row['balance'] ?? 0) : 0;
    }

    public static function adjustBonus(int $userId, int $adminId, int $delta, string $reason): int
    {
        self::ensureSchema();

        DB::$pdo->beginTransaction();
        try {
            $current = self::getBonusBalance($userId);
            $newBalance = $current + $delta;

            DB::query(
                'INSERT INTO crm_user_bonus (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = VALUES(balance)',
                [$userId, $newBalance]
            );

            self::recordAudit($userId, $adminId, 'bonus_adjust', $reason, (string) $current, (string) $newBalance);
            self::recordActivity($userId, 'bonus_adjust', 'Бонусний баланс змінено: ' . ($delta >= 0 ? '+' : '') . $delta, ['delta' => $delta, 'new_balance' => $newBalance]);

            DB::$pdo->commit();

            return $newBalance;
        } catch (\Throwable $e) {
            if (DB::$pdo->inTransaction()) {
                DB::$pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function getMarketingEmailSubscription(int $userId): bool
    {
        self::ensureSchema();

        $row = DB::query('SELECT marketing_email FROM crm_user_subscriptions WHERE user_id = ? LIMIT 1', [$userId])->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['marketing_email'] ?? 0) === 1;
    }

    public static function setMarketingEmailSubscription(int $userId, bool $enabled): void
    {
        self::ensureSchema();

        DB::query(
            'INSERT INTO crm_user_subscriptions (user_id, marketing_email) VALUES (?, ?) ON DUPLICATE KEY UPDATE marketing_email = VALUES(marketing_email)',
            [$userId, $enabled ? 1 : 0]
        );
    }

    public static function recordAudit(int $userId, int $adminId, string $actionType, string $reason, ?string $oldValue = null, ?string $newValue = null): void
    {
        self::ensureSchema();

        DB::query(
            'INSERT INTO crm_user_action_audit (user_id, admin_id, action_type, reason, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $adminId, $actionType, trim($reason), $oldValue, $newValue]
        );
    }
}
