<?php

namespace App\Services;

use App\Core\Database\DB;
use App\Models\Setting;

/**
 * SearchService — повнотекстовий пошук товарів.
 *
 * Архітектура спроектована для легкого переключення на
 * Elasticsearch / Manticore Search без зміни контролерів:
 * достатньо замінити реалізацію цього класу.
 *
 * Пріоритет стратегій:
 *   1. FULLTEXT MATCH...AGAINST (якщо таблиця має FT-індекс)
 *   2. LIKE-пошук по токенах (fallback)
 *   3. Fuzzy-пошук через levenshtein() (якщо нічого не знайдено)
 */
class SearchService
{
    // ── Налаштування ─────────────────────────────────────────────────────────

    private const CACHE_TTL_SECONDS  = 600;   // 10 хвилин
    private const MAX_QUERY_LENGTH   = 200;
    private const MIN_TOKEN_LENGTH   = 2;
    private const RESULTS_PER_PAGE   = 20;
    private const FUZZY_MAX_DISTANCE = 2;     // max відстань Левенштейна
    private const FUZZY_CANDIDATES   = 50;    // скільки слів перевіряємо fuzzy

    /**
     * Стоп-слова (UA + RU + EN) — не несуть змістового навантаження.
     */
    private const STOP_WORDS = [
        // UA
        'і','й','та','або','але','що','як','це','той','та','в','у','на','за',
        'до','з','із','зі','від','по','при','над','під','між','через','для',
        'про','без','після','перед','під','вже','ще','не','ні','так','де',
        'коли','якщо','хоча','бо','тому','щоб','він','вона','воно','вони',
        'ми','ви','я','ти','його','її','їх','ним','нею','нам','вам',
        // RU
        'и','в','во','не','что','он','на','я','с','со','как','а','то','все',
        'она','так','его','но','да','ты','к','у','же','вы','за','бы','по',
        'только','ее','мне','было','вот','от','меня','еще','нет','о','из',
        // EN
        'a','an','the','and','or','but','in','on','at','to','for','of','with',
        'by','from','is','it','its','be','as','are','was','were','has','have',
    ];

    // ── Головний метод пошуку ─────────────────────────────────────────────────

    /**
     * @return array{
     *   results: array,
     *   total: int,
     *   page: int,
     *   pages: int,
     *   query: string,
     *   tokens: string[],
     *   suggestion: string|null,
     *   strategy: string,
     *   from_cache: bool,
     * }
     */
    public static function search(string $rawQuery, int $page = 1, int $limit = self::RESULTS_PER_PAGE): array
    {
        $query  = self::sanitize($rawQuery);
        $page   = max(1, $page);
        $offset = ($page - 1) * $limit;

        if ($query === '') {
            return self::emptyResult($page);
        }

        // Перевіряємо кеш
        $cacheKey = self::cacheKey($query, $page, $limit);
        $cached   = self::getCache($cacheKey);
        if ($cached !== null) {
            $cached['from_cache'] = true;
            return $cached;
        }

        $tokens = self::tokenize($query);
        if (empty($tokens)) {
            return self::emptyResult($page);
        }

        // Спробуємо FULLTEXT
        [$results, $total, $strategy] = self::fullTextSearch($query, $tokens, $limit, $offset);

        // Fallback: LIKE по токенах
        if ($total === 0) {
            [$results, $total, $strategy] = self::likeSearch($tokens, $limit, $offset);
        }

        // Fuzzy fallback: levenshtein
        $suggestion = null;
        if ($total === 0) {
            $suggestion              = self::fuzzySuggest($tokens);
            [$results, $total, $strategy] = self::likeSearch(
                $suggestion ? self::tokenize($suggestion) : $tokens,
                $limit,
                $offset
            );
            $strategy = 'fuzzy';
        }

        $pages = max(1, (int) ceil($total / $limit));

        // Логуємо запит
        self::logQuery($query, $total);

        // Збільшуємо лічильник переглядів
        foreach ($results as $r) {
            self::incrementViews((int) $r['id']);
        }

        $payload = [
            'results'    => $results,
            'total'      => $total,
            'page'       => $page,
            'pages'      => $pages,
            'query'      => $query,
            'tokens'     => $tokens,
            'suggestion' => $suggestion,
            'strategy'   => $strategy,
            'from_cache' => false,
        ];

        self::setCache($cacheKey, $payload);
        return $payload;
    }

    // ── Стратегія 1: FULLTEXT MATCH...AGAINST ─────────────────────────────────

