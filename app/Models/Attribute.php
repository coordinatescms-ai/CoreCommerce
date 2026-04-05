<?php

namespace App\Models;

use App\Core\Model;

class Attribute extends Model
{
    protected static $table = 'attributes';

    /**
     * Дозволені типи атрибутів у БД.
     * number з UI зберігається як range для сумісності з поточними фільтрами.
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_SELECT = 'select';
    public const TYPE_RANGE = 'range';

    /**
     * Отримати атрибут за ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function findById($id)
    {
        $result = self::query("SELECT * FROM " . self::$table . " WHERE id = ?", [$id]);
        return $result ? $result[0] : null;
    }

    /**
     * Отримати атрибут за slug
     * 
     * @param string $slug
     * @return array|null
     */
    public static function findBySlug($slug)
    {
        $result = self::query("SELECT * FROM " . self::$table . " WHERE slug = ?", [$slug]);
        return $result ? $result[0] : null;
    }

    /**
     * Отримати атрибут за назвою (без урахування регістру)
     *
     * @param string $name
     * @return array|null
     */
    public static function findByName($name)
    {
        $result = self::query(
            "SELECT * FROM " . self::$table . " WHERE LOWER(name) = LOWER(?) LIMIT 1",
            [trim((string) $name)]
        );

        return $result ? $result[0] : null;
    }

    /**
     * Отримати всі атрибути
     * 
     * @param bool $filterableOnly
     * @return array
     */
    public static function all($filterableOnly = false)
    {
        $query = "SELECT * FROM " . self::$table;
        $params = [];
        
        if ($filterableOnly) {
            $query .= " WHERE is_filterable = 1";
        }
        
        $query .= " ORDER BY sort_order, name";
        
        return self::query($query, $params) ?? [];
    }

    /**
     * Отримати атрибути для адмін-списку з агрегатами.
     *
     * @return array
     */
    public static function allForAdmin()
    {
        $result = self::query(
            "SELECT a.*,
                    COUNT(DISTINCT ca.category_id) AS categories_count,
                    COUNT(DISTINCT pa.product_id) AS products_count
             FROM attributes a
             LEFT JOIN category_attributes ca ON ca.attribute_id = a.id
             LEFT JOIN product_attributes pa ON pa.attribute_id = a.id
             GROUP BY a.id
             ORDER BY a.sort_order, a.name"
        );

        return $result ?? [];
    }

    /**
     * Отримати опції атрибута
     * 
     * @param int $attributeId
     * @return array
     */
    public static function getOptions($attributeId)
    {
        $result = self::query(
            "SELECT * FROM attribute_options WHERE attribute_id = ? ORDER BY sort_order, name",
            [$attributeId]
        );
        
        return $result ?? [];
    }

    /**
     * Отримати опцію атрибута за ID
     * 
     * @param int $optionId
     * @return array|null
     */
    public static function getOption($optionId)
    {
        $result = self::query("SELECT * FROM attribute_options WHERE id = ?", [$optionId]);
        return $result ? $result[0] : null;
    }

    /**
     * Отримати опцію атрибута за значенням/назвою (без урахування регістру)
     *
     * @param int $attributeId
     * @param string $value
     * @return array|null
     */
    public static function findOptionByValue($attributeId, $value)
    {
        $normalizedValue = trim((string) $value);
        if ($normalizedValue === '') {
            return null;
        }

        $result = self::query(
            "SELECT * FROM attribute_options
             WHERE attribute_id = ?
               AND (LOWER(value) = LOWER(?) OR LOWER(name) = LOWER(?))
             LIMIT 1",
            [(int) $attributeId, $normalizedValue, $normalizedValue]
        );

        return $result ? $result[0] : null;
    }

