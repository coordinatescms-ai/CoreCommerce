<?php

namespace App\Services;

use App\Core\Database\DB;
use App\Models\Setting;
use XMLWriter;

/**
 * SitemapService — генерація XML Sitemap із розбиттям по файлах.
 *
 * Архітектура:
 *   sitemap.xml              — Sitemap Index (посилання на всі файли)
 *   sitemap-static.xml(.gz)  — статичні сторінки (головна, каталог, пошук…)
 *   sitemap-categories.xml(.gz)
 *   sitemap-pages.xml(.gz)   — CMS-сторінки
 *   sitemap-products-1.xml(.gz), sitemap-products-2.xml(.gz), …
 *
 * Принципи роботи з пам'яттю:
 *   - XMLWriter пише безпосередньо у файл на диск (openURI), не в RAM.
 *   - Товари вибираються батчами по BATCH_SIZE через WHERE id > $lastId,
 *     тому пікове споживання RAM = один батч × розмір рядка БД.
 */
class SitemapService
{
    // ── Налаштування ─────────────────────────────────────────────────────────

    /** Максимум URL в одному файлі (Google limit = 50 000, беремо запас). */
    private const MAX_URLS_PER_FILE = 40_000;

    /** Розмір батчу для вибірки товарів. */
    private const BATCH_SIZE = 2_000;

    // ── Публічне API ─────────────────────────────────────────────────────────