    private static function fullTextSearch(string $query, array $tokens, int $limit, int $offset): array
    {
        try {
            $boolQuery = implode(' ', array_map(fn($t) => '+' . $t . '*', $tokens));
            $skuLike   = '%' . implode('%', $tokens) . '%';
            $limSql    = (int) $limit;
            $offSql    = (int) $offset;

            $sql = "
                SELECT
                    p.*,
                    c.name AS category_name,
                    COALESCE(ps.quantity, 0) AS stock_qty,
                    (
                        MATCH(p.name)        AGAINST(:q1 IN BOOLEAN MODE) * 3
                      + MATCH(p.description) AGAINST(:q2 IN BOOLEAN MODE) * 1
                      + IF(p.sku LIKE :sku_like, 2, 0)
                      + IF(COALESCE(ps.quantity,0) > 0, 0.5, 0)
                      + LEAST(p.views_count / 1000, 1)
                    ) AS relevance
                FROM products p
                LEFT JOIN categories c  ON c.id = p.category_id
                LEFT JOIN product_stocks ps
                    ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
                    AND ps.option_id IS NULL
                WHERE
                    p.is_visible = 1
                    AND MATCH(p.name, p.description, p.sku) AGAINST(:q3 IN BOOLEAN MODE)
                HAVING relevance > 0
                ORDER BY relevance DESC, p.views_count DESC
                LIMIT {$limSql} OFFSET {$offSql}
            ";

            $rows = DB::query($sql, [
                'q1'       => $boolQuery,
                'q2'       => $boolQuery,
                'q3'       => $boolQuery,
                'sku_like' => $skuLike,
            ])->fetchAll(\PDO::FETCH_ASSOC);

            $countSql = "
                SELECT COUNT(*) FROM products p
                WHERE is_visible = 1
                  AND MATCH(name, description, sku) AGAINST(:q IN BOOLEAN MODE)
            ";
            $total = (int) DB::query($countSql, ['q' => $boolQuery])->fetchColumn();

            return [$rows, $total, 'fulltext'];

        } catch (\Throwable $e) {
            // FULLTEXT індекс відсутній або помилка — переходимо до LIKE
            return [[], 0, 'fulltext_error'];
        }
    }

    // ── Стратегія 2: LIKE по токенах ─────────────────────────────────────────

    private static function likeSearch(array $tokens, int $limit, int $offset): array
    {
        if (empty($tokens)) {
            return [[], 0, 'like'];
        }

        $conditions = [];
        $params     = [];

        foreach ($tokens as $i => $token) {
            $like = '%' . $token . '%';
            $conditions[] = "(p.name LIKE :n{$i} OR p.description LIKE :d{$i} OR p.sku LIKE :s{$i})";
            $params["n{$i}"] = $like;
            $params["d{$i}"] = $like;
            $params["s{$i}"] = $like;
        }

        $where  = implode(' AND ', $conditions);
        // LIMIT і OFFSET вставляємо напряму — вони вже int, SQL-ін'єкція неможлива
        $limSql = (int) $limit;
        $offSql = (int) $offset;

        $params['name_main'] = '%' . $tokens[0] . '%';

        $sql = "
            SELECT p.*, c.name AS category_name, COALESCE(ps.quantity,0) AS stock_qty,
                (
                    IF(p.name LIKE :name_main, 3, 0)
                  + IF(COALESCE(ps.quantity,0) > 0, 0.5, 0)
                  + LEAST(p.views_count / 1000, 1)
                ) AS relevance
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_stocks ps
                ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
                AND ps.option_id IS NULL
            WHERE p.is_visible = 1 AND {$where}
            ORDER BY relevance DESC, p.views_count DESC
            LIMIT {$limSql} OFFSET {$offSql}
        ";

        $rows = DB::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        // Count
        $countSql    = "SELECT COUNT(*) FROM products p WHERE p.is_visible = 1 AND {$where}";
        $countParams = array_filter(
            $params,
            fn($k) => $k !== 'name_main',
            ARRAY_FILTER_USE_KEY
        );
        $total = (int) DB::query($countSql, $countParams)->fetchColumn();

        return [$rows, $total, 'like'];
    }

    // ── Стратегія 3: Fuzzy suggest (levenshtein) ─────────────────────────────

    private static function fuzzySuggest(array $tokens): ?string
    {
        if (empty($tokens)) {
            return null;
        }

        // Беремо реальні слова з БД (sample)
        $dbWords = DB::query(
            "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(name,' ',n.n),' ',-1) AS word
             FROM products
             JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) n
             WHERE is_visible = 1
             HAVING LENGTH(word) >= 3
             LIMIT " . self::FUZZY_CANDIDATES
        )->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $suggestions = [];
        foreach ($tokens as $token) {
            $best     = null;
            $bestDist = PHP_INT_MAX;

            foreach ($dbWords as $word) {
                $word = mb_strtolower($word);
                $dist = levenshtein($token, $word);
                if ($dist < $bestDist && $dist <= self::FUZZY_MAX_DISTANCE) {
                    $bestDist = $dist;
                    $best     = $word;
                }
            }

            $suggestions[] = $best ?? $token;
        }

