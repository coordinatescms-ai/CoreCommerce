<?php
namespace App\Core\Database;
use PDO;

class DB {
    static $pdo;
    
    static function connect($d, $u, $p) {
        self::$pdo = new PDO($d, $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    
    static function query($s, $p = []) {
        $q = self::$pdo->prepare($s);
        
        // Явно зв'язуємо кожний параметр з правильним типом даних
        foreach ($p as $key => $value) {
            // Індекс параметра в PDO починається з 1
            $paramIndex = $key + 1;
            
            // Визначаємо тип параметра
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }
            
            // Зв'язуємо параметр з явним типом
            $q->bindValue($paramIndex, $value, $type);
        }
        
        $q->execute();
        return $q;
    }
    
    static function execute($s, $p = []) {
        return self::query($s, $p);
    }
    
    static function getInstance() {
        return new self();
    }
    
    public function __call($name, $args) {
        if ($name === 'query' || $name === 'execute') {
            return self::$name(...$args);
        }
    }
}
