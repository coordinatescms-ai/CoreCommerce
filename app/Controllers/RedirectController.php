<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Services\SlugHelper;

class RedirectController
{
    /**
     * Обробити редирект на основі старого slug
     * 
     * @param string $oldSlug
     * @param string $entityType
     */
    public static function handleRedirect($oldSlug, $entityType)
    {
        // Отримати редирект
        $redirect = SlugHelper::getRedirect($oldSlug, $entityType);
        
        if (!$redirect) {
            // Редирект не знайдено, показати 404
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Виконати 301 редирект
        $newUrl = self::buildUrl($redirect['new_slug'], $entityType);
        
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $newUrl);
        exit;
    }

    /**
     * Побудувати URL на основі slug та типу сутності
     * 
     * @param string $slug
     * @param string $entityType
     * @return string
     */
    private static function buildUrl($slug, $entityType)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        switch ($entityType) {
            case 'product':
                return $protocol . '://' . $host . '/product/' . $slug;
            case 'category':
                return $protocol . '://' . $host . '/category/' . $slug;
            case 'page':
                return $protocol . '://' . $host . '/page/' . $slug;
            default:
                return $protocol . '://' . $host . '/';
        }
    }

    /**
     * Показати список активних редиректів (для адміна)
     */
    public function index()
    {
        // Перевірити авторизацію адміна
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $redirects = $db->query("SELECT * FROM url_redirects WHERE is_active = 1 ORDER BY created_at DESC");

        View::render('admin/redirects/index', [
            'redirects' => $redirects ?? []
        ], 'admin');
    }

    /**
     * Деактивувати редирект
     * 
     * @param int $id
     */
    public function deactivate($id)
    {
        // Перевірити авторизацію адміна
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $result = $db->execute(
            "UPDATE url_redirects SET is_active = 0 WHERE id = ?",
            [$id]
        );

        if ($result) {
            $_SESSION['success'] = 'Редирект деактивовано';
        } else {
            $_SESSION['error'] = 'Помилка при деактивації редиректу';
        }

        header('Location: /admin/redirects');
        exit;
    }

    /**
     * Видалити редирект
     * 
     * @param int $id
     */
    public function delete($id)
    {
        // Перевірити авторизацію адміна
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $result = $db->execute(
            "DELETE FROM url_redirects WHERE id = ?",
            [$id]
        );

        if ($result) {
            $_SESSION['success'] = 'Редирект видалено';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні редиректу';
        }

        header('Location: /admin/redirects');
        exit;
    }
}
