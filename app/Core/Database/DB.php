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
}
