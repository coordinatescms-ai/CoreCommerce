<?php

namespace App\Models;

use App\Core\Model;

class ProductAttribute extends Model
{
    protected static $table = 'product_attributes';

    /**
     * Отримати атрибути товару
     * 
     * @param int $productId
     * @return array
     */
    public static function getByProduct($productId)
    {
        $result = self::query(
            "SELECT pa.*, a.name as attribute_name, a.slug as attribute_slug, a.type as attribute_type 
             FROM " . static::$table . " pa 
             INNER JOIN attributes a ON pa.attribute_id = a.id 
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
    public static function setValue($productId, $attributeId, $value, $optionId = null)
    {
        // Перевірити, чи існує запис
        $existing = self::getValue($productId, $attributeId);
        
        if ($existing) {
            // Оновити
            return self::execute(
                "UPDATE " . static::$table . " SET value = ?, attribute_option_id = ? WHERE product_id = ? AND attribute_id = ?",
                [$value, $optionId, $productId, $attributeId]
            );
        } else {
            // Вставити
            return self::execute(
                "INSERT INTO " . static::$table . " (product_id, attribute_id, value, attribute_option_id) VALUES (?, ?, ?, ?)",
                [$productId, $attributeId, $value, $optionId]
            );
        }
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
        $result = self::query(
            "SELECT DISTINCT pa.value, pa.attribute_option_id, ao.name as option_name, ao.color_code
             FROM " . static::$table . " pa
             INNER JOIN products p ON pa.product_id = p.id
             LEFT JOIN attribute_options ao ON pa.attribute_option_id = ao.id
             WHERE p.category_id = ? AND pa.attribute_id = ?
             ORDER BY pa.value",
            [$categoryId, $attributeId]
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
        $result = self::query(
            "SELECT COUNT(DISTINCT p.id) as count
             FROM products p
             INNER JOIN " . static::$table . " pa ON p.id = pa.product_id
             WHERE p.category_id = ? AND pa.attribute_id = ? AND pa.value = ?",
            [$categoryId, $attributeId, $value]
        );
        
        return !empty($result) ? $result[0]['count'] : 0;
    }
}
