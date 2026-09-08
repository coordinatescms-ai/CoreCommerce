<?php

namespace App\Models;

use App\Core\Model;

class SocialLink extends Model
{
    protected static $table = 'social_links';

    public static function getAll()
    {
        return self::query("SELECT * FROM social_links ORDER BY sort_order ASC, name ASC");
    }

    public static function getActive()
    {
        return self::query("SELECT * FROM social_links WHERE is_active = 1 AND url != '' ORDER BY sort_order ASC");
    }

    public static function updateLink(int $id, array $data)
    {
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 0;
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
        $url = trim((string)($data['url'] ?? ''));
        
        // Basic URL validation
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            // If it doesn't have protocol, try adding https://
            if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                $url = "https://" . $url;
            }
        }

        return self::execute(
            "UPDATE social_links SET url = ?, is_active = ?, sort_order = ?, name = ? WHERE id = ?",
            [$url, $isActive, $sortOrder, $data['name'] ?? '', $id]
        );
    }
    
    public static function add(array $data)
    {
        return self::execute(
            "INSERT INTO social_links (name, slug, url, is_active, sort_order) VALUES (?, ?, ?, ?, ?)",
            [
                $data['name'] ?? 'New Link',
                $data['slug'] ?? 'new-link',
                $data['url'] ?? '',
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                isset($data['sort_order']) ? (int)$data['sort_order'] : 0
            ]
        );
    }

    public static function deleteLink(int $id)
    {
        return self::execute("DELETE FROM social_links WHERE id = ?", [$id]);
    }
}
