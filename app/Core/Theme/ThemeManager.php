<?php

namespace App\Core\Theme;

class ThemeManager
{
    /**
     * Директорія з темами
     */
    private static $themes_dir = __DIR__ . '/../../../resources/themes';
    
    /**
     * Активна тема
     */
    private static $active_theme = null;
    
    /**
     * Кешовані теми
     */
    private static $themes_cache = [];
    
    /**
     * Конфіг активної теми
     */
    private static $theme_config = null;
    
    /**
     * Отримати активну тему
     * 
     * @return string
     */
    public static function getActiveTheme()
    {
        if (self::$active_theme !== null) {
            return self::$active_theme;
        }
        
        // Спочатку перевіряємо сесію
        if (!empty($_SESSION['theme'])) {
            $theme = $_SESSION['theme'];
            if (self::themeExists($theme)) {
                self::$active_theme = $theme;
                return $theme;
            }
        }
        
        // Потім перевіряємо кукі
        if (!empty($_COOKIE['theme'])) {
            $theme = $_COOKIE['theme'];
            if (self::themeExists($theme)) {
                $_SESSION['theme'] = $theme;
                self::$active_theme = $theme;
                return $theme;
            }
        }
        
        // Завантажуємо з конфігурації
        $config = require __DIR__ . '/../../../config/theme.php';
        if (!empty($config['active']) && self::themeExists($config['active'])) {
            $_SESSION['theme'] = $config['active'];
            self::$active_theme = $config['active'];
            return $config['active'];
        }
        
        // За замовчуванням повертаємо першу доступну тему
        $themes = self::getAvailableThemes();
        if (!empty($themes)) {
            $default_theme = reset($themes);
            $_SESSION['theme'] = $default_theme['id'];
            self::$active_theme = $default_theme['id'];
            return $default_theme['id'];
        }
        
        // Якщо немає тем, повертаємо 'default'
        self::$active_theme = 'default';
        return 'default';
    }
    
