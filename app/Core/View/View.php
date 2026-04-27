<?php

namespace App\Core\View;

use App\Models\Category;
use App\Core\Theme\ThemeManager;

class View
{
    /**
     * Рендерити шаблон з відповідним макетом
     * 
     * @param string $view Назва шаблону (наприклад, 'products.index')
     * @param array $data Дані для передачі у шаблон
     * @param string $layout Тип макета: 'theme' (frontend) або 'admin'
     * @return void
     */
    
    public static function render($view, $data = [], $layout = 'theme')
    {
        // Додаємо категорії для хедера, якщо їх немає
        if (!array_key_exists('headerCategories', $data)) {
            $data['headerCategories'] = Category::getTree();
        }

        // ДОДАЄМО СТОРІНКИ ДЛЯ ФУТЕРА
        // Робимо це тільки для 'theme' (frontend), щоб не навантажувати адмінку
        if ($layout === 'theme' && !array_key_exists('footerPages', $data)) {
            $pageModel = new \App\Models\Page();
            $data['footerPages'] = $pageModel->getPublished();
        }

        // Екстрактуємо дані для доступу як змінні
        extract($data);
        
        // Знаходимо файл шаблону
        $view_path = __DIR__ . '/../../../resources/views/' . str_replace('.', '/', $view) . '.php';
        
        // ... (решта вашого коду без змін) ...
        
        if (!file_exists($view_path)) {
            throw new \Exception("View file not found: {$view_path}");
        }
        
        ob_start();
        include $view_path;
        $content = ob_get_clean();
        
        if ($layout === 'admin') {
            $layout_path = __DIR__ . '/../../../resources/views/layouts/admin.php';
        } else {
            $active_theme = ThemeManager::getActiveTheme();
            $layout_path = ThemeManager::getLayoutPath($active_theme);
        }
        
        if (!file_exists($layout_path)) {
            throw new \Exception("Layout file not found: {$layout_path}");
        }
        
        include $layout_path;
    }
    
    public static function renderPartial($view, $data = [])
    {
        extract($data);

        $view_path = __DIR__ . '/../../../resources/views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($view_path)) {
            throw new \Exception("View file not found: {$view_path}");
        }

        include $view_path;
    }

    /**
     * Отримати активну тему
     * 
     * @return string
     */
    public static function getActiveTheme()
    {
        return ThemeManager::getActiveTheme();
    }
    
    /**
     * Отримати шлях до CSS теми
     * 
     * @return string
     */
    public static function getThemeStyle()
    {
        return ThemeManager::getStylePath();
    }
    
    /**
     * Отримати інформацію про активну тему
     * 
     * @return array
     */
    public static function getThemeInfo()
    {
        $active_theme = ThemeManager::getActiveTheme();
        return ThemeManager::getThemeInfo($active_theme);
    }
}
