<?php

namespace App\Middleware;

class AuthMiddleware
{
    /**
     * Перевірити, чи користувач авторизований
     * 
     * @return bool
     */
    public static function handle()
    {
        if (empty($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        return true;
    }

    /**
     * Перевірити, чи користувач має роль адміна
     * 
     * @return bool
     */
    public static function isAdmin()
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        return true;
    }

    /**
     * Перевірити, чи користувач має роль модератора або адміна
     * 
     * @return bool
     */
    public static function isModerator()
    {
        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'moderator'])) {
            header('Location: /login');
            exit;
        }
        return true;
    }

    /**
     * Отримати поточного користувача
     * 
     * @return array|null
     */
    public static function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Перевірити, чи користувач авторизований
     * 
     * @return bool
     */
    public static function isAuthenticated()
    {
        return !empty($_SESSION['user']);
    }

    /**
     * Перевірити, чи користувач має певну роль
     * 
     * @param string $role
     * @return bool
     */
    public static function hasRole($role)
    {
        if (empty($_SESSION['user'])) {
            return false;
        }
        return $_SESSION['user']['role'] === $role;
    }

    /**
     * Перевірити, чи користувач має одну з ролей
     * 
     * @param array $roles
     * @return bool
     */
    public static function hasAnyRole($roles)
    {
        if (empty($_SESSION['user'])) {
            return false;
        }
        return in_array($_SESSION['user']['role'], $roles);
    }
}