    /**
     * Встановити активну тему
     * 
     * @param string $theme Ідентифікатор теми
     * @return bool
     */
    public static function setActiveTheme($theme)
    {
        if (!self::themeExists($theme)) {
            return false;
        }
        
        $_SESSION['theme'] = $theme;
        setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), '/', '', false, true);
        self::$active_theme = $theme;
        
        // Оновлюємо конфіг
        self::updateThemeConfig($theme);
        
        return true;
    }
    
    /**
     * Отримати список доступних тем
     * 
     * @return array
     */
    public static function getAvailableThemes()
    {
        if (!empty(self::$themes_cache)) {
            return self::$themes_cache;
        }
        
        $themes = [];
        
        if (!is_dir(self::$themes_dir)) {
            return $themes;
        }
        
        $theme_dirs = array_diff(scandir(self::$themes_dir), ['.', '..']);
        
        foreach ($theme_dirs as $theme_dir) {
            $theme_path = self::$themes_dir . '/' . $theme_dir;
            
            if (is_dir($theme_path)) {
                $config_file = $theme_path . '/theme.json';
                
                if (file_exists($config_file)) {
                    $config = json_decode(file_get_contents($config_file), true);
                    
                    if ($config && isset($config['id'])) {
                        $themes[] = [
                            'id' => $config['id'],
                            'name' => $config['name'] ?? $config['id'],
                            'description' => $config['description'] ?? '',
                            'version' => $config['version'] ?? '1.0.0',
                            'author' => $config['author'] ?? 'Unknown',
                            'path' => $theme_path,
                        ];
                    }
                } else {
                    // Якщо немає theme.json, створюємо базову конфіг
                    $themes[] = [
                        'id' => $theme_dir,
                        'name' => ucfirst($theme_dir),
                        'description' => 'Theme: ' . ucfirst($theme_dir),
                        'version' => '1.0.0',
                        'author' => 'Unknown',
                        'path' => $theme_path,
                    ];
                }
            }
        }
        
        self::$themes_cache = $themes;
        return $themes;
    }
    
    /**
     * Перевірити, чи існує тема
     * 
     * @param string $theme Ідентифікатор теми
     * @return bool
     */
    public static function themeExists($theme)
    {
        $theme_path = self::$themes_dir . '/' . $theme;
        return is_dir($theme_path);
    }
    
    /**
     * Отримати інформацію про тему
     * 
     * @param string $theme Ідентифікатор теми
     * @return array|null
     */
    public static function getThemeInfo($theme)
    {
        $themes = self::getAvailableThemes();
        
        foreach ($themes as $theme_info) {
            if ($theme_info['id'] === $theme) {
                return $theme_info;
            }
        }
        
        return null;
    }
    
    /**
     * Отримати шлях до файлу макета теми
     * 
     * @param string $theme Ідентифікатор теми (опціонально)
     * @return string
     */
    public static function getLayoutPath($theme = null)
    {
        if ($theme === null) {
            $theme = self::getActiveTheme();
        }
        
        $layout_path = self::$themes_dir . '/' . $theme . '/layout.php';
        
        if (file_exists($layout_path)) {
            return $layout_path;
        }
        
        // Якщо файл не існує, повертаємо шлях до default теми
        return self::$themes_dir . '/default/layout.php';
    }
    
    /**
     * Отримати шлях до директорії теми
     * 
     * @param string $theme Ідентифікатор теми (опціонально)
     * @return string
     */
    public static function getThemePath($theme = null)
    {
        if ($theme === null) {
            $theme = self::getActiveTheme();
        }
        
        return self::$themes_dir . '/' . $theme;
    }
    
    /**
     * Отримати шлях до CSS файлу теми
     * 
     * @param string $theme Ідентифікатор теми (опціонально)
     * @return string
     */
    public static function getStylePath($theme = null)
    {
        if ($theme === null) {
            $theme = self::getActiveTheme();
        }
        
        $style_path = self::$themes_dir . '/' . $theme . '/style.css';
        
        if (file_exists($style_path)) {
            return '/resources/themes/' . $theme . '/style.css';
        }
        
        return '/resources/themes/default/style.css';
    }
    
    /**
     * Отримати конфіг теми
     * 
     * @param string $theme Ідентифікатор теми (опціонально)
     * @return array
     */
    public static function getThemeConfig($theme = null)
    {
        if ($theme === null) {
            $theme = self::getActiveTheme();
        }
        
        if (self::$theme_config !== null && isset(self::$theme_config[$theme])) {
            return self::$theme_config[$theme];
        }
        
        $config_file = self::$themes_dir . '/' . $theme . '/theme.json';
        
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            
            if (self::$theme_config === null) {
                self::$theme_config = [];
            }
            
            self::$theme_config[$theme] = $config ?? [];
            return self::$theme_config[$theme];
        }
        
        return [];
    }
    
    /**
     * Оновити конфіг теми
     * 
     * @param string $theme Ідентифікатор теми
     * @return bool
     */
    private static function updateThemeConfig($theme)
    {
        $config_file = __DIR__ . '/../../../config/theme.php';
        $config = ['active' => $theme];
        
        $content = '<?php return ' . var_export($config, true) . ';';
        
        return file_put_contents($config_file, $content) !== false;
    }
    
    /**
     * Отримати змінні кольорів теми
     * 
     * @param string $theme Ідентифікатор теми (опціонально)
     * @return array
     */
    public static function getThemeColors($theme = null)
    {
        if ($theme === null) {
            $theme = self::getActiveTheme();
        }
        
        $config = self::getThemeConfig($theme);
        return $config['colors'] ?? [];
    }
    
    /**
     * Отримати змінні шрифтів теми
     * 
     * @param string $theme Ідентифікатор теми (опціонально)
     * @return array
     */
    public static function getThemeFonts($theme = null)
    {
        if ($theme === null) {
            $theme = self::getActiveTheme();
        }
        
        $config = self::getThemeConfig($theme);
        return $config['fonts'] ?? [];
    }
}
