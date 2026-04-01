<?php
namespace App\Core;

use App\Core\Database\DB;

class Model
{
    protected static $table;

    public static function all()
    {
        return DB::query("SELECT * FROM " . static::$table)->fetchAll();
    }

    public static function find($id)
    {
        return DB::query("SELECT * FROM " . static::$table . " WHERE id=?", [$id])->fetch();
    }

    public static function create($data)
    {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        return DB::query("INSERT INTO " . static::$table . " ($fields) VALUES ($placeholders)", array_values($data));
    }

    public static function query($sql, $params = [])
    {
        return DB::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function execute($sql, $params = [])
    {
        return DB::query($sql, $params);
    }
}