<?php

namespace App\Services;

use App\Core\Database\DB as Database;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\Attribute;

class ProductFilterService
{
    private const FILTER_OPTION_PREFIX = 'opt:';

    private static function getCategoryWithChildrenIds($categoryId)
    {
        $allCategories = [(int) $categoryId];
        $children = Category::getAllChildren($categoryId);

        foreach ($children as $child) {
            $allCategories[] = (int) $child['id'];
        }

        return array_values(array_unique($allCategories));
    }

    private static function normalizeFilters(array $filters): array
    {
        $normalized = [
            'category_id' => !empty($filters['category_id']) ? (int) $filters['category_id'] : null,
            'min_price' => isset($filters['min_price']) && $filters['min_price'] !== '' ? (float) $filters['min_price'] : null,
            'max_price' => isset($filters['max_price']) && $filters['max_price'] !== '' ? (float) $filters['max_price'] : null,
            'attributes' => [],
            'sort_by' => $filters['sort_by'] ?? 'name',
            'sort_order' => strtoupper((string) ($filters['sort_order'] ?? 'ASC')),
            'limit' => isset($filters['limit']) ? max(1, (int) $filters['limit']) : null,
            'offset' => isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0,
        ];

        if (empty($filters['attributes']) || !is_array($filters['attributes'])) {
            return $normalized;
        }

        foreach ($filters['attributes'] as $attributeId => $rawFilter) {
            $attributeId = (int) $attributeId;
            if ($attributeId <= 0 || $rawFilter === '' || $rawFilter === null || $rawFilter === []) {
                continue;
            }

            if (is_array($rawFilter) && (array_key_exists('min', $rawFilter) || array_key_exists('max', $rawFilter))) {
                $min = ($rawFilter['min'] ?? '') !== '' ? (float) $rawFilter['min'] : null;
                $max = ($rawFilter['max'] ?? '') !== '' ? (float) $rawFilter['max'] : null;
                if ($min !== null || $max !== null) {
                    $normalized['attributes'][$attributeId] = ['type' => 'range', 'min' => $min, 'max' => $max];
                }
                continue;
            }

            $values = is_array($rawFilter) ? $rawFilter : explode(',', (string) $rawFilter);
            $optionIds = [];
            $plainValues = [];

            foreach ($values as $value) {
                if ($value === '' || $value === null) {
                    continue;
                }

                if (is_string($value) && strpos($value, self::FILTER_OPTION_PREFIX) === 0) {
                    $optionId = (int) substr($value, strlen(self::FILTER_OPTION_PREFIX));
                    if ($optionId > 0) {
                        $optionIds[] = $optionId;
                    }
                    continue;
                }

                if (is_numeric($value) && ctype_digit((string) $value)) {
                    $optionIds[] = (int) $value;
                    continue;
                }

                $plainValues[] = (string) $value;
            }

            $optionIds = array_values(array_unique(array_filter($optionIds, function ($id) {
                return $id > 0;
            })));
            $plainValues = array_values(array_unique(array_filter($plainValues, function ($value) {
                return trim($value) !== '';
            })));

            if (!empty($optionIds) || !empty($plainValues)) {
                $normalized['attributes'][$attributeId] = [
                    'type' => 'match',
                    'option_ids' => $optionIds,
                    'values' => $plainValues,
                ];
            }
        }

        return $normalized;
    }

    private static function buildBaseProductQuery(array $normalizedFilters): array
    {
        $conditions = ['p.is_visible = 1'];
        $params = [];

        if (!empty($normalizedFilters['category_id'])) {
            $category = Category::findById($normalizedFilters['category_id']);
            if ($category) {
                $categoryIds = self::getCategoryWithChildrenIds($normalizedFilters['category_id']);
                $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $conditions[] = "p.category_id IN ($placeholders)";
                $params = array_merge($params, $categoryIds);
            }
        }

        if ($normalizedFilters['min_price'] !== null) {
            $conditions[] = 'p.price >= ?';
            $params[] = $normalizedFilters['min_price'];
        }

        if ($normalizedFilters['max_price'] !== null) {
            $conditions[] = 'p.price <= ?';
            $params[] = $normalizedFilters['max_price'];
        }

        foreach ($normalizedFilters['attributes'] as $attributeId => $attributeFilter) {
            $existsSql = 'EXISTS (SELECT 1 FROM product_attributes pa WHERE pa.product_id = p.id AND pa.attribute_id = ?';
            $existsParams = [(int) $attributeId];

            if ($attributeFilter['type'] === 'range') {
                $existsSql .= " AND pa.value REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'";
                if ($attributeFilter['min'] !== null) {
                    $existsSql .= ' AND CAST(pa.value AS DECIMAL(12,2)) >= ?';
                    $existsParams[] = $attributeFilter['min'];
                }
                if ($attributeFilter['max'] !== null) {
                    $existsSql .= ' AND CAST(pa.value AS DECIMAL(12,2)) <= ?';
                    $existsParams[] = $attributeFilter['max'];
                }
            } else {
                $matchConditions = [];
                if (!empty($attributeFilter['option_ids'])) {
                    $placeholders = implode(',', array_fill(0, count($attributeFilter['option_ids']), '?'));
                    $matchConditions[] = "pa.attribute_option_id IN ($placeholders)";
                    $existsParams = array_merge($existsParams, $attributeFilter['option_ids']);
                }

                if (!empty($attributeFilter['values'])) {
                    $placeholders = implode(',', array_fill(0, count($attributeFilter['values']), '?'));
                    $matchConditions[] = "pa.value IN ($placeholders)";
                    $existsParams = array_merge($existsParams, $attributeFilter['values']);
                }

                if (empty($matchConditions)) {
                    continue;
                }

                $existsSql .= ' AND (' . implode(' OR ', $matchConditions) . ')';
            }

            $existsSql .= ')';
            $conditions[] = $existsSql;
            $params = array_merge($params, $existsParams);
        }

        return [
            'where' => implode(' AND ', $conditions),
            'params' => $params,
        ];
    }

