<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SlugHelper;

class Product extends Model
{
    protected static $table = 'products';

    /**
     * Отримати товар за slug
     * 
     * @param string $slug
     * @return array|null
     */
    public static function findBySlug($slug)
    {
        return SlugHelper::getBySlug($slug, 'product');
    }

    /**
     * Отримати видимий товар за slug.
     *
     * @param string $slug
     * @return array|null
     */
    public static function findVisibleBySlug($slug)
    {
        $result = self::query(
            "SELECT * FROM " . static::$table . " WHERE slug = ? AND is_visible = 1 LIMIT 1",
            [$slug]
        );

        return !empty($result) ? $result[0] : null;
    }

    /**
     * Отримати товар за ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function findById($id)
    {
        $result = self::query("SELECT * FROM " . static::$table . " WHERE id = ?", [$id]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Отримати видимий товар за ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function findVisibleById($id)
    {
        $result = self::query(
            "SELECT * FROM " . static::$table . " WHERE id = ? AND is_visible = 1",
            [(int) $id]
        );

        return !empty($result) ? $result[0] : null;
    }

    /**
     * Отримати всі товари
     * 
     * @return array
     */
    public static function all()
    {
        return self::query("SELECT * FROM " . static::$table) ?? [];
    }


    /**
     * Отримати всі товари разом з назвою категорії
     *
     * @return array
     */
    public static function allWithCategory()
    {
        return self::query(
            "SELECT p.*, c.name as category_name
             FROM " . static::$table . " p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC"
        ) ?? [];
    }

    /**
     * Отримати товар за ID разом з назвою категорії
     *
     * @param int $id
     * @return array|null
     */
    public static function findWithCategoryById($id)
    {
        $result = self::query(
            "SELECT p.*, c.name as category_name
             FROM " . static::$table . " p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?",
            [$id]
        );

        return !empty($result) ? $result[0] : null;
    }
    /**
     * Отримати товари за категорією
     * 
     * @param int $categoryId
     * @return array
     */
    public static function findByCategory($categoryId)
    {
        return self::query("SELECT * FROM " . static::$table . " WHERE category_id = ?", [$categoryId]) ?? [];
    }

    /**
     * Отримати схожі товари для сторінки товару.
     *
     * @param int $productId
     * @param int|null $categoryId
     * @param int $limit
     * @return array
     */
    public static function getSimilar(int $productId, ?int $categoryId, int $limit = 4): array
    {
        $limit = max(1, min($limit, 12));

        if ($categoryId) {
            $items = self::query(
                "SELECT * FROM " . static::$table . "
                 WHERE category_id = ? AND id != ? AND is_visible = 1
                 ORDER BY updated_at DESC, id DESC
                 LIMIT " . $limit,
                [$categoryId, $productId]
            ) ?? [];

            if (!empty($items)) {
                return $items;
            }
        }

        return self::query(
            "SELECT * FROM " . static::$table . "
             WHERE id != ? AND is_visible = 1
             ORDER BY updated_at DESC, id DESC
             LIMIT " . $limit,
            [$productId]
        ) ?? [];
    }

    /**
     * Створити новий товар
     * 
     * @param array $data
     * @return int|false
     */
    public static function create($data)
    {
        // Генерувати унікальний slug, якщо не надано
        if (empty($data['slug'])) {
            $data['slug'] = SlugHelper::getUnique($data['name'], 'product');
        } else {
            // Перевірити унікальність наданого slug
            if (!SlugHelper::isUnique($data['slug'], 'product')) {
                return false;
            }
        }

        // Валідувати slug
        if (!SlugHelper::validate($data['slug'])) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $query = "INSERT INTO " . self::$table . " (" . implode(',', $columns) . ") 
                  VALUES (" . implode(',', $placeholders) . ")";
        
        $result = self::execute($query, array_values($data));
        
        if ($result) {
            // Отримати ID вставленого запису
            $lastId = self::getLastInsertId();
            
            // Зберегти SEO-налаштування, якщо надано
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                SlugHelper::saveSeoSettings('product', $lastId, [
                    'title' => $data['meta_title'] ?? null,
                    'description' => $data['meta_description'] ?? null,
                    'keywords' => $data['meta_keywords'] ?? null,
                ]);
            }
            
            return $lastId;
        }
        
        return false;
    }

    /**
     * Оновити товар
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $oldProduct = self::findById($id);
        
        if (!$oldProduct) {
            return false;
        }

        // Якщо змінюється slug
        if (!empty($data['slug']) && $data['slug'] !== $oldProduct['slug']) {
            // Перевірити унікальність нового slug
            if (!SlugHelper::isUnique($data['slug'], 'product', $id)) {
                return false;
            }

            // Валідувати новий slug
            if (!SlugHelper::validate($data['slug'])) {
                return false;
            }

            // Зберегти історію
            SlugHelper::saveHistory('product', $id, $oldProduct['slug'], $data['slug'], 
                $_SESSION['user']['id'] ?? null, 'Manual edit');

            // Створити редирект 301
            SlugHelper::createRedirect($oldProduct['slug'], $data['slug'], 'product', $id);
        }

        $updates = [];
        $values = [];

        foreach ($data as $column => $value) {
            $updates[] = "$column = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $query = "UPDATE " . self::$table . " SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $result = self::execute($query, $values);

        if ($result) {
            // Оновити SEO-налаштування, якщо надано
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                SlugHelper::saveSeoSettings('product', $id, [
                    'title' => $data['meta_title'] ?? null,
                    'description' => $data['meta_description'] ?? null,
                    'keywords' => $data['meta_keywords'] ?? null,
                ]);
            }
        }

        return $result;
    }

    /**
     * Видалити товар
     * 
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        self::execute("DELETE FROM product_attribute_values WHERE product_id = ?", [(int) $id]);
        return self::execute("DELETE FROM " . self::$table . " WHERE id = ?", [$id]);
    }

    /**
     * Отримати SEO-налаштування товара
     * 
     * @param int $id
     * @return array|null
     */
    public static function getSeoSettings($id)
    {
        return SlugHelper::getSeoSettings('product', $id);
    }

    /**
     * Отримати історію змін slug
     * 
     * @param int $id
     * @return array
     */
    public static function getSlugHistory($id)
    {
        return SlugHelper::getHistory('product', $id);
    }

    /**
     * Отримати останній ID вставленого запису
     * 
     * @return int
     */
    private static function getLastInsertId()
    {
        $result = self::query("SELECT LAST_INSERT_ID() as id");
        return !empty($result) ? $result[0]['id'] : 0;
    }
}
