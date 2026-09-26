<?php

namespace App\Core\Plugin;

use App\Core\Database\DB;

/**
 * Обмежений проксі до бази даних для плагінів.
 *
 * Пісочниця рівня БД:
 *  — Плагін працює лише зі своїми таблицями (префікс plugin_{slug}_)
 *  — SELECT дозволено лише на таблицях з READABLE_CORE_TABLES або з власним
 *    префіксом плагіна — перевіряється по іменах таблиць у самому запиті
 *  — INSERT/UPDATE/DELETE — тільки у власних таблицях плагіна
 *  — Транзакції дозволені
 *  — Логування всіх запитів плагіна
 *
 * ВАЖЛИВО: private $pdo у App\Core\Database\DB сам по собі НЕ є межею
 * безпеки — DB::query()/execute()/exec() публічні (потрібні ядру) і
 * теоретично доступні звідки завгодно. Реальна межа — це перевірка
 * викликача в App\Core\Database\DB::assertCallerAllowed(): будь-який
 * прямий виклик DB::query()/execute()/exec() з файлу під /plugins/
 * (в обхід цього класу) блокується там, на рівні DB.
 */
class PluginDB
{
    // Таблиці ядра, які плагін може тільки читати
    private const READABLE_CORE_TABLES = [
        'products', 'categories', 'orders', 'order_items',
        'users', 'settings', 'currencies', 'attributes',
        'product_attributes', 'product_stocks', 'shop_methods',
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
                sprintf(__('plugin_db_select_only'), $this->slug)
            );
        }

        foreach ($this->extractSelectTableNames($sql) as $table) {
            $isOwnTable     = str_starts_with($table, $this->tablePrefix);
            $isReadableCore = in_array($table, self::READABLE_CORE_TABLES, true);

            if (!$isOwnTable && !$isReadableCore) {
                throw new PluginSecurityException(
                    sprintf(__('plugin_db_select_forbidden_table'), $this->slug, $table)
                );
            }
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
                sprintf(__('plugin_db_use_select'), $this->slug)
            );
        }

        $tables = $this->extractTableNames($sql);
        foreach ($tables as $table) {
            if (!str_starts_with($table, $this->tablePrefix)) {
                throw new PluginSecurityException(
                    sprintf(__('plugin_db_write_only_prefix'), $this->slug, $this->tablePrefix, $table)
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
     *
     * Патерни прив'язані до ПОЧАТКУ інструкції (^), а не шукаються будь-де
     * в тексті: інакше конструкція "... ON UPDATE CURRENT_TIMESTAMP" у
     * визначенні колонки (типовий MySQL-ідіом для updated_at) хибно
     * розпізнавалась як окрема UPDATE-інструкція з "таблицею"
     * CURRENT_TIMESTAMP і блокувала легітимний CREATE TABLE плагіна.
     */
    private function extractTableNames(string $sql): array
    {
        $tables = [];
        $sql    = preg_replace('/\s+/', ' ', strtolower(trim($sql)));

        // INSERT INTO `table`
        if (preg_match('/^insert\s+(?:ignore\s+)?into\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }
        // UPDATE `table`
        if (preg_match('/^update\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }
        // DELETE FROM `table`
        if (preg_match('/^delete\s+from\s+[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }
        // CREATE TABLE / DROP TABLE
        if (preg_match('/^(?:create|drop)\s+table\s+(?:if\s+(?:not\s+)?exists\s+)?[`"]?(\w+)[`"]?/i', $sql, $m)) {
            $tables[] = $m[1];
        }

        return array_unique($tables);
    }

    /**
     * Витягує назви таблиць з FROM/JOIN у SELECT-запиті (включно зі старим
     * стилем "FROM a, b" через кому). Не є повноцінним SQL-парсером — цього
     * достатньо, щоб перевірити whitelist для звичайних SELECT-запитів
     * плагінів; підзапити в дужках `FROM (SELECT ...) x` тут не зачіпають
     * реальних таблиць і просто не потраплять у результат.
     */
    private function extractSelectTableNames(string $sql): array
    {
        $tables     = [];
        $normalized = preg_replace('/\s+/', ' ', strtolower(trim($sql)));

        // FROM `table` / JOIN `table` (включно з подальшими JOIN)
        if (preg_match_all('/\b(?:from|join)\s+[`"]?(\w+)[`"]?/', $normalized, $m)) {
            $tables = array_merge($tables, $m[1]);
        }

        // FROM a, b, c — старий стиль неявного JOIN через кому
        if (preg_match('/\bfrom\s+[`"]?\w+[`"]?\s*((?:,\s*[`"]?\w+[`"]?)+)/', $normalized, $m)) {
            foreach (explode(',', $m[1]) as $part) {
                $part = trim($part, " `\"");
                if ($part !== '') {
                    $tables[] = $part;
                }
            }
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