        $suggested = implode(' ', $suggestions);
        return $suggested !== implode(' ', $tokens) ? $suggested : null;
    }

    // ── Токенізація та санітизація ────────────────────────────────────────────

    public static function sanitize(string $query): string
    {
        $query = mb_strtolower(trim($query));
        $query = mb_substr($query, 0, self::MAX_QUERY_LENGTH);

        // Видаляємо спецсимволи (залишаємо літери, цифри, пробіли, дефіс)
        $query = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $query);
        $query = preg_replace('/\s+/', ' ', $query);

        return trim($query);
    }

    public static function tokenize(string $query): array
    {
        $words  = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = [];

        foreach ($words as $word) {
            $word = mb_strtolower(trim($word, '-'));
            if (
                mb_strlen($word) >= self::MIN_TOKEN_LENGTH
                && !in_array($word, self::STOP_WORDS, true)
            ) {
                $tokens[] = $word;
            }
        }

        return array_unique($tokens);
    }

    // ── Підсвітка знайдених слів ──────────────────────────────────────────────

    /**
     * Підсвітити токени в тексті HTML-тегом <mark>.
     */
    public static function highlight(string $text, array $tokens, int $maxLength = 200): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Обрізаємо до першого токена
        if (mb_strlen($text) > $maxLength && !empty($tokens)) {
            $pos = mb_stripos($text, $tokens[0]);
            if ($pos !== false) {
                $start = max(0, $pos - 60);
                $text  = ($start > 0 ? '…' : '') . mb_substr($text, $start, $maxLength) . '…';
            } else {
                $text = mb_substr($text, 0, $maxLength) . '…';
            }
        }

        foreach ($tokens as $token) {
            $safe = preg_quote(htmlspecialchars($token, ENT_QUOTES, 'UTF-8'), '/');
            $text = preg_replace(
                "/($safe)/ui",
                '<mark>$1</mark>',
                $text
            );
        }

        return $text;
    }

    // ── Популярні запити ──────────────────────────────────────────────────────

    public static function getPopularQueries(int $limit = 8): array
    {
        try {
            return DB::query(
                'SELECT query, search_count, results_count FROM search_queries
                 WHERE results_count > 0
                 ORDER BY search_count DESC LIMIT ?',
                [$limit]
            )->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Кеш ──────────────────────────────────────────────────────────────────

    private static function cacheKey(string $query, int $page, int $limit): string
    {
        return hash('sha256', "search:{$query}:p{$page}:l{$limit}");
    }

    private static function getCache(string $key): ?array
    {
        try {
            $row = DB::query(
                'SELECT results FROM search_cache WHERE query_hash = ? AND expires_at > NOW()',
                [$key]
            )->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            // Збільшуємо лічильник звернень
            DB::query(
                'UPDATE search_cache SET hits = hits + 1 WHERE query_hash = ?',
                [$key]
            );

            return json_decode($row['results'], true);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function setCache(string $key, array $payload): void
    {
        try {
            $json    = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $expires = date('Y-m-d H:i:s', time() + self::CACHE_TTL_SECONDS);
            $query   = $payload['query'];

            DB::query(
                'INSERT INTO search_cache (query_hash, query_text, results, expires_at)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE results = VALUES(results), expires_at = VALUES(expires_at), hits = hits + 1',
                [$key, $query, $json, $expires]
            );
        } catch (\Throwable) {
            // Кеш необов'язковий
        }
    }

    public static function clearExpiredCache(): int
    {
        try {
            return (int) DB::query(
                'DELETE FROM search_cache WHERE expires_at < NOW()'
            )->rowCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    // ── Лічильник переглядів ──────────────────────────────────────────────────

    private static function incrementViews(int $productId): void
    {
        try {
            DB::query(
                'UPDATE products SET views_count = views_count + 1 WHERE id = ?',
                [$productId]
            );
        } catch (\Throwable) {
            // views_count може бути відсутній (до міграції)
        }
    }

    // ── Логування запитів ─────────────────────────────────────────────────────

    private static function logQuery(string $query, int $resultsCount): void
    {
        try {
            DB::query(
                'INSERT INTO search_queries (query, results_count, search_count, last_searched)
                 VALUES (?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE
                     search_count   = search_count + 1,
                     results_count  = VALUES(results_count),
                     last_searched  = NOW()',
                [$query, $resultsCount]
            );
        } catch (\Throwable) {
            // Таблиця може бути відсутня (до міграції)
        }
    }

    // ── Хелпери ───────────────────────────────────────────────────────────────

    private static function emptyResult(int $page): array
    {
        return [
            'results'    => [],
            'total'      => 0,
            'page'       => $page,
            'pages'      => 0,
            'query'      => '',
            'tokens'     => [],
            'suggestion' => null,
            'strategy'   => 'none',
            'from_cache' => false,
        ];
    }
}
