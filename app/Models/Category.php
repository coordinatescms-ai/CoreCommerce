<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SlugHelper;

class Category extends Model
{
    protected static $table = 'categories';

    /**
     * Отримати категорію за slug
     * 
     * @param string $slug
     * @return array|null
     */
    public static function findBySlug($slug)
    {
        return SlugHelper::getBySlug($slug, 'category');
    }

    /**
     * Отримати категорію за ID
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
     * Отримати всі категорії
     * 
     * @return array
     */
    public static function all()
    {
        return self::query("SELECT * FROM " . static::$table . " ORDER BY name") ?? [];
    }

    /**
     * Отримати категорії за батьківською категорією
     * 
     * @param int|null $parentId
     * @return array
     */
    public static function findByParent($parentId = null)
    {
        if ($parentId === null) {
            return self::query("SELECT * FROM " . static::$table . " WHERE parent_id IS NULL ORDER BY name") ?? [];
        }
        
        return self::query("SELECT * FROM " . static::$table . " WHERE parent_id = ? ORDER BY name", [$parentId]) ?? [];
    }

    /**
     * Отримати дерево категорій (рекурсивно)
     * 
     * @param int|null $parentId
     * @param int $depth
     * @param int $maxDepth
     * @return array
     */
    public static function getTree($parentId = null, $depth = 0, $maxDepth = 10)
    {
        if ($depth > $maxDepth) {
            return [];
        }

        $categories = self::findByParent($parentId);
        
        foreach ($categories as &$category) {
            $category['children'] = self::getTree($category['id'], $depth + 1, $maxDepth);
            $category['depth'] = $depth;
            $category['has_children'] = !empty($category['children']);
        }
        
        return $categories;
    }

    /**
     * Отримати плоский список категорій з інформацією про рівень
     * 
     * @param int|null $parentId
     * @param int $level
     * @return array
     */
    public static function getFlatTree($parentId = null, $level = 0)
    {
        $categories = self::findByParent($parentId);
        $result = [];
        
        foreach ($categories as $category) {
            $category['level'] = $level;
            $result[] = $category;
            
            // Рекурсивно отримати дочірні категорії
            $children = self::getFlatTree($category['id'], $level + 1);
            $result = array_merge($result, $children);
        }
        
        return $result;
    }

    /**
     * Отримати хлібні крихти (breadcrumbs) для категорії
     * 
     * @param int $categoryId
     * @return array
     */
    public static function getBreadcrumbs($categoryId)
    {
        $breadcrumbs = [];
        $currentId = $categoryId;
        
        while ($currentId !== null) {
            $category = self::findById($currentId);
            
            if (!$category) {
                break;
            }
            
            array_unshift($breadcrumbs, [
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'url' => '/category/' . $category['slug']
            ]);
            
            $currentId = $category['parent_id'];
        }
        
        return $breadcrumbs;
    }

    /**
     * Отримати батьківську категорію
     * 
     * @param int $categoryId
     * @return array|null
     */
    public static function getParent($categoryId)
    {
        $category = self::findById($categoryId);
        
        if (!$category || !$category['parent_id']) {
            return null;
        }
        
        return self::findById($category['parent_id']);
    }

    /**
     * Отримати дочірні категорії
     * 
     * @param int $categoryId
     * @return array
     */
    public static function getChildren($categoryId)
    {
        return self::findByParent($categoryId);
    }

    /**
     * Отримати всі дочірні категорії (рекурсивно)
     * 
     * @param int $categoryId
     * @return array
     */
    public static function getAllChildren($categoryId)
    {
        $children = [];
        $directChildren = self::getChildren($categoryId);
        
        foreach ($directChildren as $child) {
            $children[] = $child;
            $children = array_merge($children, self::getAllChildren($child['id']));
        }
        
        return $children;
    }

    /**
     * Отримати кількість товарів у категорії (включаючи дочірні)
     * 
     * @param int $categoryId
     * @return int
     */
    public static function getProductCount($categoryId)
    {
        $allCategoryIds = [$categoryId];
        $children = self::getAllChildren($categoryId);
        
        foreach ($children as $child) {
            $allCategoryIds[] = $child['id'];
        }
        
        $placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
        $result = self::query("SELECT COUNT(*) as count FROM products WHERE category_id IN ($placeholders)", $allCategoryIds);
        
        return $result ? $result[0]['count'] : 0;
    }

    /**
     * Отримати атрибути категорії
     * 
     * @param int $categoryId
     * @return array
     */
    public static function getAttributes($categoryId)
    {
        $result = self::query(
            "SELECT a.* FROM attributes a 
             INNER JOIN category_attributes ca ON a.id = ca.attribute_id 
             WHERE ca.category_id = ? 
             ORDER BY a.name",
            [$categoryId]
        );
        
        return $result ?? [];
    }

