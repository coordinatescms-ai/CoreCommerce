<?php

namespace App\Controllers;

use App\Core\Theme\ThemeManager;

class ThemeController
{
    /**
     * Отримати список доступних тем
     * 
     * @return void
     */
    public function index()
    {
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
        $active_theme = ThemeManager::getActiveTheme();
        $theme_info = ThemeManager::getThemeInfo($active_theme);
        
        echo json_encode($theme_info);
    }
}