    public static function filter($filters = [])
    {
        $db = Database::getInstance();
        $normalizedFilters = self::normalizeFilters($filters);
        $base = self::buildBaseProductQuery($normalizedFilters);

        $query = 'SELECT p.* FROM products p WHERE ' . $base['where'];
        $params = $base['params'];

        $allowedSortBy = ['name', 'price', 'created_at', 'popularity'];
        $allowedSortOrder = ['ASC', 'DESC'];

        $sortBy = in_array($normalizedFilters['sort_by'], $allowedSortBy, true) ? $normalizedFilters['sort_by'] : 'name';
        $sortOrder = in_array($normalizedFilters['sort_order'], $allowedSortOrder, true) ? $normalizedFilters['sort_order'] : 'ASC';
        $query .= " ORDER BY p.{$sortBy} {$sortOrder}";

        if ($normalizedFilters['limit'] !== null) {
            $query .= ' LIMIT ? OFFSET ?';
            $params[] = $normalizedFilters['limit'];
            $params[] = $normalizedFilters['offset'];
        }

        return $db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC) ?? [];
    }

    public static function count($filters = [])
    {
        $db = Database::getInstance();
        $normalizedFilters = self::normalizeFilters($filters);
        $base = self::buildBaseProductQuery($normalizedFilters);

        $result = $db->query('SELECT COUNT(*) as count FROM products p WHERE ' . $base['where'], $base['params'])->fetchAll(\PDO::FETCH_ASSOC);
        return !empty($result) ? (int) $result[0]['count'] : 0;
    }

    public static function getFilterOptions($categoryId, $currentFilters = [])
    {
        $category = Category::findById($categoryId);
        if (!$category) {
            return [];
        }

        $attributes = Category::getAllowedAttributes($categoryId);
        $categoryIds = self::getCategoryWithChildrenIds($categoryId);
        $baseFilters = self::normalizeFilters($currentFilters);
        $baseFilters['category_id'] = (int) $categoryId;

        $options = [];
        foreach ($attributes as $attribute) {
            if (empty($attribute['is_filterable'])) {
                continue;
            }

            $attributeId = (int) $attribute['id'];
            $options[$attributeId] = [
                'id' => $attributeId,
                'name' => $attribute['name'],
                'slug' => $attribute['slug'],
                'type' => $attribute['type'],
                'options' => [],
            ];

            if ($attribute['type'] === 'range') {
                $options[$attributeId]['range'] = ProductAttribute::getNumericRangeForCategory($categoryIds, $attributeId);
                continue;
            }

            $values = ProductAttribute::getUniqueValuesForCategory($categoryIds, $attributeId);
            foreach ($values as $value) {
                $optionId = isset($value['attribute_option_id']) ? (int) $value['attribute_option_id'] : 0;
                $rawValue = (string) ($value['value'] ?? '');

                $attributeSpecificFilter = [
                    $attributeId => [
                        'type' => 'match',
                        'option_ids' => $optionId > 0 ? [$optionId] : [],
                        'values' => $optionId > 0 ? [] : [$rawValue],
                    ],
                ];

                $countFilters = $baseFilters;
                $countFilters['attributes'][$attributeId] = $attributeSpecificFilter[$attributeId];
                $count = self::count($countFilters);

                $options[$attributeId]['options'][] = [
                    'value' => $optionId > 0 ? self::FILTER_OPTION_PREFIX . $optionId : $rawValue,
                    'label' => $optionId > 0 ? (string) ($value['option_name'] ?? $rawValue) : $rawValue,
                    'color' => $value['color_code'] ?? null,
                    'count' => $count,
                ];
            }
        }

        return $options;
    }

    public static function getPriceRange($categoryId)
    { /* unchanged */
        $db = Database::getInstance();
        $allCategories = [$categoryId];
        $children = Category::getAllChildren($categoryId);
        foreach ($children as $child) {
            $allCategories[] = $child['id'];
        }
        $placeholders = implode(',', array_fill(0, count($allCategories), '?'));
        $result = $db->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE category_id IN ($placeholders) AND is_visible = 1", $allCategories)->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return ['min' => (float) $result[0]['min_price'], 'max' => (float) $result[0]['max_price']];
        }
        return ['min' => 0, 'max' => 0];
    }

    public static function getGlobalPriceRange(){/* unchanged */
        $db = Database::getInstance();
        $result = $db->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_visible = 1")->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($result) && $result[0]['min_price'] !== null && $result[0]['max_price'] !== null) {
            return ['min' => (float) $result[0]['min_price'], 'max' => (float) $result[0]['max_price']];
        }
        return ['min' => 0.0, 'max' => 0.0];
    }

    public static function getCatalogFilterOptions(){/* unchanged */
        $attributes = Attribute::all(true); $filterOptions = [];
        foreach ($attributes as $attribute) {
            $values = Product::query("SELECT DISTINCT pa.value FROM product_attributes pa INNER JOIN products p ON p.id = pa.product_id WHERE pa.attribute_id = ? AND pa.value IS NOT NULL AND pa.value <> '' ORDER BY pa.value", [$attribute['id']]) ?? [];
            if (empty($values)) { continue; }
            $filterOptions[$attribute['id']] = ['id'=>$attribute['id'],'name'=>$attribute['name'],'slug'=>$attribute['slug'],'type'=>$attribute['type'],'options'=>[]];
            foreach ($values as $valueRow) {
                $value = $valueRow['value'];
                $countResult = Product::query("SELECT COUNT(DISTINCT product_id) as count FROM product_attributes WHERE attribute_id = ? AND value = ?", [$attribute['id'], $value]);
                $filterOptions[$attribute['id']]['options'][] = ['value'=>$value,'label'=>$value,'count'=>(int) ($countResult[0]['count'] ?? 0),'color'=>null];
            }
        }
        return $filterOptions;
    }

    public static function getPopularAttributes($categoryId, $limit = 5){/* unchanged */
        $attributes = Category::getAttributes($categoryId); $popular = [];
        foreach ($attributes as $attribute) {
            if (!$attribute['is_filterable']) { continue; }
            $values = ProductAttribute::getUniqueValuesForCategory($categoryId, $attribute['id']);
            if (!empty($values)) { $popular[] = ['id'=>$attribute['id'],'name'=>$attribute['name'],'slug'=>$attribute['slug'],'type'=>$attribute['type'],'value_count'=>count($values)]; }
        }
        usort($popular, function($a, $b) { return $b['value_count'] - $a['value_count']; });
        return array_slice($popular, 0, $limit);
    }

    public static function getFilterUrl($categoryId, $filters = [])
    {
        $url = '/category/' . Category::findById($categoryId)['slug'];
        if (empty($filters)) { return $url; }
        $queryParams = [];
        if (!empty($filters['min_price'])) { $queryParams['min_price'] = $filters['min_price']; }
        if (!empty($filters['max_price'])) { $queryParams['max_price'] = $filters['max_price']; }
        if (!empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeId => $values) {
                if (empty($values)) { continue; }
                if (is_array($values) && (isset($values['min']) || isset($values['max']))) {
                    if ($values['min'] !== '' && $values['min'] !== null) { $queryParams["attr_{$attributeId}_min"] = $values['min']; }
                    if ($values['max'] !== '' && $values['max'] !== null) { $queryParams["attr_{$attributeId}_max"] = $values['max']; }
                    continue;
                }
                $queryParams["attr_{$attributeId}"] = is_array($values) ? implode(',', $values) : $values;
            }
        }
        return !empty($queryParams) ? ($url . '?' . http_build_query($queryParams)) : $url;
    }

    public static function parseFiltersFromUrl($queryParams = [])
    {
        $filters = [];
        if (!empty($queryParams['min_price'])) { $filters['min_price'] = (float) $queryParams['min_price']; }
        if (!empty($queryParams['max_price'])) { $filters['max_price'] = (float) $queryParams['max_price']; }
        $filters['attributes'] = [];
        foreach ($queryParams as $key => $value) {
            if (strpos($key, 'attr_') !== 0) { continue; }
            if (substr($key, -4) === '_min' || substr($key, -4) === '_max') {
                $bound = substr($key, -3) === 'min' ? 'min' : 'max';
                $attributeId = (int) substr($key, 5, -4);
                $filters['attributes'][$attributeId][$bound] = is_numeric($value) ? (float) $value : $value;
                continue;
            }
            $attributeId = (int) substr($key, 5);
            if (is_array($value)) {
                $filters['attributes'][$attributeId] = array_values(array_filter($value, fn($item) => $item !== '' && $item !== null));
            } else {
                $filters['attributes'][$attributeId] = array_values(array_filter(explode(',', (string) $value), fn($item) => $item !== '' && $item !== null));
            }
        }
        return $filters;
    }
}
