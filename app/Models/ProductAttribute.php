<?php

namespace App\Models;

use App\Core\Model;

class ProductAttribute extends Model
{
    protected static $table = 'product_attributes';

    /**
     * Нормалізувати список категорій до масиву id.
     *
     * @param int|array $categoryIds
     * @return array
     */
    private static function normalizeCategoryIds($categoryIds)
    {
        if (is_array($categoryIds)) {
            $ids = array_map('intval', $categoryIds);
        } else {
            $ids = [(int) $categoryIds];
        }

        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return $id > 0;
        })));

        return $ids;
    }

    /**
     * Отримати атрибути товару
     * 
     * @param int $productId
     * @return array
     */
    public static function getByProduct($productId)
    {
        $result = self::query(
            "SELECT pa.*, a.name as attribute_name, a.slug as attribute_slug, a.type as attribute_type, ao.name as option_name, ao.color_code
             FROM " . static::$table . " pa 
             INNER JOIN attributes a ON pa.attribute_id = a.id 
             LEFT JOIN attribute_options ao ON ao.id = pa.attribute_option_id
             WHERE pa.product_id = ? 
             ORDER BY a.sort_order, a.name",
            [$productId]
        );
        
        return $result ?? [];
    }

    /**
     * Отримати значення атрибута товару
     * 
     * @param int $productId
     * @param int $attributeId
     * @return array|null
     */
    public static function getValue($productId, $attributeId)
    {
        $result = self::query(
            "SELECT * FROM " . static::$table . " WHERE product_id = ? AND attribute_id = ?",
            [$productId, $attributeId]
        );
        
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Встановити значення атрибута товару
     * 
     * @param int $productId
     * @param int $attributeId
     * @param mixed $value
     * @param int|null $optionId
     * @return bool
     */
    public static function setValue($productId, $attributeId, $value, $optionId = null, array $extra = [])
    {
        $sku = $extra['sku'] ?? null;
        $priceModifier = isset($extra['price_modifier']) ? (float) $extra['price_modifier'] : 0.0;
        $priceOperation = (($extra['price_operation'] ?? '+') === '-') ? '-' : '+';
        $stockQuantity = array_key_exists('stock_quantity', $extra) ? $extra['stock_quantity'] : null;
        $isSelectable = !empty($extra['is_selectable']) ? 1 : 0;

        // Перевірити, чи існує запис для цієї самої пари "атрибут + значення/опція"
        $existing = self::query(
            "SELECT id FROM " . static::$table . "
             WHERE product_id = ? AND attribute_id = ? AND (
                 (attribute_option_id IS NOT NULL AND attribute_option_id = ?)
                 OR
                 (attribute_option_id IS NULL AND ? IS NULL AND value = ?)
             )
             LIMIT 1",
            [$productId, $attributeId, $optionId, $optionId, $value]
        );
        $existingId = !empty($existing) ? (int) ($existing[0]['id'] ?? 0) : 0;
        
        if ($existingId > 0) {
            // Оновити
            return self::execute(
                "UPDATE " . static::$table . " SET value = ?, attribute_option_id = ?, sku = ?, price_modifier = ?, price_operation = ?, stock_quantity = ?, is_selectable = ? WHERE id = ?",
                [$value, $optionId, $sku, $priceModifier, $priceOperation, $stockQuantity, $isSelectable, $existingId]
            );
        } else {
            // Вставити
            return self::execute(
                "INSERT INTO " . static::$table . " (product_id, attribute_id, value, attribute_option_id, sku, price_modifier, price_operation, stock_quantity, is_selectable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$productId, $attributeId, $value, $optionId, $sku, $priceModifier, $priceOperation, $stockQuantity, $isSelectable]
            );
        }
    }

    public static function refreshSkuForProduct(int $productId, string $baseSku): void
    {
        $baseSku = trim($baseSku);
        if ($baseSku === '') {
            self::execute("UPDATE " . static::$table . " SET sku = NULL WHERE product_id = ?", [$productId]);
            self::execute("UPDATE product_stocks SET sku = NULL WHERE product_id = ?", [$productId]);
            return;
        }

        self::execute(
            "UPDATE " . static::$table . " SET sku = CONCAT(?, '-', attribute_option_id) WHERE product_id = ? AND attribute_option_id IS NOT NULL",
            [$baseSku, $productId]
        );
        self::execute(
            "UPDATE product_stocks ps
             INNER JOIN " . static::$table . " pa ON pa.product_id = ps.product_id AND pa.attribute_option_id = ps.option_id
             SET ps.sku = pa.sku, ps.updated_at = NOW()
             WHERE ps.product_id = ?",
            [$productId]
        );
    }

    /**
     * Видалити атрибут товару
     * 
     * @param int $productId
     * @param int $attributeId
     * @return bool
     */
    public static function delete($productId, $attributeId)
    {
        return self::execute(
            "DELETE FROM " . static::$table . " WHERE product_id = ? AND attribute_id = ?",
            [$productId, $attributeId]
        );
    }

    /**
     * Видалити всі атрибути товару
     * 
     * @param int $productId
     * @return bool
     */
    public static function deleteAll($productId)
    {
        return self::execute(
            "DELETE FROM " . static::$table . " WHERE product_id = ?",
            [$productId]
        );
    }

    /**
     * Отримати товари за значенням атрибута
     * 
     * @param int $attributeId
     * @param string $value
     * @return array
     */
    public static function getProductsByValue($attributeId, $value)
    {
        $result = self::query(
            "SELECT DISTINCT p.* FROM products p 
             INNER JOIN " . static::$table . " pa ON p.id = pa.product_id 
             WHERE pa.attribute_id = ? AND pa.value = ?",
            [$attributeId, $value]
        );
        
        return $result ?? [];
    }

    /**
     * Отримати товари за опцією атрибута
     * 
     * @param int $optionId
     * @return array
     */
    public static function getProductsByOption($optionId)
    {
        $result = self::query(
            "SELECT DISTINCT p.* FROM products p 
             INNER JOIN " . static::$table . " pa ON p.id = pa.product_id 
             WHERE pa.attribute_option_id = ?",
            [$optionId]
        );
        
        return $result ?? [];
    }

    /**
     * Отримати унікальні значення атрибута для категорії
     * 
     * @param int $categoryId
     * @param int $attributeId
     * @return array
     */
    public static function getUniqueValuesForCategory($categoryId, $attributeId)
    {
        $categoryIds = self::normalizeCategoryIds($categoryId);

        if (empty($categoryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = array_merge($categoryIds, [$attributeId]);

        $result = self::query(
            "SELECT DISTINCT pa.value, pa.attribute_option_id, ao.name as option_name, ao.color_code
             FROM " . static::$table . " pa
             INNER JOIN products p ON pa.product_id = p.id
             LEFT JOIN attribute_options ao ON pa.attribute_option_id = ao.id
             WHERE p.category_id IN ($placeholders) AND pa.attribute_id = ?
             ORDER BY pa.value",
            $params
        );
        
        return $result ?? [];
    }

    /**
     * Отримати кількість товарів з певним значенням атрибута в категорії
     * 
     * @param int $categoryId
     * @param int $attributeId
     * @param string $value
     * @return int
     */
    public static function getCountInCategory($categoryId, $attributeId, $value)
    {
        $categoryIds = self::normalizeCategoryIds($categoryId);

        if (empty($categoryIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = array_merge($categoryIds, [$attributeId, $value]);

        $result = self::query(
            "SELECT COUNT(DISTINCT p.id) as count
             FROM products p
             INNER JOIN " . static::$table . " pa ON p.id = pa.product_id
             WHERE p.category_id IN ($placeholders) AND pa.attribute_id = ? AND pa.value = ?",
            $params
        );
        
        return !empty($result) ? $result[0]['count'] : 0;
    }

    /**
     * Отримати числовий діапазон значень атрибута в категорії.
     *
     * @param int|array $categoryId
     * @param int $attributeId
     * @return array{min: float, max: float}
     */
    public static function getNumericRangeForCategory($categoryId, $attributeId)
    {
        $categoryIds = self::normalizeCategoryIds($categoryId);

        if (empty($categoryIds)) {
            return ['min' => 0.0, 'max' => 0.0];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = array_merge($categoryIds, [$attributeId]);

        $result = self::query(
            "SELECT MIN(CAST(pa.value AS DECIMAL(12,2))) as min_value,
                    MAX(CAST(pa.value AS DECIMAL(12,2))) as max_value
             FROM " . static::$table . " pa
             INNER JOIN products p ON p.id = pa.product_id
             WHERE p.category_id IN ($placeholders)
               AND pa.attribute_id = ?
               AND pa.value REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'",
            $params
        );

        if (empty($result) || $result[0]['min_value'] === null || $result[0]['max_value'] === null) {
            return ['min' => 0.0, 'max' => 0.0];
        }

        return [
            'min' => (float) $result[0]['min_value'],
            'max' => (float) $result[0]['max_value']
        ];
    }
}
