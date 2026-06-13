<?php

namespace App\Core\Database;

/**
 * Файловий кеш результатів SQL-запитів.
 *
 * Принцип:
 *  — Результат запиту зберігається у storage/cache/queries/
 *  — Ключ кешу = md5(sql + serialize(params))
 *  — Кожен запис має TTL (час життя в секундах)
 *  — Інвалідація: за тегами (наприклад 'products') або повна
 *
 * Використання через DB::cached():
 *
 *   $rows = DB::cached(
 *       'SELECT * FROM products WHERE is_visible = 1',
 *       [],
 *       ttl: 300,        // 5 хвилин
 *       tags: ['products']
 *   );
 *
 * Очищення кешу при зміні даних:
 *   QueryCache::flush('products');  // очистити всі записи з тегом 'products'
 *   QueryCache::flushAll();         // очистити весь кеш запитів
 */
class QueryCache
{
    private static string $cacheDir = '';

    private static function dir(): string
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = dirname(__DIR__, 3) . '/storage/cache/queries';
        }
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        return self::$cacheDir;
    }

    /**
     * Отримати результат з кешу або виконати запит і закешувати.
     *
     * @param  string   $sql    SQL-запит
     * @param  array    $params Параметри запиту
     * @param  int      $ttl    Час життя в секундах (0 = без кешу)
     * @param  string[] $tags   Теги для групової інвалідації
     * @return array
     */
    public static function remember(string $sql, array $params = [], int $ttl = 60, array $tags = []): array
    {
        if ($ttl <= 0) {
            return DB::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        }

        $key  = self::makeKey($sql, $params);
        $file = self::dir() . '/' . $key . '.cache';

        // Читаємо кеш
        if (is_file($file)) {
            $data = @unserialize(file_get_contents($file));
            if (is_array($data) && isset($data['expires']) && $data['expires'] > time()) {
                return $data['rows'];
            }
        }

        // Виконуємо запит і зберігаємо
        $rows = DB::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        self::write($key, $rows, $ttl, $tags);

        return $rows;
    }

    /**
     * Запам'ятати довільне значення (не тільки SQL-результат).
     *
     * @param  string   $key   Ключ кешу
     * @param  callable $callback  Функція що повертає значення
     * @param  int      $ttl   Час життя
     * @param  string[] $tags  Теги
     * @return mixed
     */
    public static function rememberValue(string $key, callable $callback, int $ttl = 60, array $tags = []): mixed
    {
        $file = self::dir() . '/' . md5($key) . '.cache';

        if (is_file($file)) {
            $data = @unserialize(file_get_contents($file));
            if (is_array($data) && isset($data['expires']) && $data['expires'] > time()) {
                return $data['rows'];
            }
        }

        $value = $callback();
        self::write(md5($key), $value, $ttl, $tags);
        return $value;
    }

    /**
     * Видалити всі записи кешу з певним тегом.
     */
    public static function flush(string $tag): int
    {
        $count   = 0;
        $tagFile = self::dir() . '/tags/' . md5($tag) . '.tag';

        if (!is_file($tagFile)) {
            return 0;
        }

        $keys = @file($tagFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($keys as $key) {
            $file = self::dir() . '/' . $key . '.cache';
            if (is_file($file) && unlink($file)) {
                $count++;
            }
        }

        // Видаляємо сам тег-файл
        @unlink($tagFile);

        return $count;
    }

    /**
     * Видалити весь кеш запитів.
     */
    public static function flushAll(): int
    {
        $count = 0;
        $files = glob(self::dir() . '/*.cache') ?: [];
        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        // Чистимо теги
        $tagFiles = glob(self::dir() . '/tags/*.tag') ?: [];
        foreach ($tagFiles as $f) {
            unlink($f);
        }
        return $count;
    }

    /**
     * Статистика кешу.
     */
    public static function stats(): array
    {
        $files   = glob(self::dir() . '/*.cache') ?: [];
        $valid   = 0;
        $expired = 0;
        $size    = 0;

        foreach ($files as $file) {
            $size += filesize($file);
            $data = @unserialize(file_get_contents($file));
            if (is_array($data) && isset($data['expires'])) {
                if ($data['expires'] > time()) {
                    $valid++;
                } else {
                    $expired++;
                }
            }
        }

        return [
            'total'   => count($files),
            'valid'   => $valid,
            'expired' => $expired,
            'size_kb' => round($size / 1024, 1),
        ];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private static function makeKey(string $sql, array $params): string
    {
        return md5($sql . '|' . serialize($params));
    }

    private static function write(string $key, mixed $value, int $ttl, array $tags): void
    {
        $file = self::dir() . '/' . $key . '.cache';
        file_put_contents($file, serialize([
            'expires' => time() + $ttl,
            'rows'    => $value,
        ]), LOCK_EX);

        // Реєструємо ключ у тег-файлах
        foreach ($tags as $tag) {
            $tagDir  = self::dir() . '/tags';
            if (!is_dir($tagDir)) {
                mkdir($tagDir, 0755, true);
            }
            $tagFile = $tagDir . '/' . md5($tag) . '.tag';
            file_put_contents($tagFile, $key . "\n", FILE_APPEND | LOCK_EX);
        }
    }
}
