<?php

namespace App\Services;

use App\Core\Database\DB;
use App\Models\Setting;

/**
 * Синхронізація товарів з Prom.ua.
 *
 * Підхід А — генерація XML/YML файлу (Рекомендовано для великих каталогів).
 *   Файл зберігається у storage/prom_feed.xml
 *   Prom сам забирає файл за URL за розкладом.
 *
 * Підхід Б — оновлення через API (Для миттєвої синхронізації).
 *   Зміни ціни/залишків ставляться в чергу prom_sync_queue.
 *   Черга обробляється окремо (cron або ручний запуск з адмінки).
 */
class PromSyncService
{
    private const FEED_FILE    = '/storage/prom_feed.xml';
    private const QUEUE_BATCH  = 50;   // Кількість товарів за один прогін черги
    private const MAX_ATTEMPTS = 3;    // Максимум спроб для одного товару

    private string   $feedPath;
    private string   $logFile;
    private string   $siteUrl;
    private string   $storeName;
    private PromApiClient $api;

    public function __construct()
    {
        $root            = dirname(__DIR__, 2);
        $this->feedPath  = $root . self::FEED_FILE;
        $this->logFile   = $root . '/storage/logs/prom.log';
        $this->siteUrl   = rtrim((string) Setting::get('site_url', 'https://example.com'), '/');
        $this->storeName = (string) Setting::get('store_name', 'Інтернет-магазин');
        $this->api       = new PromApiClient();
    }

    // =========================================================================
    // Підхід А: XML/YML feed
    // =========================================================================

