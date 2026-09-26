<?php

namespace App\Core\Database;

use PDO;
use PDOStatement;

class DB
{
    private static PDO $pdo;

    /**
     * Ініціалізація з'єднання (викликається один раз у public/index.php).
     */
    public static function connect(string $dsn, string $user, string $pass): void
    {
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /**
     * Виконати підготовлений запит з автоматичним визначенням типів параметрів.
     * Підтримує як позиційні (?) так і іменовані (:name) параметри.
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        self::assertCallerAllowed();

        $stmt = self::$pdo->prepare($sql);

        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }

            if (is_string($key)) {
                // Named параметр: ':name' або 'name'
                $paramKey = str_starts_with($key, ':') ? $key : ':' . $key;
                $stmt->bindValue($paramKey, $value, $type);
            } else {
                // Позиційний параметр: індекс з 1
                $stmt->bindValue($key + 1, $value, $type);
            }
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Псевдонім query() для зворотної сумісності.
     */
    public static function execute(string $sql, array $params = []): PDOStatement
    {
        self::assertCallerAllowed();

        return self::query($sql, $params);
    }

    // ── Транзакції ────────────────────────────────────────────────────────────

    public static function beginTransaction(): bool
    {
        return self::$pdo->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::$pdo->commit();
    }

    public static function rollBack(): bool
    {
        return self::$pdo->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::$pdo->inTransaction();
    }

    // ── Утиліти ───────────────────────────────────────────────────────────────

    public static function lastInsertId(): int
    {
        return (int) self::$pdo->lastInsertId();
    }

    public static function quote(string $value): string
    {
        return self::$pdo->quote($value);
    }

    /**
     * Виконати запит з файловим кешем результату.
     *
     * @param  string   $sql    SQL-запит
     * @param  array    $params Параметри
     * @param  int      $ttl    Час життя кешу в секундах (0 = без кешу)
     * @param  string[] $tags   Теги для групової інвалідації
     * @return array
     */
    public static function cached(string $sql, array $params = [], int $ttl = 60, array $tags = []): array
    {
        return QueryCache::remember($sql, $params, $ttl, $tags);
    }

    /**
     * Виконати SQL без параметрів (наприклад, ALTER TABLE при оновленнях).
     * Використовуйте тільки з довіреними рядками — без user-input.
     */
    public static function exec(string $sql): int|false
    {
        self::assertCallerAllowed();

        return self::$pdo->exec($sql);
    }

    // ── Зворотна сумісність ───────────────────────────────────────────────────

    /**
     * @deprecated Використовуйте DB::query() або DB::beginTransaction() тощо.
     *             Залишено лише для зворотної сумісності старого коду.
     */
    public static function getInstance(): static
    {
        return new static();
    }

    /**
     * Підтримка виклику через екземпляр: $db->query(...).
     * @deprecated
     */
    public function __call(string $name, array $args): mixed
    {
        if (method_exists(static::class, $name)) {
            return static::$name(...$args);
        }
        throw new \BadMethodCallException("DB::$name() не існує.");
    }

    // ── Захист від прямого доступу з коду плагінів ──────────────────────────────

    /**
     * Плагіни мають працювати з БД лише через App\Core\Plugin\PluginDB (обмежений
     * проксі: SELECT з публічних таблиць, INSERT/UPDATE/DELETE лише у власних
     * таблицях плагіна). DB::query()/execute()/exec() публічні (потрібно ядру),
     * тому раніше будь-який код плагіна міг викликати їх напряму й повністю
     * обійти whitelist з PluginDB.
     *
     * Тут перевіряємо файл БЕЗПОСЕРЕДНЬОГО викликача (debug_backtrace) — якщо він
     * лежить у папці /plugins/, кидаємо виняток. Викликам зсередини самого DB.php
     * (execute() -> query()) і з PluginDB довіряємо: їх або вже перевірено на межі
     * публічного виклику, або вони йдуть через контрольований проксі.
     *
     * Це не абсолютний sandboxing (плагін — це PHP-код у тому ж процесі, і
     * достатньо завзятий автор може обійти майже будь-яку перевірку рівня
     * застосунку, наприклад через Reflection). Мета — закрити очевидний і
     * випадковий шлях в обхід PluginDB, а не гарантувати ізоляцію від
     * навмисно зловмисного плагіна.
     */
    private static function assertCallerAllowed(): void
    {
        // Frame 0 — виклик assertCallerAllowed() зсередини query()/execute()/exec()
        // (тобто сам DB.php). Frame 1 — той, хто фактично викликав query()/execute()/exec().
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callerFile = $trace[1]['file'] ?? '';

        if ($callerFile === '') {
            // Невідомий викликач (напр. eval()) — не блокуємо ядро наосліп.
            return;
        }

        $callerReal = str_replace('\\', '/', realpath($callerFile) ?: $callerFile);
        $ownFile    = str_replace('\\', '/', __FILE__);

        // Внутрішня делегація всередині самого DB.php (execute() -> query()) —
        // виклик вже перевірено на межі публічного методу, яким скористався клієнт.
        if ($callerReal === $ownFile) {
            return;
        }

        // Виклик з контрольованого проксі PluginDB — довірений.
        if (str_ends_with($callerReal, '/app/Core/Plugin/PluginDB.php')) {
            return;
        }

        $pluginsDir = str_replace('\\', '/', realpath(__DIR__ . '/../../../plugins') ?: '');
        if ($pluginsDir !== '' && str_starts_with($callerReal, $pluginsDir . '/')) {
            throw new \App\Core\Plugin\PluginSecurityException(
                'Прямі виклики DB::query()/execute()/exec() з коду плагінів заборонені. Використовуйте PluginDB ($pluginDB->select() / $pluginDB->write()).'
            );
        }
    }
}