    /**
     * Створити новий атрибут
     * 
     * @param array $data
     * @return int|false
     */
    public static function create($data)
    {
        // Генерувати slug, якщо не надано
        if (empty($data['slug'])) {
            $data['slug'] = self::generateSlug($data['name']);
        }

        // Забезпечити унікальність slug
        $baseSlug = $data['slug'];
        $suffix = 2;
        while (self::findBySlug($data['slug'])) {
            $data['slug'] = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $query = "INSERT INTO " . self::$table . " (" . implode(',', $columns) . ") 
                  VALUES (" . implode(',', $placeholders) . ")";
        
        $result = self::execute($query, array_values($data));
        
        if ($result) {
            return self::getLastInsertId();
        }
        
        return false;
    }

    /**
     * Оновити атрибут
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data)
    {
        $updates = [];
        $values = [];

        foreach ($data as $column => $value) {
            $updates[] = "$column = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $query = "UPDATE " . self::$table . " SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return self::execute($query, $values);
    }

    /**
     * Видалити атрибут
     * 
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        return self::execute("DELETE FROM " . self::$table . " WHERE id = ?", [$id]);
    }

    /**
     * Створити опцію атрибута
     * 
     * @param int $attributeId
     * @param array $data
     * @return int|false
     */
    public static function createOption($attributeId, $data)
    {
        $data['attribute_id'] = $attributeId;
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $query = "INSERT INTO attribute_options (" . implode(',', $columns) . ") 
                  VALUES (" . implode(',', $placeholders) . ")";
        
        $result = self::execute($query, array_values($data));
        
        if ($result) {
            return self::getLastInsertId();
        }
        
        return false;
    }

    /**
     * Оновити опцію атрибута
     * 
     * @param int $optionId
     * @param array $data
     * @return bool
     */
    public static function updateOption($optionId, $data)
    {
        $updates = [];
        $values = [];

        foreach ($data as $column => $value) {
            $updates[] = "$column = ?";
            $values[] = $value;
        }

        $values[] = $optionId;

        $query = "UPDATE attribute_options SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return self::execute($query, $values);
    }

    /**
     * Видалити опцію атрибута
     * 
     * @param int $optionId
     * @return bool
     */
    public static function deleteOption($optionId)
    {
        return self::execute("DELETE FROM attribute_options WHERE id = ?", [$optionId]);
    }

    /**
     * Видалити всі опції конкретного атрибута.
     *
     * @param int $attributeId
     * @return bool
     */
    public static function deleteAllOptions($attributeId)
    {
        return (bool) self::execute("DELETE FROM attribute_options WHERE attribute_id = ?", [(int) $attributeId]);
    }

    /**
     * Отримати ID категорій, до яких прив'язано атрибут.
     *
     * @param int $attributeId
     * @return array
     */
    public static function getAssignedCategoryIds($attributeId)
    {
        $result = self::query(
            "SELECT category_id FROM category_attributes WHERE attribute_id = ? ORDER BY category_id",
            [(int) $attributeId]
        );

        if (!$result) {
            return [];
        }

        return array_map('intval', array_column($result, 'category_id'));
    }

    /**
     * Синхронізувати прив'язки атрибута до категорій.
     *
     * @param int $attributeId
     * @param array $categoryIds
     * @return void
     */
    public static function syncCategories($attributeId, $categoryIds)
    {
        $attributeId = (int) $attributeId;
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $categoryIds), function ($id) {
            return $id > 0;
        })));

        self::execute("DELETE FROM category_attributes WHERE attribute_id = ?", [$attributeId]);

        foreach ($categoryIds as $sortOrder => $categoryId) {
            self::execute(
                "INSERT INTO category_attributes (category_id, attribute_id, is_required, sort_order)
                 VALUES (?, ?, 0, ?)",
                [$categoryId, $attributeId, $sortOrder]
            );
        }
    }

    /**
     * Нормалізація типу атрибута перед збереженням.
     * number -> range (сумісність зі схемою та фільтрами).
     *
     * @param string $rawType
     * @return string
     */
    public static function normalizeTypeForStorage($rawType)
    {
        $type = strtolower(trim((string) $rawType));
        if ($type === 'number') {
            return self::TYPE_RANGE;
        }

        $allowed = [self::TYPE_TEXT, self::TYPE_SELECT, self::TYPE_RANGE, 'multiselect', 'color'];
        if (!in_array($type, $allowed, true)) {
            return self::TYPE_TEXT;
        }

        return $type;
    }

    /**
     * Генерувати slug з назви
     * 
     * @param string $name
     * @return string
     */
    private static function generateSlug($name)
    {
        $slug = mb_strtolower($name, 'UTF-8');
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Отримати останній ID вставленого запису
     * 
     * @return int
     */
    private static function getLastInsertId()
    {
        $result = self::query("SELECT LAST_INSERT_ID() as id");
        return $result ? $result[0]['id'] : 0;
    }

    /**
     * Отримати список назв атрибутів для автодоповнення
     *
     * @return array
     */
    public static function getAllNames()
    {
        $result = self::query("SELECT DISTINCT name FROM " . self::$table . " ORDER BY name ASC");
        if (!$result) {
            return [];
        }

        return array_values(array_filter(array_map(function ($row) {
            return $row['name'] ?? null;
        }, $result)));
    }
}
