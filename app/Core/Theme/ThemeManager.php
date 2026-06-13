<?php

namespace App\Core\Theme;

class ThemeManager
{
    private const MAX_UPLOAD_SIZE = 10485760;

    private const ALLOWED_UPLOAD_EXTENSIONS = [
        'css',
        'eot',
        'gif',
        'ico',
        'jpeg',
        'jpg',
        'js',
        'json',
        'md',
        'php',
        'png',
        'ttf',
        'txt',
        'webp',
        'woff',
        'woff2',
    ];

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
        $theme = self::sanitizeThemeId((string) $theme);
        if ($theme === '') {
            return false;
        }

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

        $theme = self::sanitizeThemeId((string) $theme);
        if ($theme === '') {
            $theme = 'default';
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

        $theme = self::sanitizeThemeId((string) $theme);
        if ($theme === '') {
            $theme = 'default';
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

        $theme = self::sanitizeThemeId((string) $theme);
        if ($theme === '') {
            $theme = 'default';
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

        $theme = self::sanitizeThemeId((string) $theme);
        if ($theme === '') {
            return [];
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
        $theme_id = self::sanitizeThemeId((string) $theme_id);
        if ($theme_id === '') {
            return false;
        }

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
        $theme_id = self::sanitizeThemeId((string) $theme_id);
        if ($theme_id === '') {
            return false;
        }

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
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }

        if (($file['size'] ?? 0) > self::MAX_UPLOAD_SIZE) {
            return false;
        }

        $originalName = (string) ($file['name'] ?? '');
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') {
            return false;
        }

        // MIME-валідація через finfo
        $tmpName  = (string) ($file['tmp_name'] ?? '');
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) { finfo_close($finfo); }

        if ($realMime !== 'application/zip' && $realMime !== 'application/x-zip-compressed') {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpName) !== true) {
            return false;
        }

        if (!self::validateZipEntries($zip)) {
            $zip->close();
            return false;
        }

        $extract_root = sys_get_temp_dir() . '/theme_upload_' . bin2hex(random_bytes(8));
        mkdir($extract_root, 0755, true);

        if (!$zip->extractTo($extract_root)) {
            $zip->close();
            self::recursiveRmdir($extract_root);
            return false;
        }

        $zip->close();

        $theme_dir = self::resolveThemeRootDir($extract_root);
        if ($theme_dir === null) {
            self::recursiveRmdir($extract_root);
            return false;
        }

        $theme_config = self::readUploadedThemeConfig($theme_dir);
        if ($theme_config === null) {
            self::recursiveRmdir($extract_root);
            return false;
        }

        $theme_id = self::sanitizeThemeId((string) $theme_config['id']);
        if ($theme_id === '' || $theme_id !== (string) $theme_config['id']) {
            self::recursiveRmdir($extract_root);
            return false;
        }

        $target_dir = self::$themes_dir . '/' . $theme_id;
        if (is_dir($target_dir)) {
            self::recursiveRmdir($extract_root);
            return false;
        }

        if (!is_dir(self::$themes_dir)) {
            mkdir(self::$themes_dir, 0755, true);
        }

        if (!rename($theme_dir, $target_dir)) {
            self::recursiveRmdir($extract_root);
            return false;
        }

        self::recursiveRmdir($extract_root);
        self::$themes_cache = [];
        self::$theme_config = null;

        return $theme_id;
    }

    private static function resolveThemeRootDir(string $extract_root): ?string
    {
        if (is_file($extract_root . '/theme.json')) {
            return $extract_root;
        }

        $dirs = glob($extract_root . '/*', GLOB_ONLYDIR) ?: [];
        $matches = [];
        foreach ($dirs as $dir) {
            if (is_file($dir . '/theme.json')) {
                $matches[] = $dir;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private static function readUploadedThemeConfig(string $theme_dir): ?array
    {
        if (!is_file($theme_dir . '/theme.json') || !is_file($theme_dir . '/layout.php')) {
            return null;
        }

        $config = json_decode((string) file_get_contents($theme_dir . '/theme.json'), true);
        if (!is_array($config)) {
            return null;
        }

        foreach (['id', 'name', 'version'] as $field) {
            if (empty($config[$field]) || !is_string($config[$field])) {
                return null;
            }
        }

        if (!preg_match('/^[0-9A-Za-z._-]+$/', $config['version'])) {
            return null;
        }

        return $config;
    }

    private static function validateZipEntries(\ZipArchive $zip): bool
    {
        if ($zip->numFiles < 1) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name)) {
                return false;
            }

            $is_directory = str_ends_with(str_replace('\\', '/', $name), '/');
            $path = self::normalizeArchivePath($name);
            if ($path === null) {
                return false;
            }

            if (!$is_directory && !self::hasAllowedUploadExtension($path)) {
                return false;
            }
        }

        return true;
    }

    private static function normalizeArchivePath(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $path = trim($path);

        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path)) {
            return null;
        }

        $path = rtrim($path, '/');
        if ($path === '') {
            return null;
        }

        $parts = explode('/', $path);
        foreach ($parts as $part) {
            if ($part === '..' || $part === '') {
                return null;
            }
        }

        return $path;
    }

    private static function hasAllowedUploadExtension(string $path): bool
    {
        $basename = basename($path);
        if ($basename === '' || str_starts_with($basename, '.')) {
            return false;
        }

        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        return $extension !== '' && in_array($extension, self::ALLOWED_UPLOAD_EXTENSIONS, true);
    }

    private static function sanitizeThemeId(string $theme_id): string
    {
        $theme_id = strtolower(trim($theme_id));
        $theme_id = preg_replace('/[^a-z0-9_-]/', '-', $theme_id) ?? '';
        $theme_id = preg_replace('/-+/', '-', $theme_id) ?? '';
        return trim($theme_id, '-_');
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
