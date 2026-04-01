<?php

namespace App\Services;

use App\Core\Database\DB as Database;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;

class ProductFilterService
{
    /**
     * Отримати відфільтровані товари
     * 
     * @param array $filters
     * @return array
     */
    public static function filter($filters = [])
    {
        $db = Database::getInstance();
        
        $query = "SELECT DISTINCT p.* FROM products p";
        $params = [];
        $joins = [];
        $conditions = [];

        // Фільтр за категорією
        if (!empty($filters['category_id'])) {
            // Отримати категорію та всі дочірні категорії
            $category = Category::findById($filters['category_id']);
            
            if ($category) {
                $allCategories = [$filters['category_id']];
                $children = Category::getAllChildren($filters['category_id']);
                
                foreach ($children as $child) {
                    $allCategories[] = $child['id'];
                }
                
                $placeholders = implode(',', array_fill(0, count($allCategories), '?'));
                $conditions[] = "p.category_id IN ($placeholders)";
                $params = array_merge($params, $allCategories);
            }
        }

        // Фільтр за ціною
        if (!empty($filters['min_price'])) {
            $conditions[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $conditions[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }

        // Фільтр за атрибутами
        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            $attributeIndex = 0;
            
            foreach ($filters['attributes'] as $attributeId => $values) {
                if (empty($values)) {
                    continue;
                }
                
                $attributeIndex++;
                $tableAlias = "pa{$attributeIndex}";
                
                // Додати JOIN для кожного атрибута
                $joins[] = "INNER JOIN product_attributes {$tableAlias} ON p.id = {$tableAlias}.product_id 
                           AND {$tableAlias}.attribute_id = ?";
                $params[] = $attributeId;
                
                // Додати умову для значень атрибута
                if (is_array($values)) {
                    $valuePlaceholders = implode(',', array_fill(0, count($values), '?'));
                    $conditions[] = "{$tableAlias}.value IN ($valuePlaceholders)";
                    $params = array_merge($params, $values);
                } else {
                    $conditions[] = "{$tableAlias}.value = ?";
                    $params[] = $values;
                }
            }
        }

        // Побудувати запит
        if (!empty($joins)) {
            $query .= " " . implode(" ", $joins);
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Сортування
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'ASC';
        
        $allowedSortBy = ['name', 'price', 'created_at', 'popularity'];
        $allowedSortOrder = ['ASC', 'DESC'];
        
        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query .= " ORDER BY p.{$sortBy} {$sortOrder}";
        } else {
            $query .= " ORDER BY p.name ASC";
        }

        // Пагінація
        if (!empty($filters['limit'])) {
            $offset = $filters['offset'] ?? 0;
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $filters['limit'];
            $params[] = $offset;
        }

        $result = $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);
        
        return $result ?? [];
    }

