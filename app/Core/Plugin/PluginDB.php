<?php

namespace App\Core\Plugin;

use App\Core\Database\DB;

/**
 * Обмежений проксі до бази даних для плагінів.
 *
 * Пісочниця рівня БД:
 *  — Плагін працює лише зі своїми таблицями (префікс plugin_{slug}_)
 *  — SELECT дозволено на публічних таблицях (products, categories, orders)
 *  — INSERT/UPDATE/DELETE — тільки у власних таблицях плагіна
 *  — Прямий доступ до DB::$pdo заборонений (він private)
 *  — Транзакції дозволені
 *  — Логування всіх запитів плагіна
 */
class PluginDB
{
    // Таблиці ядра, які плагін може тільки читати
    private const READABLE_CORE_TABLES = [
        'products', 'categories', 'orders', 'order_items',
        'users', 'settings', 'currencies', 'attributes',
        'product_attributes', 'product_stocks',
    ];

    // Таблиці ядра, заборонені для будь-яких змін плагіном
    private const PROTECTED_TABLES = [
        'plugins', 'plugin_settings', 'users', 'settings',
        'currencies', 'login_attempts',
    ];

    private string $slug;
    private string $tablePrefix;
    private array  $queryLog = [];

    public function __construct(string $pluginSlug)
    {
        $this->slug        = $pluginSlug;
        $this->tablePrefix = 'plugin_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($pluginSlug)) . '_';
    }

    /**
     * Виконати SELECT-запит.
     * Дозволено: власні таблиці + публічні таблиці ядра (тільки читання).
     */
    public function select(string $sql, array $params = []): array
    {
        $sql = trim($sql);

        if (!$this->isSelectQuery($sql)) {
            throw new PluginSecurityException(
                "Плагін «{$this->slug}»: метод select() приймає тільки SELECT-запити."
            );
        }

        $this->logQuery('SELECT', $sql, $params);
        return DB::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Виконати INSERT/UPDATE/DELETE — тільки у власних таблицях плагіна.
     */
    public function write(string $sql, array $params = []): \PDOStatement
    {
        $sql = trim($sql);

        if ($this->isSelectQuery($sql)) {
            throw new PluginSecurityException(
                "Плагін «{$this->slug}»: використовуйте select() для читання даних."
            );
        }

        $tables = $this->extractTableNames($sql);
        foreach ($tables as $table) {
            if (!str_starts_with($table, $this->tablePrefix)) {
                throw new PluginSecurityException(
                    "Плагін «{$this->slug}»: запис дозволений тільки у таблиці з префіксом «{$this->tablePrefix}». " .
                    "Спроба запису в «{$table}»."
                );
            }
        }

        $this->logQuery('WRITE', $sql, $params);
        return DB::query($sql, $params);
    }

    /**
     * Отримати повну назву таблиці плагіна (з префіксом).
     */
    public function table(string $name): string
    {
        return $this->tablePrefix . preg_replace('/[^a-z0-9_]/', '_', strtolower($name));
    }

    /**
     * Отримати налаштування плагіна з plugin_settings.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $row = DB::query(
            'SELECT `value` FROM plugin_settings WHERE plugin_slug = ? AND `key` = ? LIMIT 1',
            [$this->slug, $key]
        )->fetch(\PDO::FETCH_ASSOC);

        return $row !== false ? $row['value'] : $default;
    }

    /**
     * Зберегти налаштування плагіна в plugin_settings.
     */
    public function setSetting(string $key, mixed $value): void
    {
        DB::query(
            "INSERT INTO plugin_settings (plugin_slug, `key`, `value`, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
            [$this->slug, $key, (string)$value, (string)$value]
        );
    }

    // Транзакції
    public function beginTransaction(): bool  { return DB::beginTransaction(); }
    public function commit(): bool            { return DB::commit(); }
    public function rollBack(): bool          { return DB::rollBack(); }
    public function inTransaction(): bool     { return DB::inTransaction(); }
    public function lastInsertId(): int       { return DB::lastInsertId(); }

    /**
     * Лог запитів (доступний для дебагу плагіна).
     */
    public function getQueryLog(): array { return $this->queryLog; }

    // ── Private ───────────────────────────────────────────────────────────────

    private function isSelectQuery(string $sql): bool
    {
        return stripos(ltrim($sql), 'SELECT') === 0;
    }

    /**
     * Простий парсер назв таблиць для INSERT/UPDATE/DELETE.
     */
    private function extractTableNames(string $sql): array
    {
        $tables = [];
        $sql    = preg_replace('/\s+/', ' ', strtolower($sql));

        // INSERT INTO `table`
        if (preg_match('/insert\s+into\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }
        // UPDATE `table`
        if (preg_match('/update\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }
        // DELETE FROM `table`
        if (preg_match('/delete\s+from\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }
        // CREATE TABLE / DROP TABLE
        if (preg_match('/(?:create|drop)\s+table\s+(?:if\s+(?:not\s+)?exists\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }

        return array_unique($tables);
    }

    private function logQuery(string $type, string $sql, array $params): void
    {
        $this->queryLog[] = [
            'type'   => $type,
            'sql'    => $sql,
            'params' => $params,
            'time'   => microtime(true),
        ];
    }
}
