<?php

namespace App\Controllers;

use App\Core\Theme\ThemeManager;
use App\Core\View\View;

class PublicThemeSwitcherController
{
    /**
     * Показати сторінку для вибору теми
     * 
     * @return void
     */
    public function show()
    {
        $themes = ThemeManager::getAvailableThemes();
        $active_theme = ThemeManager::getActiveTheme();
        
        View::render('theme_switcher', [
            'themes' => $themes,
            'active_theme' => $active_theme,
        ]);
    }
}