    /**
     * Отримати ланцюжок категорій від поточної до кореня.
     *
     * @param int $categoryId
     * @return array
     */
    public static function getLineageIds($categoryId)
    {
        $lineageIds = [];
        $visitedIds = [];
        $currentId = (int) $categoryId;

        while ($currentId > 0 && !isset($visitedIds[$currentId])) {
            $visitedIds[$currentId] = true;
            $lineageIds[] = $currentId;

            $category = self::findById($currentId);
            if (!$category || empty($category['parent_id'])) {
                break;
            }

            $currentId = (int) $category['parent_id'];
        }

        return $lineageIds;
    }

    /**
     * Отримати дозволені атрибути категорії з урахуванням успадкування від батьків.
     *
     * @param int $categoryId
     * @return array
     */
    public static function getAllowedAttributes($categoryId)
    {
        $lineageIds = self::getLineageIds($categoryId);
        if (empty($lineageIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($lineageIds), '?'));
        $result = self::query(
            "SELECT DISTINCT a.id, a.name, a.slug, a.type, a.is_filterable, a.is_visible, a.sort_order
             FROM category_attributes ca
             INNER JOIN attributes a ON a.id = ca.attribute_id
             WHERE ca.category_id IN ($placeholders)
             ORDER BY a.sort_order, a.name",
            $lineageIds
        );

        return $result ?? [];
    }

    /**
     * Отримати тільки ID дозволених атрибутів категорії.
     *
     * @param int $categoryId
     * @return array
     */
    public static function getAllowedAttributeIds($categoryId)
    {
        $attributes = self::getAllowedAttributes($categoryId);
        if (empty($attributes)) {
            return [];
        }

        return array_values(array_map(function ($attribute) {
            return (int) $attribute['id'];
        }, $attributes));
    }

    /**
     * Створити нову категорію
     * 
     * @param array $data
     * @return int|false
     */
    public static function create($data)
    {
        // Генерувати унікальний slug, якщо не надано
        if (empty($data['slug'])) {
            $data['slug'] = SlugHelper::getUnique($data['name'], 'category');
        } else {
            if (!SlugHelper::isUnique($data['slug'], 'category')) {
                return false;
            }
        }

        if (!SlugHelper::validate($data['slug'])) {
            return false;
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $query = "INSERT INTO " . self::$table . " (" . implode(',', $columns) . ") 
                  VALUES (" . implode(',', $placeholders) . ")";
        
        $result = self::execute($query, array_values($data));
        
        if ($result) {
            $lastId = self::getLastInsertId();
            
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                SlugHelper::saveSeoSettings('category', $lastId, [
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
     * Оновити категорію
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $oldCategory = self::findById($id);
        
        if (!$oldCategory) {
            return false;
        }

        if (!empty($data['slug']) && $data['slug'] !== $oldCategory['slug']) {
            if (!SlugHelper::isUnique($data['slug'], 'category', $id)) {
                return false;
            }

            if (!SlugHelper::validate($data['slug'])) {
                return false;
            }

            SlugHelper::saveHistory('category', $id, $oldCategory['slug'], $data['slug'], 
                $_SESSION['user']['id'] ?? null, 'Manual edit');

            SlugHelper::createRedirect($oldCategory['slug'], $data['slug'], 'category', $id);
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
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                SlugHelper::saveSeoSettings('category', $id, [
                    'title' => $data['meta_title'] ?? null,
                    'description' => $data['meta_description'] ?? null,
                    'keywords' => $data['meta_keywords'] ?? null,
                ]);
            }
        }

        return $result;
    }

    /**
     * Видалити категорію
     * 
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        // Перевірити, чи є дочірні категорії
        $children = self::getChildren($id);
        
        if (!empty($children)) {
            // Перемістити дочірні категорії до батьківської
            $category = self::findById($id);
            self::execute(
                "UPDATE " . self::$table . " SET parent_id = ? WHERE parent_id = ?",
                [$category['parent_id'], $id]
            );
        }
        
        return self::execute("DELETE FROM " . self::$table . " WHERE id = ?", [$id]);
    }

    /**
     * Отримати SEO-налаштування категорії
     * 
     * @param int $id
     * @return array|null
     */
    public static function getSeoSettings($id)
    {
        return SlugHelper::getSeoSettings('category', $id);
    }

    /**
     * Отримати історію змін slug
     * 
     * @param int $id
     * @return array
     */
    public static function getSlugHistory($id)
    {
        return SlugHelper::getHistory('category', $id);
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