    /**
     * Згенерувати XML-фід у форматі Prom YML та зберегти у storage/prom_feed.xml.
     * Повертає масив з результатом: ['success', 'message', 'products_count', 'file_path']
     */
    public function generateXmlFeed(): array
    {
        if (!PromApiClient::isEnabled()) {
            return ['success' => false, 'message' => 'Інтеграція з Prom.ua вимкнена.', 'products_count' => 0, 'file_path' => ''];
        }

        $products = $this->fetchProductsForFeed();

        if (empty($products)) {
            return [
                'success'        => false,
                'message'        => 'Немає видимих товарів для генерації фіду.',
                'products_count' => 0,
                'file_path'      => '',
            ];
        }

        $categories = $this->fetchCategoriesMap();

        $xml = $this->buildXml($products, $categories);

        $dir = dirname($this->feedPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($this->feedPath, $xml, LOCK_EX) === false) {
            $this->log('FEED_ERROR', 'Не вдалося записати файл: ' . $this->feedPath);
            return [
                'success'        => false,
                'message'        => 'Помилка запису файлу фіду.',
                'products_count' => 0,
                'file_path'      => '',
            ];
        }

        // Зберігаємо час останньої генерації
        DB::query(
            "INSERT INTO settings (`key`, `value`, `group`, `type`, updated_at)
             VALUES ('prom_last_sync', ?, 'prom', 'text', NOW())
             ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
            [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );

        $count = count($products);
        $this->log('FEED_OK', "XML-фід згенеровано: {$count} товарів → " . $this->feedPath);

        return [
            'success'        => true,
            'message'        => "Фід згенеровано: {$count} товарів.",
            'products_count' => $count,
            'file_path'      => $this->feedPath,
            'feed_url'       => $this->siteUrl . '/prom/feed.xml',
        ];
    }

    /**
     * Побудувати XML-рядок у форматі YML для Prom.
     */
    private function buildXml(array $products, array $categories): string
    {
        $date = date('Y-m-d H:i');
        $name = $this->xmlEscape($this->storeName);
        $url  = $this->xmlEscape($this->siteUrl);

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">';
        $lines[] = "<yml_catalog date=\"{$date}\">";
        $lines[] = "  <shop>";
        $lines[] = "    <name>{$name}</name>";
        $lines[] = "    <company>{$name}</company>";
        $lines[] = "    <url>{$url}</url>";
        $lines[] = "    <currencies>";
        $lines[] = "      <currency id=\"UAH\" rate=\"1\"/>";
        $lines[] = "    </currencies>";

        // Категорії
        $lines[] = "    <categories>";
        foreach ($categories as $cat) {
            $catName = $this->xmlEscape($cat['name']);
            $parent  = $cat['parent_id']
                ? " parentId=\"{$cat['parent_id']}\""
                : '';
            $lines[] = "      <category id=\"{$cat['id']}\"{$parent}>{$catName}</category>";
        }
        $lines[] = "    </categories>";

        // Товари
        $lines[] = "    <offers>";
        foreach ($products as $p) {
            $lines[] = $this->buildOfferXml($p);
        }
        $lines[] = "    </offers>";
        $lines[] = "  </shop>";
        $lines[] = "</yml_catalog>";

        return implode("\n", $lines);
    }

    /**
     * Побудувати XML-блок одного товару (<offer>).
     */
    private function buildOfferXml(array $p): string
    {
        $id          = (int)$p['id'];
        $available   = (int)($p['stock_quantity'] ?? 0) > 0 ? 'true' : 'false';
        $name        = $this->xmlEscape((string)$p['name']);
        $price       = number_format((float)$p['price'], 2, '.', '');
        $categoryId  = (int)($p['category_id'] ?? 0);
        $description = $this->xmlEscape(strip_tags((string)($p['description'] ?? '')));
        $url         = $this->xmlEscape($this->siteUrl . '/product/' . $p['slug']);
        $sku         = $this->xmlEscape((string)($p['sku'] ?? ''));

        // URL зображення
        $imageTag = '';
        if (!empty($p['image'])) {
            $imgUrl  = $this->xmlEscape($this->siteUrl . $p['image']);
            $imageTag = "        <picture>{$imgUrl}</picture>\n";
        }

        $categoryTag = $categoryId > 0
            ? "        <categoryId>{$categoryId}</categoryId>\n"
            : '';

        $vendorCodeTag = $sku !== ''
            ? "        <vendorCode>{$sku}</vendorCode>\n"
            : '';

        // external_id — наш product.id, щоб Prom повернув його у вебхуку
        return <<<XML
      <offer id="{$id}" available="{$available}" external_id="{$id}">
        <url>{$url}</url>
        <price>{$price}</price>
        <currencyId>UAH</currencyId>
{$categoryTag}        <name>{$name}</name>
{$imageTag}{$vendorCodeTag}        <description>{$description}</description>
      </offer>
XML;
    }

    // =========================================================================
    // Підхід Б: черга API-оновлень
    // =========================================================================

    /**
     * Поставити товари в чергу на оновлення через API.
     *
     * @param array  $productIds  Масив наших product.id (порожній = всі)
     * @param string $action      'price' | 'quantity' | 'both'
     */
    public function enqueueProducts(array $productIds = [], string $action = 'both'): int
    {
        if (!PromApiClient::isEnabled()) {
            return 0;
        }

        if (empty($productIds)) {
            // Ставимо всі видимі товари з prom_product_id
            $rows = DB::query(
                'SELECT id FROM products WHERE is_visible = 1 AND prom_product_id IS NOT NULL'
            )->fetchAll(\PDO::FETCH_COLUMN);
            $productIds = $rows;
        }

        if (empty($productIds)) {
            return 0;
        }

        $count = 0;
        foreach ($productIds as $pid) {
            // Не дублюємо — якщо вже є pending запис для цього товару
            $exists = DB::query(
                "SELECT id FROM prom_sync_queue
                 WHERE product_id = ? AND status = 'pending' LIMIT 1",
                [(int)$pid]
            )->fetch();

            if (!$exists) {
                DB::query(
                    "INSERT INTO prom_sync_queue (product_id, action, status, created_at)
                     VALUES (?, ?, 'pending', NOW())",
                    [(int)$pid, $action]
                );
                $count++;
            }
        }

        $this->log('QUEUE_ADD', "Додано в чергу: {$count} товарів, дія: {$action}");
        return $count;
    }

    /**
     * Обробити чергу: взяти QUEUE_BATCH pending записів і надіслати на Prom.
     * Викликається cron-скриптом або вручну з адмінки.
     *
     * Повертає статистику: ['processed', 'success', 'failed', 'remaining']
     */
    public function processQueue(): array
    {
        if (!PromApiClient::isEnabled()) {
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'remaining' => 0];
        }

        $rows = DB::query(
            "SELECT q.id, q.product_id, q.action, q.attempts,
                    p.prom_product_id, p.price,
                    COALESCE(ps.quantity, 0) AS stock_quantity
             FROM prom_sync_queue q
             JOIN products p ON p.id = q.product_id
             LEFT JOIN product_stocks ps
                ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
               AND ps.option_id IS NULL
             WHERE q.status = 'pending'
               AND q.attempts < ?
             ORDER BY q.created_at ASC
             LIMIT ?",
            [self::MAX_ATTEMPTS, self::QUEUE_BATCH]
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'remaining' => 0];
        }

        $processed = 0;
        $success   = 0;
        $failed    = 0;

