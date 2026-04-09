<?php

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static $table = 'settings';

    /**
     * Отримати значення налаштування за ключем
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $result = self::query("SELECT `value` FROM settings WHERE `key` = ?", [$key]);
        return !empty($result) ? $result[0]['value'] : $default;
    }

    /**
     * Отримати всі налаштування, згруповані за групами
     * 
     * @return array
     */
    public static function getAllGrouped()
    {
        $settings = self::query("SELECT * FROM settings ORDER BY `group`, `key` ASC");
        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting['group']][] = $setting;
        }
        return $grouped;
    }

    /**
     * Оновити або створити налаштування
     * 
     * @param string $key
     * @param string $value
     * @return bool
     */
    public static function set($key, $value)
    {
        $exists = self::query("SELECT `key` FROM settings WHERE `key` = ?", [$key]);
        if (!empty($exists)) {
            return self::execute("UPDATE settings SET `value` = ? WHERE `key` = ?", [$value, $key]);
        } else {
            return self::execute("INSERT INTO settings (`key`, `value`) VALUES (?, ?)", [$key, $value]);
        }
    }

    /**
     * Оновити декілька налаштувань одночасно
     * 
     * @param array $settings [key => value]
     * @return bool
     */
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