    /**
     * Запустити повну генерацію. Повертає масив зі статистикою.
     *
     * @param string $outputDir  Абсолютний шлях до папки для збереження файлів
     * @param string|null $baseUrl  Базовий URL сайту (якщо null — беремо з Settings)
     * @return array{files: string[], counts: array<string,int>, time: float}
     */
    public static function generate(string $outputDir, ?string $baseUrl = null): array
    {
        $start   = microtime(true);
        $baseUrl = rtrim($baseUrl ?? (string) Setting::get('site_url', 'https://example.com'), '/');
        $outputDir = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true)) {
            throw new \RuntimeException("Cannot create output dir: $outputDir");
        }

        $sitemapFiles = []; // [filename => lastmod]
        $counts       = [];

        // 1. Статичні сторінки
        [$file, $count] = self::generateStatic($outputDir, $baseUrl);
        $sitemapFiles[$file] = date('c');
        $counts['static']    = $count;

        // 2. Категорії
        [$file, $count] = self::generateCategories($outputDir, $baseUrl);
        $sitemapFiles[$file] = date('c');
        $counts['categories'] = $count;

        // 3. CMS-сторінки
        [$file, $count] = self::generatePages($outputDir, $baseUrl);
        $sitemapFiles[$file] = date('c');
        $counts['pages'] = $count;

        // 4. Товари (батчами, можлива генерація кількох файлів)
        [$productFiles, $count] = self::generateProducts($outputDir, $baseUrl);
        foreach ($productFiles as $pf) {
            $sitemapFiles[$pf] = date('c');
        }
        $counts['products'] = $count;

        // 5. Sitemap Index (головний файл)
        self::generateIndex($outputDir, $baseUrl, $sitemapFiles);

        return [
            'files'  => array_keys($sitemapFiles),
            'counts' => $counts,
            'time'   => round(microtime(true) - $start, 2),
        ];
    }

    // ── Генератори окремих файлів ─────────────────────────────────────────────

    /**
     * Статичні сторінки: головна, каталог, пошук, кошик.
     * @return array{string, int}  [filename, urlCount]
     */
    private static function generateStatic(string $dir, string $base): array
    {
        $urls = [
            ['loc' => '/',         'lastmod' => date('c')],
            ['loc' => '/products', 'lastmod' => date('c')],
            ['loc' => '/search',   'lastmod' => date('c')],
            ['loc' => '/cart',     'lastmod' => date('c')],
        ];

        $filename = 'sitemap-static.xml';
        $writer   = self::openWriter($dir . $filename);

        foreach ($urls as $url) {
            self::writeUrl($writer, $base . $url['loc'], $url['lastmod']);
        }

        self::closeWriter($writer);
        self::compress($dir . $filename);

        return [$filename, count($urls)];
    }

    /**
     * Категорії — всі активні, один файл.
     * @return array{string, int}
     */
    private static function generateCategories(string $dir, string $base): array
    {
        $filename = 'sitemap-categories.xml';
        $writer   = self::openWriter($dir . $filename);
        $count    = 0;

        // Категорії компактні (зазвичай < 10 000), вибираємо разом
        $rows = DB::query(
            "SELECT slug, path, updated_at
             FROM categories
             WHERE is_active = 1
             ORDER BY id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            // Використовуємо path (вкладений ЧПУ) якщо є, інакше slug
            $urlPath = !empty($row['path'])
                ? ltrim($row['path'], '/')
                : $row['slug'];

            self::writeUrl(
                $writer,
                $base . '/category/' . $urlPath,
                self::w3cDate($row['updated_at'])
            );
            $count++;
        }

        self::closeWriter($writer);
        self::compress($dir . $filename);

        return [$filename, $count];
    }

    /**
     * CMS-сторінки — всі активні.
     * @return array{string, int}
     */
    private static function generatePages(string $dir, string $base): array
    {
        $filename = 'sitemap-pages.xml';
        $writer   = self::openWriter($dir . $filename);
        $count    = 0;

        $rows = DB::query(
            "SELECT slug, updated_at
             FROM pages
             WHERE is_active = 1
             ORDER BY id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            self::writeUrl(
                $writer,
                $base . '/pages/' . $row['slug'],
                self::w3cDate($row['updated_at'])
            );
            $count++;
        }

        self::closeWriter($writer);
        self::compress($dir . $filename);

        return [$filename, $count];
    }

    /**
     * Товари — батчева вибірка по BATCH_SIZE, авторозбиття на кілька файлів.
     *
     * Стратегія: WHERE id > $lastId ORDER BY id ASC LIMIT BATCH_SIZE
     * Це швидше ніж OFFSET при великих таблицях (використовує PRIMARY KEY).
     *
     * @return array{string[], int}  [[filenames...], totalCount]
     */
    private static function generateProducts(string $dir, string $base): array
    {
        $fileIndex = 1;         // номер поточного файлу
        $urlsInFile = 0;        // URL у поточному файлі
        $totalCount = 0;        // загальна кількість
        $lastId     = 0;        // курсор для батчевої вибірки
        $filenames  = [];

        // Відкриваємо перший файл
        $filename = "sitemap-products-{$fileIndex}.xml";
        $writer   = self::openWriter($dir . $filename);
        $filenames[] = $filename;

        do {
            // Батч: наступні BATCH_SIZE товарів після $lastId
            $batch = DB::query(
                "SELECT id, slug, updated_at
                 FROM products
                 WHERE id > ? AND is_visible = 1
                 ORDER BY id ASC
                 LIMIT " . self::BATCH_SIZE,
                [$lastId]
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($batch as $row) {
                // Перевіряємо ліміт файлу перед записом
                if ($urlsInFile >= self::MAX_URLS_PER_FILE) {
                    // Закриваємо поточний файл і відкриваємо наступний
                    self::closeWriter($writer);
                    self::compress($dir . $filename);

                    $fileIndex++;
                    $urlsInFile = 0;
                    $filename   = "sitemap-products-{$fileIndex}.xml";
                    $writer     = self::openWriter($dir . $filename);
                    $filenames[] = $filename;
                }

                self::writeUrl(
                    $writer,
                    $base . '/product/' . $row['slug'],
                    self::w3cDate($row['updated_at'])
                );

                $urlsInFile++;
                $totalCount++;
                $lastId = (int) $row['id']; // зсуваємо курсор
            }

        } while (count($batch) === self::BATCH_SIZE); // якщо менше BATCH_SIZE — це останній батч

        // Закриваємо останній файл
        self::closeWriter($writer);
        self::compress($dir . $filename);

        return [$filenames, $totalCount];
    }

    /**
     * Sitemap Index — головний файл з посиланнями на всі файли.
     *
     * @param array<string,string> $files  [filename => lastmod]
     */
    private static function generateIndex(string $dir, string $base, array $files): void
    {
        $path = $dir . 'sitemap.xml';
        $w    = new XMLWriter();
        $w->openURI($path);
        $w->startDocument('1.0', 'UTF-8');
        $w->setIndent(true);
        $w->setIndentString('  ');

        $w->startElement('sitemapindex');
        $w->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($files as $filename => $lastmod) {
            // Посилаємось на .xml.gz якщо він існує
            $gz = $filename . '.gz';
            $publicFile = file_exists($dir . $gz) ? $gz : $filename;

            $w->startElement('sitemap');
            $w->writeElement('loc', htmlspecialchars($base . '/sitemaps/' . $publicFile, ENT_XML1, 'UTF-8'));
            $w->writeElement('lastmod', $lastmod);
            $w->endElement();
        }

        $w->endElement(); // sitemapindex
        $w->endDocument();
        $w->flush();

        // Копіюємо sitemap.xml в корінь public (де його шукають боти)
        @copy($path, dirname($dir) . DIRECTORY_SEPARATOR . 'sitemap.xml');
    }

    // ── XMLWriter хелпери ─────────────────────────────────────────────────────

    /**
     * Відкрити новий XML-файл і записати заголовок.
     * openURI() → XMLWriter пише потоково на диск, не в пам'ять.
     */
    private static function openWriter(string $path): XMLWriter
    {
        $w = new XMLWriter();

        if (!$w->openURI($path)) {
            throw new \RuntimeException("XMLWriter cannot open file: $path");
        }

        $w->startDocument('1.0', 'UTF-8');
        $w->setIndent(true);
        $w->setIndentString('  ');

        $w->startElement('urlset');
        $w->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $w;
    }

    /** Закрити urlset і файл. */
    private static function closeWriter(XMLWriter $w): void
    {
        $w->endElement(); // urlset
        $w->endDocument();
        $w->flush();
    }

    /** Записати один тег <url>. */
    private static function writeUrl(XMLWriter $w, string $loc, string $lastmod): void
    {
        $w->startElement('url');
        $w->writeElement('loc',     htmlspecialchars($loc, ENT_XML1, 'UTF-8'));
        $w->writeElement('lastmod', $lastmod);
        $w->endElement();
    }

    // ── Утиліти ───────────────────────────────────────────────────────────────

    /**
     * Стиснути файл у .gz і видалити оригінал.
     * gzopen/gzwrite записує побайтово із буфером 64KB — не вантажить RAM.
     */
    private static function compress(string $filePath): void
    {
        $gzPath = $filePath . '.gz';

        $in  = fopen($filePath, 'rb');
        $out = gzopen($gzPath, 'wb9');

        if (!$in || !$out) {
            return; // якщо gz не вдався — залишаємо .xml
        }

        while (!feof($in)) {
            gzwrite($out, fread($in, 65536)); // 64 KB буфер
        }

        fclose($in);
        gzclose($out);

        // Видаляємо оригінал лише якщо .gz успішно створено
        if (file_exists($gzPath) && filesize($gzPath) > 0) {
            @unlink($filePath);
        }
    }

    /**
     * Перетворити datetime БД у формат W3C для <lastmod>.
     * Приклад: "2025-06-01 12:00:00" → "2025-06-01T12:00:00+02:00"
     */
    private static function w3cDate(?string $dbDatetime): string
    {
        if (empty($dbDatetime)) {
            return date('c');
        }

        try {
            return (new \DateTimeImmutable($dbDatetime))->format(\DateTimeInterface::ATOM);
        } catch (\Throwable) {
            return date('c');
        }
    }
}
