<?php

namespace App\Models;

use App\Core\Model;

class ShopMethod extends Model
{
    protected $table = 'shop_methods';

    /**
     * Отримати всі методи певного типу
     */
    public function getAllByType($type)
    {
        $sql = "SELECT * FROM {$this->table} WHERE type = :type ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    /**
     * Оновити JSON-налаштування методу
     */
    public function updateSettings($id, $settings)
    {
        $sql = "UPDATE {$this->table} SET settings = :settings WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
            'id' => $id
        ]);
    }

    /**
     * Перемкнути активність методу
     */
    public function toggleActive($id, $isActive)
    {
        $sql = "UPDATE {$this->table} SET is_active = :is_active WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'is_active' => (int)$isActive,
            'id' => $id
        ]);
    }

    /**
     * Створити новий метод доставки/оплати
     */
    public function createMethod($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (type, code, name, description, is_active, is_test_mode, settings, sort_order)
                VALUES (:type, :code, :name, :description, :is_active, :is_test_mode, :settings, :sort_order)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'type' => $data['type'],
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => (int)($data['is_active'] ?? 1),
            'is_test_mode' => (int)($data['is_test_mode'] ?? 0),
            'settings' => json_encode($data['settings'] ?? [], JSON_UNESCAPED_UNICODE),
            'sort_order' => (int)($data['sort_order'] ?? 0)
        ]);
    }
}