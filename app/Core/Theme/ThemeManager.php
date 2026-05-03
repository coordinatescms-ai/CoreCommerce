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
     * Тема для попереднього перегляду
     */
    private static $preview_theme = null;
    
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
        // Пріоритет 1: Тема для попереднього перегляду (тільки для поточного запиту)
        if (self::$preview_theme !== null) {
            return self::$preview_theme;
        }

        // Пріоритет 2: Тема з сесії для тривалого попереднього перегляду
        if (!empty($_SESSION['preview_theme'])) {
            return $_SESSION['preview_theme'];
        }

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
        
        // Очищаємо попередній перегляд при зміні основної теми
        unset($_SESSION['preview_theme']);
        
        $_SESSION['theme'] = $theme;
        setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), '/', '', false, true);
        self::$active_theme = $theme;
        
        // Оновлюємо конфіг
        self::updateThemeConfig($theme);
        
        return true;
    }

    /**
     * Встановити тему для попереднього перегляду
     */
    public static function setPreviewTheme($theme_id)
    {
        if (self::themeExists($theme_id)) {
            $_SESSION['preview_theme'] = $theme_id;
            return true;
        }
        return false;
    }

    /**
     * Скасувати попередній перегляд
     */
    public static function cancelPreview()
    {
        unset($_SESSION['preview_theme']);
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
        
        // Підтримка дочірніх тем
        $info = self::getThemeInfo($theme);
        if (!empty($info['parent']) && $info['parent'] !== $theme) {
            return self::getLayoutPath($info['parent']);
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

        // Підтримка дочірніх тем
        $info = self::getThemeInfo($theme);
        if (!empty($info['parent']) && $info['parent'] !== $theme) {
            return self::getStylePath($info['parent']);
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
     * Оновити theme.json конкретної теми
     * 
     * @param string $theme_id
     * @param array $data
     * @return bool
     */
    public static function updateThemeMetadata($theme_id, array $data)
    {
        $config_file = self::$themes_dir . '/' . $theme_id . '/theme.json';
        if (!file_exists($config_file)) {
            return false;
        }

        $current_config = json_decode(file_get_contents($config_file), true) ?: [];
        $new_config = array_merge($current_config, $data);
        
        return file_put_contents($config_file, json_encode($new_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    /**
     * Видалити тему
     * 
     * @param string $theme_id
     * @return bool
     */
    public static function deleteTheme($theme_id)
    {
        if ($theme_id === 'default' || $theme_id === self::getActiveTheme()) {
            return false;
        }

        $theme_path = self::$themes_dir . '/' . $theme_id;
        if (!is_dir($theme_path)) {
            return false;
        }

        return self::recursiveRmdir($theme_path);
    }

    /**
     * Рекурсивне видалення директорії
     */
    private static function recursiveRmdir($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::recursiveRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Завантажити тему з ZIP архіву
     * 
     * @param array $file Елемент масиву $_FILES
     * @return string|bool Повертає ID теми або false
     */
    public static function uploadTheme($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            return false;
        }

        // Шукаємо theme.json для визначення ID теми
        $theme_id = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (basename($filename) === 'theme.json') {
                $content = $zip->getFromIndex($i);
                $data = json_decode($content, true);
                if (isset($data['id'])) {
                    $theme_id = $data['id'];
                    break;
                }
            }
        }

        if (!$theme_id) {
            // Якщо theme.json не знайдено, використовуємо назву архіву
            $theme_id = str_replace('.zip', '', strtolower(basename($file['name'])));
        }

        $extract_path = self::$themes_dir . '/' . $theme_id;
        if (is_dir($extract_path)) {
            // Тема вже існує - можна або видати помилку, або перезаписати
            // Для безпеки додамо префікс
            $theme_id .= '_' . time();
            $extract_path = self::$themes_dir . '/' . $theme_id;
        }

        mkdir($extract_path, 0755, true);
        
        // Перевіряємо, чи запакована тема в папку всередині архіву
        $first_file = $zip->getNameIndex(0);
        $has_root_dir = (strpos($first_file, '/') !== false);

        if ($has_root_dir) {
            // Складніша логіка розпакування, якщо є коренева папка в ZIP
            $root_dir = explode('/', $first_file)[0];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (strpos($name, $root_dir . '/') === 0) {
                    $zip->extractTo(self::$themes_dir, $name);
                }
            }
            // Перейменовуємо розпаковану папку в наш theme_id
            rename(self::$themes_dir . '/' . $root_dir, $extract_path);
        } else {
            $zip->extractTo($extract_path);
        }

        $zip->close();
        return $theme_id;
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