    /**
     * Отримати кількість товарів за фільтрами
     * 
     * @param array $filters
     * @return int
     */
    public static function count($filters = [])
    {
        $db = Database::getInstance();
        
        $query = "SELECT COUNT(DISTINCT p.id) as count FROM products p";
        $params = [];
        $joins = [];
        $conditions = [];

        // Фільтр за категорією
        if (!empty($filters['category_id'])) {
            $category = Category::findById($filters['category_id']);
            
            if ($category) {
                $allCategories = [$filters['category_id']];
                $children = Category::getAllChildren($filters['category_id']);
                
                foreach ($children as $child) {
                    $allCategories[] = $child['id'];
                }
                
                $placeholders = implode(',', array_fill(0, count($allCategories), '?'));
                $conditions[] = "p.category_id IN ($placeholders)";
                $params = array_merge($params, $allCategories);
            }
        }

        // Фільтр за ціною
        if (!empty($filters['min_price'])) {
            $conditions[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $conditions[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }

        // Фільтр за атрибутами
        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            $attributeIndex = 0;
            
            foreach ($filters['attributes'] as $attributeId => $values) {
                if (empty($values)) {
                    continue;
                }
                
                $attributeIndex++;
                $tableAlias = "pa{$attributeIndex}";
                
                $joins[] = "INNER JOIN product_attributes {$tableAlias} ON p.id = {$tableAlias}.product_id 
                           AND {$tableAlias}.attribute_id = ?";
                $params[] = $attributeId;
                
                if (is_array($values)) {
                    $valuePlaceholders = implode(',', array_fill(0, count($values), '?'));
                    $conditions[] = "{$tableAlias}.value IN ($valuePlaceholders)";
                    $params = array_merge($params, $values);
                } else {
                    $conditions[] = "{$tableAlias}.value = ?";
                    $params[] = $values;
                }
            }
        }

        if (!empty($joins)) {
            $query .= " " . implode(" ", $joins);
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $result = $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);
        
        return !empty($result) ? $result[0]['count'] : 0;
    }

    /**
     * Отримати доступні опції фільтра для категорії
     * 
     * @param int $categoryId
     * @param array $currentFilters
     * @return array
     */
    public static function getFilterOptions($categoryId, $currentFilters = [])
    {
        $db = Database::getInstance();
        $category = Category::findById($categoryId);
        
        if (!$category) {
            return [];
        }

        $attributes = Category::getAttributes($categoryId);
        $filterOptions = [];

        foreach ($attributes as $attribute) {
            if (!$attribute['is_filterable']) {
                continue;
            }

            $filterOptions[$attribute['id']] = [
                'id' => $attribute['id'],
                'name' => $attribute['name'],
                'slug' => $attribute['slug'],
                'type' => $attribute['type'],
                'options' => []
            ];

            // Отримати доступні значення атрибута для товарів у категорії
            $values = ProductAttribute::getUniqueValuesForCategory($categoryId, $attribute['id']);
            
            foreach ($values as $value) {
                $count = ProductAttribute::getCountInCategory($categoryId, $attribute['id'], $value['value']);
                
                $filterOptions[$attribute['id']]['options'][] = [
                    'value' => $value['value'],
                    'label' => $value['option_name'] ?? $value['value'],
                    'color' => $value['color_code'] ?? null,
                    'count' => $count
                ];
            }
        }

        return $filterOptions;
    }

    /**
     * Отримати діапазон цін для категорії
     * 
     * @param int $categoryId
     * @return array
     */
    public static function getPriceRange($categoryId)
    {
        $db = Database::getInstance();
        
        // Отримати категорію та всі дочірні категорії
        $allCategories = [$categoryId];
        $children = Category::getAllChildren($categoryId);
        
        foreach ($children as $child) {
            $allCategories[] = $child['id'];
        }
        
        $placeholders = implode(',', array_fill(0, count($allCategories), '?'));
        
        $result = $db->query(
            "SELECT MIN(price) as min_price, MAX(price) as max_price 
             FROM products 
             WHERE category_id IN ($placeholders)",
            $allCategories
        )->fetchAll(\PDO::FETCH_ASSOC);
        
        if (!empty($result)) {
            return [
                'min' => (float) $result[0]['min_price'],
                'max' => (float) $result[0]['max_price']
            ];
        }
        
        return ['min' => 0, 'max' => 0];
    }

    /**
     * Отримати популярні атрибути для категорії
     * 
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public static function getPopularAttributes($categoryId, $limit = 5)
    {
        $attributes = Category::getAttributes($categoryId);
        $popular = [];

        foreach ($attributes as $attribute) {
            if (!$attribute['is_filterable']) {
                continue;
            }

            $values = ProductAttribute::getUniqueValuesForCategory($categoryId, $attribute['id']);
            
            if (!empty($values)) {
                $popular[] = [
                    'id' => $attribute['id'],
                    'name' => $attribute['name'],
                    'slug' => $attribute['slug'],
                    'type' => $attribute['type'],
                    'value_count' => count($values)
                ];
            }
        }

        usort($popular, function($a, $b) {
            return $b['value_count'] - $a['value_count'];
        });

        return array_slice($popular, 0, $limit);
    }

    /**
     * Отримати URL для фільтра
     * 
     * @param int $categoryId
     * @param array $filters
     * @return string
     */
    public static function getFilterUrl($categoryId, $filters = [])
    {
        $url = "/category/" . Category::findById($categoryId)['slug'];
        
        if (empty($filters)) {
            return $url;
        }

        $queryParams = [];

        if (!empty($filters['min_price'])) {
            $queryParams['min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $queryParams['max_price'] = $filters['max_price'];
        }

        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeId => $values) {
                if (!empty($values)) {
                    if (is_array($values)) {
                        $queryParams["attr_{$attributeId}"] = implode(',', $values);
                    } else {
                        $queryParams["attr_{$attributeId}"] = $values;
                    }
                }
            }
        }

        if (!empty($queryParams)) {
            $url .= "?" . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Розпарсити фільтри з URL параметрів
     * 
     * @param array $queryParams
     * @return array
     */
    public static function parseFiltersFromUrl($queryParams = [])
    {
        $filters = [];

        if (!empty($queryParams['min_price'])) {
            $filters['min_price'] = (float) $queryParams['min_price'];
        }

        if (!empty($queryParams['max_price'])) {
            $filters['max_price'] = (float) $queryParams['max_price'];
        }

        // Розпарсити атрибути
        $filters['attributes'] = [];
        
        foreach ($queryParams as $key => $value) {
            if (strpos($key, 'attr_') === 0) {
                $attributeId = (int) substr($key, 5);
                $values = explode(',', $value);
                $filters['attributes'][$attributeId] = $values;
            }
        }

        return $filters;
    }
}
