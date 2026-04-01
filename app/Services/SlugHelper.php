<?php

namespace App\Services;

use App\Core\Database\DB as Database;

class SlugHelper
{
    /**
     * Генерувати slug з тексту
     * 
     * @param string $text
     * @return string
     */
    public static function generate($text)
    {
        // Перетворити на нижній регістр
        $slug = mb_strtolower($text, 'UTF-8');
        
        // Замінити кириличні символи на латиницю
        $slug = self::transliterate($slug);
        
        // Замінити пробіли на дефіси
        $slug = preg_replace('/\s+/', '-', $slug);
        
        // Видалити всі символи крім букв, цифр та дефісів
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        // Замінити кілька дефісів на один
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Видалити дефіси на початку та в кінці
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Транслітерація кириличних символів
     * 
     * @param string $text
     * @return string
     */
    private static function transliterate($text)
    {
        $cyrillic = [
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
        ];
        
        $latin = [
            'a', 'b', 'v', 'g', 'd', 'e', 'yo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya',
            'a', 'b', 'v', 'g', 'd', 'e', 'yo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya'
        ];
        
        return str_replace($cyrillic, $latin, $text);
    }

    /**
     * Перевірити унікальність slug
     * 
     * @param string $slug
     * @param string $entityType (product, category, page)
     * @param int|null $excludeId (для виключення поточного запису)
     * @return bool
     */
    public static function isUnique($slug, $entityType, $excludeId = null)
    {
        $db = Database::getInstance();
        
        // Визначити таблицю на основі типу сутності
        $table = self::getTableByEntityType($entityType);
        
        if (!$table) {
            return false;
        }
        
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);
        
        return !empty($result) && $result[0]['count'] == 0;
    }

