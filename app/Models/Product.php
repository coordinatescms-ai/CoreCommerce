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
     * Отримати всі товари
     * 
     * @return array
     */
    public static function all()
    {
        return self::query("SELECT * FROM " . static::$table) ?? [];
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
