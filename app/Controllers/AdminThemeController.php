<?php

namespace App\Controllers;

use App\Core\Theme\ThemeManager;
use App\Core\View\View;
use App\Middleware\AuthMiddleware;

class AdminThemeController
{
    /**
     * Показати список тем
     * 
     * @return void
     */
    public function index()
    {
        AuthMiddleware::handle();
        
        $themes = ThemeManager::getAvailableThemes();
        $active_theme = ThemeManager::getActiveTheme();
        
        View::render('admin.themes.index', [
            'themes' => $themes,
            'active_theme' => $active_theme,
        ]);
    }
}