    /**
     * Отримати унікальний slug
     * 
     * @param string $text
     * @param string $entityType
     * @param int|null $excludeId
     * @return string
     */
    public static function getUnique($text, $entityType, $excludeId = null)
    {
        $slug = self::generate($text);
        $originalSlug = $slug;
        $counter = 1;
        
        // Якщо slug не унікальний, додавати числа до кінця
        while (!self::isUnique($slug, $entityType, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Отримати таблицю на основі типу сутності
     * 
     * @param string $entityType
     * @return string|null
     */
    private static function getTableByEntityType($entityType)
    {
        $tables = [
            'product' => 'products',
            'category' => 'categories',
            'page' => 'pages'
        ];
        
        return $tables[$entityType] ?? null;
    }

    /**
     * Отримати сутність за slug
     * 
     * @param string $slug
     * @param string $entityType
     * @return array|null
     */
    public static function getBySlug($slug, $entityType)
    {
        $db = Database::getInstance();
        $table = self::getTableByEntityType($entityType);
        
        if (!$table) {
            return null;
        }
        
        $result = $db->query("SELECT * FROM {$table} WHERE slug = ?", [$slug])->fetchAll(\PDO::FETCH_ASSOC);
        
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Отримати ID сутності за slug
     * 
     * @param string $slug
     * @param string $entityType
     * @return int|null
     */
    public static function getIdBySlug($slug, $entityType)
    {
        $entity = self::getBySlug($slug, $entityType);
        return $entity ? $entity['id'] : null;
    }

    /**
     * Зберегти історію змін slug
     * 
     * @param string $entityType
     * @param int $entityId
     * @param string $oldSlug
     * @param string $newSlug
     * @param int|null $changedBy
     * @param string|null $reason
     * @return bool
     */
    public static function saveHistory($entityType, $entityId, $oldSlug, $newSlug, $changedBy = null, $reason = null)
    {
        $db = Database::getInstance();
        
        $query = "INSERT INTO slug_history (entity_type, entity_id, old_slug, new_slug, changed_by, reason) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        return $db->execute($query, [
            $entityType,
            $entityId,
            $oldSlug,
            $newSlug,
            $changedBy,
            $reason
        ]);
    }

    /**
     * Створити редирект 301
     * 
     * @param string $oldSlug
     * @param string $newSlug
     * @param string $entityType
     * @param int|null $entityId
     * @return bool
     */
    public static function createRedirect($oldSlug, $newSlug, $entityType, $entityId = null)
    {
        $db = Database::getInstance();
        
        $query = "INSERT INTO url_redirects (old_slug, new_slug, entity_type, entity_id) 
                  VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE new_slug = ?, is_active = 1";
        
        return $db->execute($query, [
            $oldSlug,
            $newSlug,
            $entityType,
            $entityId,
            $newSlug
        ]);
    }

    /**
     * Отримати редирект
     * 
     * @param string $oldSlug
     * @param string $entityType
     * @return array|null
     */
    public static function getRedirect($oldSlug, $entityType)
    {
        $db = Database::getInstance();
        
        $result = $db->query(
            "SELECT * FROM url_redirects WHERE old_slug = ? AND entity_type = ? AND is_active = 1",
            [$oldSlug, $entityType]
        )->fetchAll(\PDO::FETCH_ASSOC);
        
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Отримати історію змін slug
     * 
     * @param string $entityType
     * @param int $entityId
     * @return array
     */
    public static function getHistory($entityType, $entityId)
    {
        $db = Database::getInstance();
        
        return $db->query(
            "SELECT * FROM slug_history WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC",
            [$entityType, $entityId]
        )->fetchAll(\PDO::FETCH_ASSOC) ?? [];
    }

    /**
     * Валідувати slug
     * 
     * @param string $slug
     * @return bool
     */
    public static function validate($slug)
    {
        // Slug повинен містити тільки букви, цифри та дефіси
        // Не повинен починатися або закінчуватися дефісом
        // Мінімальна довжина 1, максимальна 255
        
        if (empty($slug) || strlen($slug) > 255) {
            return false;
        }
        
        if (preg_match('/^-|-$/', $slug)) {
            return false;
        }
        
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return false;
        }
        
        return true;
    }

    /**
     * Отримати SEO-налаштування сутності
     * 
     * @param string $entityType
     * @param int $entityId
     * @return array|null
     */
    public static function getSeoSettings($entityType, $entityId)
    {
        $db = Database::getInstance();
        
        $result = $db->query(
            "SELECT * FROM seo_settings WHERE entity_type = ? AND entity_id = ?",
            [$entityType, $entityId]
        )->fetchAll(\PDO::FETCH_ASSOC);
        
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Зберегти SEO-налаштування сутності
     * 
     * @param string $entityType
     * @param int $entityId
     * @param array $data
     * @return bool
     */
    public static function saveSeoSettings($entityType, $entityId, $data)
    {
        $db = Database::getInstance();
        
        // Перевірити, чи існують налаштування
        $existing = self::getSeoSettings($entityType, $entityId);
        
        if ($existing) {
            // Оновити
            $query = "UPDATE seo_settings SET title = ?, description = ?, keywords = ?, 
                      og_title = ?, og_description = ?, og_image = ?, canonical_url = ?, robots_meta = ?
                      WHERE entity_type = ? AND entity_id = ?";
            
            return $db->execute($query, [
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['keywords'] ?? null,
                $data['og_title'] ?? null,
                $data['og_description'] ?? null,
                $data['og_image'] ?? null,
                $data['canonical_url'] ?? null,
                $data['robots_meta'] ?? null,
                $entityType,
                $entityId
            ]);
        } else {
            // Вставити
            $query = "INSERT INTO seo_settings (entity_type, entity_id, title, description, keywords, 
                      og_title, og_description, og_image, canonical_url, robots_meta)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            return $db->execute($query, [
                $entityType,
                $entityId,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['keywords'] ?? null,
                $data['og_title'] ?? null,
                $data['og_description'] ?? null,
                $data['og_image'] ?? null,
                $data['canonical_url'] ?? null,
                $data['robots_meta'] ?? null
            ]);
        }
    }
}
