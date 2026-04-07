<?php

namespace App\Models;

use App\Core\Model;

class ProductAttributeValue extends Model
{
    protected static $table = 'product_attribute_values';

    /**
     * Отримати призначені атрибути товару (EAV).
     *
     * @param int $productId
     * @return array
     */
    public static function getByProduct($productId)
    {
        $result = self::query(
            "SELECT pav.*, a.name AS attribute_name, a.slug AS attribute_slug, a.type AS attribute_type, pav.value_text AS value,
                    COALESCE(pav.is_selectable, 0) AS is_selectable
             FROM " . static::$table . " pav
             INNER JOIN attributes a ON pav.attribute_id = a.id
             WHERE pav.product_id = ?
             ORDER BY a.sort_order, a.name",
            [(int) $productId]
        );

        return $result ?? [];
    }

    /**
     * Видалити всі значення атрибутів товару.
     *
     * @param int $productId
     * @return bool
     */
    public static function deleteAll($productId)
    {
        return self::execute(
            "DELETE FROM " . static::$table . " WHERE product_id = ?",
            [(int) $productId]
        );
    }

    /**
     * Додати значення атрибута товару.
     *
     * @param int $productId
     * @param int $attributeId
     * @param string $valueText
     * @param bool $isSelectable
     * @return bool
     */
    public static function addValue($productId, $attributeId, $valueText, $isSelectable = false)
    {
        return self::execute(
            "INSERT INTO " . static::$table . " (product_id, attribute_id, value_text, is_selectable) VALUES (?, ?, ?, ?)",
            [(int) $productId, (int) $attributeId, (string) $valueText, $isSelectable ? 1 : 0]
        );
    }
}
