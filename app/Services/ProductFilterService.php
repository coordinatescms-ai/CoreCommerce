<?php

namespace App\Services;

use App\Core\Database\DB as Database;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\Attribute;

class ProductFilterService
{
    /**
     * Отримати id категорії та всіх дочірніх.
     *
     * @param int $categoryId
     * @return array
     */
    private static function getCategoryWithChildrenIds($categoryId)
    {
        $allCategories = [(int) $categoryId];
        $children = Category::getAllChildren($categoryId);

        foreach ($children as $child) {
            $allCategories[] = (int) $child['id'];
        }

        return array_values(array_unique($allCategories));
    }

    /**
     * Застосувати фільтри атрибутів до SQL-запиту.
     *
     * @param array $filters
     * @param array $joins
     * @param array $conditions
     * @param array $params
     * @return void
     */
    private static function applyAttributeFilters($filters, &$joins, &$conditions, &$params)
    {
        if (empty($filters['attributes']) || !is_array($filters['attributes'])) {
            return;
        }

        $attributeIndex = 0;

        foreach ($filters['attributes'] as $attributeId => $attributeFilter) {
            if ($attributeFilter === '' || $attributeFilter === null || $attributeFilter === []) {
                continue;
            }

            $attributeIndex++;
            $tableAlias = "pa{$attributeIndex}";
            $joins[] = "INNER JOIN product_attributes {$tableAlias} ON p.id = {$tableAlias}.product_id 
                        AND {$tableAlias}.attribute_id = ?";
            $params[] = (int) $attributeId;

            if (is_array($attributeFilter) && (isset($attributeFilter['min']) || isset($attributeFilter['max']))) {
                if ($attributeFilter['min'] !== '' && $attributeFilter['min'] !== null) {
                    $conditions[] = "CAST({$tableAlias}.value AS DECIMAL(12,2)) >= ?";
                    $params[] = (float) $attributeFilter['min'];
                }

                if ($attributeFilter['max'] !== '' && $attributeFilter['max'] !== null) {
                    $conditions[] = "CAST({$tableAlias}.value AS DECIMAL(12,2)) <= ?";
                    $params[] = (float) $attributeFilter['max'];
                }

                continue;
            }

            $values = is_array($attributeFilter) ? $attributeFilter : [$attributeFilter];
            $values = array_values(array_filter($values, function ($value) {
                return $value !== '' && $value !== null;
            }));

            if (empty($values)) {
                continue;
            }

            $valuePlaceholders = implode(',', array_fill(0, count($values), '?'));
            $conditions[] = "{$tableAlias}.value IN ($valuePlaceholders)";
            $params = array_merge($params, $values);
        }
    }

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
            $category = Category::findById($filters['category_id']);

            if ($category) {
                $allCategories = self::getCategoryWithChildrenIds($filters['category_id']);
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
        self::applyAttributeFilters($filters, $joins, $conditions, $params);

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
                $allCategories = self::getCategoryWithChildrenIds($filters['category_id']);
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
        self::applyAttributeFilters($filters, $joins, $conditions, $params);

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

        $attributes = Category::getAllowedAttributes($categoryId);
        $filterOptions = [];

        $categoryIds = self::getCategoryWithChildrenIds($categoryId);

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
            if ($attribute['type'] === 'range') {
                $range = ProductAttribute::getNumericRangeForCategory($categoryIds, $attribute['id']);
                $filterOptions[$attribute['id']]['range'] = $range;
                continue;
            }

            $values = ProductAttribute::getUniqueValuesForCategory($categoryIds, $attribute['id']);

            foreach ($values as $value) {
                $count = ProductAttribute::getCountInCategory($categoryIds, $attribute['id'], $value['value']);

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
     * Отримати діапазон цін для всього каталогу.
     *
     * @return array{min: float, max: float}
     */
    public static function getGlobalPriceRange()
    {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products"
        )->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($result) && $result[0]['min_price'] !== null && $result[0]['max_price'] !== null) {
            return [
                'min' => (float) $result[0]['min_price'],
                'max' => (float) $result[0]['max_price']
            ];
        }

        return ['min' => 0.0, 'max' => 0.0];
    }

    /**
     * Отримати опції фільтра для всього каталогу на основі існуючих атрибутів у БД.
     *
     * @return array
     */
    public static function getCatalogFilterOptions()
    {
        $attributes = Attribute::all(true);
        $filterOptions = [];

        foreach ($attributes as $attribute) {
            $values = Product::query(
                "SELECT DISTINCT pa.value
                 FROM product_attributes pa
                 INNER JOIN products p ON p.id = pa.product_id
                 WHERE pa.attribute_id = ? AND pa.value IS NOT NULL AND pa.value <> ''
                 ORDER BY pa.value",
                [$attribute['id']]
            ) ?? [];

            if (empty($values)) {
                continue;
            }

            $filterOptions[$attribute['id']] = [
                'id' => $attribute['id'],
                'name' => $attribute['name'],
                'slug' => $attribute['slug'],
                'type' => $attribute['type'],
                'options' => []
            ];

            foreach ($values as $valueRow) {
                $value = $valueRow['value'];
                $countResult = Product::query(
                    "SELECT COUNT(DISTINCT product_id) as count
                     FROM product_attributes
                     WHERE attribute_id = ? AND value = ?",
                    [$attribute['id'], $value]
                );

                $filterOptions[$attribute['id']]['options'][] = [
                    'value' => $value,
                    'label' => $value,
                    'count' => (int) ($countResult[0]['count'] ?? 0),
                    'color' => null,
                ];
            }
        }

        return $filterOptions;
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
                    if (is_array($values) && (isset($values['min']) || isset($values['max']))) {
                        if ($values['min'] !== '' && $values['min'] !== null) {
                            $queryParams["attr_{$attributeId}_min"] = $values['min'];
                        }

                        if ($values['max'] !== '' && $values['max'] !== null) {
                            $queryParams["attr_{$attributeId}_max"] = $values['max'];
                        }

                        continue;
                    }

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
                if (substr($key, -4) === '_min' || substr($key, -4) === '_max') {
                    $bound = substr($key, -3) === 'min' ? 'min' : 'max';
                    $attributeId = (int) substr($key, 5, -4);
                    $filters['attributes'][$attributeId][$bound] = is_numeric($value) ? (float) $value : $value;
                    continue;
                }

                if (is_array($value)) {
                    $filters['attributes'][$attributeId] = array_values(array_filter($value, function ($item) {
                        return $item !== '' && $item !== null;
                    }));
                    continue;
                }

                $filters['attributes'][$attributeId] = explode(',', $value);
            }
        }

        return $filters;
    }
}