        foreach ($rows as $row) {
            $queueId       = (int)$row['id'];
            $promProductId = (int)($row['prom_product_id'] ?? 0);

            // Позначаємо як processing
            DB::query(
                "UPDATE prom_sync_queue SET status = 'processing', attempts = attempts + 1
                 WHERE id = ?",
                [$queueId]
            );

            if ($promProductId === 0) {
                DB::query(
                    "UPDATE prom_sync_queue
                     SET status = 'failed', last_error = 'prom_product_id не встановлено', processed_at = NOW()
                     WHERE id = ?",
                    [$queueId]
                );
                $failed++;
                $processed++;
                continue;
            }

            // Визначаємо що оновлюємо
            $price    = $row['action'] !== 'quantity' ? (float)$row['price']         : null;
            $quantity = $row['action'] !== 'price'    ? (int)$row['stock_quantity'] : null;

            $result = $this->api->updateProduct($promProductId, $price, $quantity);

            if (isset($result['error']) || isset($result['errors'])) {
                $error = $result['error'] ?? json_encode($result['errors']);
                $attempts = (int)$row['attempts'] + 1;

                $newStatus = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending';

                DB::query(
                    "UPDATE prom_sync_queue
                     SET status = ?, last_error = ?, processed_at = NOW()
                     WHERE id = ?",
                    [$newStatus, (string)$error, $queueId]
                );

                $this->log(
                    'QUEUE_FAIL',
                    "product_id={$row['product_id']}, prom_id={$promProductId}: {$error}"
                );
                $failed++;
            } else {
                DB::query(
                    "UPDATE prom_sync_queue
                     SET status = 'done', last_error = NULL, processed_at = NOW()
                     WHERE id = ?",
                    [$queueId]
                );
                $success++;
            }

            $processed++;
        }

        // Рахуємо скільки ще залишилось
        $remaining = (int) DB::query(
            "SELECT COUNT(*) FROM prom_sync_queue WHERE status = 'pending'"
        )->fetchColumn();

        // Оновлюємо час останньої синхронізації
        if ($success > 0) {
            DB::query(
                "INSERT INTO settings (`key`, `value`, `group`, `type`, updated_at)
                 VALUES ('prom_last_sync', ?, 'prom', 'text', NOW())
                 ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
                [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
            );
        }

        $this->log(
            'QUEUE_DONE',
            "Оброблено: {$processed}, успіх: {$success}, помилок: {$failed}, залишок: {$remaining}"
        );

        return [
            'processed' => $processed,
            'success'   => $success,
            'failed'    => $failed,
            'remaining' => $remaining,
        ];
    }

    /**
     * Отримати статистику черги для відображення в адмінці.
     */
    public function getQueueStats(): array
    {
        $rows = DB::query(
            "SELECT status, COUNT(*) as cnt
             FROM prom_sync_queue
             GROUP BY status"
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        return [
            'pending'    => (int)($rows['pending']    ?? 0),
            'processing' => (int)($rows['processing'] ?? 0),
            'done'       => (int)($rows['done']       ?? 0),
            'failed'     => (int)($rows['failed']     ?? 0),
        ];
    }

    /**
     * Очистити виконані та провалені записи черги.
     */
    public function clearQueue(string $status = 'done'): int
    {
        $allowed = ['done', 'failed', 'all'];
        if (!in_array($status, $allowed, true)) {
            return 0;
        }

        if ($status === 'all') {
            $count = (int) DB::query("SELECT COUNT(*) FROM prom_sync_queue")->fetchColumn();
            DB::query("DELETE FROM prom_sync_queue");
        } else {
            $count = (int) DB::query(
                "SELECT COUNT(*) FROM prom_sync_queue WHERE status = ?", [$status]
            )->fetchColumn();
            DB::query("DELETE FROM prom_sync_queue WHERE status = ?", [$status]);
        }

        return $count;
    }

    // =========================================================================
    // Допоміжні методи
    // =========================================================================

    private function fetchProductsForFeed(): array
    {
        return DB::query(
            "SELECT p.id, p.name, p.slug, p.description, p.price,
                    p.sku, p.image, p.category_id,
                    COALESCE(ps.quantity, 0) AS stock_quantity
             FROM products p
             LEFT JOIN product_stocks ps
                ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
               AND ps.option_id IS NULL
             WHERE p.is_visible = 1
             ORDER BY p.id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function fetchCategoriesMap(): array
    {
        return DB::query(
            "SELECT id, name, parent_id FROM categories WHERE is_active = 1 ORDER BY id ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function xmlEscape(string $str): string
    {
        return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function log(string $level, string $message): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $message);
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
