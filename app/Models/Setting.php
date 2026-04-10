<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static $table = 'settings';

    public static function get($key, $default = null)
    {
        $result = self::query("SELECT `value` FROM settings WHERE `key` = ?", [$key]);
        return !empty($result) ? $result[0]['value'] : $default;
    }

    public static function getAllGrouped()
    {
        $settings = self::query("SELECT * FROM settings ORDER BY `group`, `key` ASC");
        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting['group']][] = $setting;
        }
        return $grouped;
    }

    public static function set($key, $value)
    {
        return self::setWithMeta($key, $value, 'general', 'text');
    }

    public static function setWithMeta(string $key, $value, string $group = 'general', string $type = 'text')
    {
        $exists = self::query("SELECT `key` FROM settings WHERE `key` = ?", [$key]);
        if (!empty($exists)) {
            return self::execute(
                "UPDATE settings SET `value` = ?, `group` = ?, `type` = ?, `updated_at` = NOW() WHERE `key` = ?",
                [(string) $value, $group, $type, $key]
            );
        }

        return self::execute(
            "INSERT INTO settings (`key`, `value`, `group`, `type`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$key, (string) $value, $group, $type]
        );
    }

    public static function updateMany($settings)
    {
        $success = true;
        foreach ($settings as $key => $value) {
            if (!self::set($key, $value)) {
                $success = false;
            }
        }
        return $success;
    }
}
