<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static $table = 'users';
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'role_id',
        'is_active',
        'email_verified',
        'email_verified_at',
    ];

    /**
     * Знайти користувача за email
     * 
     * @param string $email
     * @return array|null
     */
    public static function findByEmail($email)
    {
        $result = self::query(
            "SELECT u.*, r.slug as role FROM users u 
             LEFT JOIN user_roles r ON u.role_id = r.id 
             WHERE u.email = ?",
            [$email]
        );
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Знайти користувача за ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function findById($id)
    {
        $result = self::query(
            "SELECT u.*, r.slug as role FROM users u 
             LEFT JOIN user_roles r ON u.role_id = r.id 
             WHERE u.id = ?",
            [$id]
        );
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Отримати всіх користувачів
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAll($limit = 50, $offset = 0)
    {
        return self::query(
            "SELECT u.*, r.name as role_name FROM users u 
             LEFT JOIN user_roles r ON u.role_id = r.id 
             ORDER BY u.created_at DESC 
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Отримати всіх користувачів для адмін-панелі
     *
     * @return array
     */
    public static function getAllForAdmin()
    {
        return self::query(
            "SELECT u.id, u.email, u.phone, u.created_at, u.role_id, r.name AS role_name
             FROM users u
             LEFT JOIN user_roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC"
        );
    }

    /**
     * Отримати список ролей
     *
     * @return array
     */
    public static function getRoles()
    {
        return self::query(
            "SELECT id, name, slug
             FROM user_roles
             ORDER BY id ASC"
        );
    }

    /**
     * Перевірити наявність ролі
     *
     * @param int $roleId
     * @return bool
     */
    public static function roleExists($roleId)
    {
        $result = self::query(
            "SELECT id FROM user_roles WHERE id = ? LIMIT 1",
            [(int) $roleId]
        );

        return !empty($result);
    }

    /**
     * Отримати кількість користувачів
     * 
     * @return int
     */
    public static function count()
    {
        $result = self::query("SELECT COUNT(*) as count FROM users");
        return !empty($result) ? $result[0]['count'] : 0;
    }

    /**
     * Перевірити пароль
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Хешувати пароль
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Створити користувача
     * 
     * @param array $data
     * @return int|false
     */
    public static function create($data)
    {
        // Якщо це перший користувач, призначити роль Адміна (role_id = 1)
        $count = self::count();
        if ($count === 0) {
            $data['role_id'] = 1; // Адмін
        } else {
            $data['role_id'] = $data['role_id'] ?? 3; // За замовчуванням Покупець
        }

        // Хешувати пароль
        if (isset($data['password'])) {
            $data['password'] = self::hashPassword($data['password']);
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        $values = array_values($data);

        $sql = "INSERT INTO users (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        return self::execute($sql, $values);
    }

    /**
     * Оновити користувача
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data)
    {
        // Не дозволяти змінювати пароль через цей метод
        unset($data['password']);

        if (empty($data)) {
            return false;
        }

        $updates = [];
        $values = [];
        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        return self::execute($sql, $values);
    }

    /**
     * Оновити пароль користувача
     * 
     * @param int $id
     * @param string $password
     * @return bool
     */
    public static function updatePassword($id, $password)
    {
        $hashed = self::hashPassword($password);
        
        return self::execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashed, $id]
        );
    }

    /**
     * Видалити користувача
     * 
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        return self::execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    /**
     * Отримати користувачів за роллю
     * 
     * @param string $role
     * @return array
     */
    public static function getByRole($role)
    {
        return self::query(
            "SELECT u.*, r.slug as role FROM users u 
             LEFT JOIN user_roles r ON u.role_id = r.id 
             WHERE r.slug = ?",
            [$role]
        );
    }

    /**
     * Встановити токен для відновлення пароля
     * 
     * @param int $id
     * @param string $token
     * @param int $expiresIn Час дійсності в секундах (за замовчуванням 1 година)
     * @return bool
     */
    public static function setPasswordResetToken($id, $token, $expiresIn = 3600)
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        
        return self::execute(
            "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?",
            [$token, $expiresAt, $id]
        );
    }

    /**
     * Перевірити токен для відновлення пароля
     * 
     * @param string $token
     * @return array|null
     */
    public static function verifyPasswordResetToken($token)
    {
        $result = self::query(
            "SELECT * FROM users 
             WHERE password_reset_token = ? 
             AND password_reset_expires > NOW()",
            [$token]
        );
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Очистити токен для відновлення пароля
     * 
     * @param int $id
     * @return bool
     */
    public static function clearPasswordResetToken($id)
    {
        return self::execute(
            "UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?",
            [$id]
        );
    }

    /**
     * Оновити час останнього входу
     * 
     * @param int $id
     * @return bool
     */
    public static function updateLastLogin($id)
    {
        return self::execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$id]
        );
    }

    /**
     * Активувати/Деактивувати користувача
     * 
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public static function setActive($id, $active = true)
    {
        return self::execute(
            "UPDATE users SET is_active = ? WHERE id = ?",
            [$active ? 1 : 0, $id]
        );
    }

    /**
     * Встановити токен запам'ятовування
     * 
     * @param int $id
     * @param string $token
     * @return bool
     */
    public static function setRememberToken($id, $token)
    {
        return self::execute(
            "UPDATE users SET remember_token = ? WHERE id = ?",
            [$token, $id]
        );
    }

    /**
     * Перевірити токен запам'ятовування
     * 
     * @param int $id
     * @param string $token
     * @return array|null
     */
    public static function verifyRememberToken($id, $token)
    {
        $result = self::query(
            "SELECT u.*, r.slug as role FROM users u 
             LEFT JOIN user_roles r ON u.role_id = r.id 
             WHERE u.id = ? AND u.remember_token = ?",
            [$id, $token]
        );
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Очистити токен запам'ятовування
     * 
     * @param int $id
     * @return bool
     */
    public static function clearRememberToken($id)
    {
        return self::execute(
            "UPDATE users SET remember_token = NULL WHERE id = ?",
            [$id]
        );
    }
}
