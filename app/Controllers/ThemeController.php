<?php

namespace App\Controllers;

use App\Core\Theme\ThemeManager;
use App\Middleware\AuthMiddleware;

/**
 * УВАГА: цей контролер історично не мав жодної перевірки авторизації —
 * будь-який відвідувач (чи пошуковий бот) міг звичайним GET-запитом
 * змінити активну тему для ВСЬОГО сайту через switch()/change().
 * Додано AuthMiddleware::isAdmin() на всі методи (як і в сусідньому,
 * вже захищеному AdminThemeController).
 *
 * Ці маршрути (/themes, /theme/switch/{theme}) ніде в поточному коді не
 * використовуються — керування темами йде через /admin/themes
 * (AdminThemeController, POST + CSRF). Лишаємо цей контролер для
 * зворотної сумісності, але тепер він так само вимагає адмін-сесію.
 */
class ThemeController
{
    /**
     * Отримати список доступних тем
     * 
     * @return void
     */
    public function index()
    {
        AuthMiddleware::isAdmin();

        $themes = ThemeManager::getAvailableThemes();
        $active_theme = ThemeManager::getActiveTheme();
        
        echo json_encode([
            'themes' => $themes,
            'active_theme' => $active_theme,
        ]);
    }
    
    /**
     * Змінити активну тему
     * 
     * @param string $theme Ідентифікатор теми
     * @return void
     */
    public function switch($theme)
    {
        AuthMiddleware::isAdmin();

        if (ThemeManager::setActiveTheme($theme)) {
            // Редирект на попередню сторінку або на головну
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $referer);
            exit;
        } else {
            http_response_code(404);
            die('Theme not found');
        }
    }

    /**
     * Змінити активну тему (API версія)
     * 
     * @param string $theme Ідентифікатор теми
     * @return void
     */
    public function change($theme)
    {
        AuthMiddleware::isAdmin();

        if (ThemeManager::setActiveTheme($theme)) {
            echo json_encode(['success' => true, 'theme' => $theme]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Theme not found']);
        }
    }
    
    /**
     * Отримати інформацію про активну тему
     * 
     * @return void
     */
    public function info()
    {
        AuthMiddleware::isAdmin();

        $active_theme = ThemeManager::getActiveTheme();
        $theme_info = ThemeManager::getThemeInfo($active_theme);
        
        echo json_encode($theme_info);
    }
}
