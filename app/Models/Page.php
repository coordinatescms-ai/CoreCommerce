<?php

namespace App\Models;

use App\Core\Database\DB;
use PDO;

class Page 
{ 
    public function create(array $data) 
    { 
        $sql = "INSERT INTO pages (title, slug, content, is_active, sort_order, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?)"; 
    
        $params = [ 
            $data['title'], 
            $data['slug'], 
            $data['content'], 
            $data['is_active'],
            $data['sort_order'],
            $data['meta_title'],      // 6
            $data['meta_description'] // 7
        ];

        return DB::query($sql, $params); 
    }

    public function update($id, array $data) 
    {
        $sql = "UPDATE pages SET title = ?, slug = ?, content = ?, is_active = ?, sort_order = ?, meta_title = ?, meta_description = ? WHERE id = ?";
    
        // Створюємо список значень БЕЗ текстових ключів
        $params = [
            $data['title'],            // 1
            $data['slug'],             // 2
            $data['content'],          // 3
            $data['is_active'],        // 4
            $data['sort_order'],       // 5
            $data['meta_title'],       // 6 (SEO)
            $data['meta_description'], // 7 (SEO)
            $id                        // 8 (WHERE id = ?)
        ];

        return DB::query($sql, $params);
    }

    public function getPublished()
    {
        // Сортуємо спочатку за пріоритетом (0, 1, 2...), потім за алфавітом
        return DB::query("SELECT title, slug FROM pages WHERE is_active = 1 ORDER BY sort_order ASC, title ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) 
    {
        return DB::query("SELECT * FROM pages WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
    }

    public function getBySlug($slug) 
    {
        return DB::query("SELECT * FROM pages WHERE slug = ? LIMIT 1", [$slug])->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() 
    { 
        return DB::query("SELECT * FROM pages ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function isSlugUnique($slug, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT id FROM pages WHERE slug = ? AND id != ? LIMIT 1";
            $result = DB::query($sql, [$slug, $excludeId])->fetch();
        } else {
            $sql = "SELECT id FROM pages WHERE slug = ? LIMIT 1";
            $result = DB::query($sql, [$slug])->fetch();
        }
        return !$result;
    }

    public function delete($id) 
    {
        return DB::query("DELETE FROM pages WHERE id = ?", [$id]);
    }
}

