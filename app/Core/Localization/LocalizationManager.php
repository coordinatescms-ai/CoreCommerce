<?php

namespace App\Core\Localization;

class LocalizationManager
{
    /**
     * Список підтримуваних мов
     * 
     * @var array
     */
    private static $supported_languages = ['ua', 'en'];
    
    /**
     * Мова за замовчуванням
     * 
     * @var string
     */
    private static $default_language = 'ua';
    
    /**
     * Кешовані переклади
     * 
     * @var array
     */
    private static $translations = [];
    
    /**
     * Отримати поточну мову
     * 
     * @return string
     */
    public static function getCurrentLanguage()
    {
        // Спочатку перевіряємо сесію
        if (!empty($_SESSION['lang'])) {
            return $_SESSION['lang'];
        }
        
        // Потім перевіряємо кукі
        if (!empty($_COOKIE['lang'])) {
            $lang = $_COOKIE['lang'];
            if (self::isLanguageSupported($lang)) {
                $_SESSION['lang'] = $lang;
                return $lang;
            }
        }
        
        // Перевіряємо HTTP Accept-Language заголовок
        $lang = self::parseAcceptLanguage();
        if ($lang) {
            $_SESSION['lang'] = $lang;
            return $lang;
        }
        
        // За замовчуванням повертаємо українську
        $_SESSION['lang'] = self::$default_language;
        return self::$default_language;
    }
    
    /**
     * Встановити мову
     * 
     * @param string $lang Код мови
     * @return bool
     */
    public static function setLanguage($lang)
    {
        if (!self::isLanguageSupported($lang)) {
            return false;
        }
        
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/', '', false, true);
        
        return true;
    }
    
    /**
     * Перевірити, чи мова підтримується
     * 
     * @param string $lang Код мови
     * @return bool
     */
    public static function isLanguageSupported($lang)
    {
        return in_array($lang, self::$supported_languages);
    }
    
    /**
     * Отримати список підтримуваних мов
     * 
     * @return array
     */
    public static function getSupportedLanguages()
    {
        return self::$supported_languages;
    }
    
    /**
     * Отримати переклад
     * 
     * @param string $key Ключ перекладу
     * @param string $lang Код мови (опціонально)
     * @return string
     */
    public static function translate($key, $lang = null)
    {
        if ($lang === null) {
            $lang = self::getCurrentLanguage();
        }
        
        // Завантажити переклади для мови, якщо вони ще не кешовані
        if (!isset(self::$translations[$lang])) {
            self::loadTranslations($lang);
        }
        
        // Повернути переклад або сам ключ, якщо переклад не знайдено
        return self::$translations[$lang][$key] ?? $key;
    }
    
    /**
     * Завантажити переклади для мови
     * 
     * @param string $lang Код мови
     * @return void
     */
    private static function loadTranslations($lang)
    {
        $lang_file = __DIR__ . '/../../../lang/' . $lang . '.php';
        
        if (file_exists($lang_file)) {
            self::$translations[$lang] = require $lang_file;
        } else {
            self::$translations[$lang] = [];
        }
    }
    
    /**
     * Розпарсити HTTP Accept-Language заголовок
     * 
     * @return string|null
     */
    private static function parseAcceptLanguage()
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // Розпарсити Accept-Language заголовок
        $languages = [];
        foreach (explode(',', $accept_language) as $lang_range) {
            $parts = explode(';', $lang_range);
            $lang = trim($parts[0]);
            $quality = isset($parts[1]) ? (float)str_replace('q=', '', trim($parts[1])) : 1.0;
            
            // Отримати основний код мови (наприклад, 'uk' з 'uk-UA')
            $lang_code = explode('-', $lang)[0];
            
            $languages[$lang_code] = $quality;
        }
        
        // Сортувати за якістю (найвища спочатку)
        arsort($languages);
        
        // Знайти першу підтримувану мову
        foreach ($languages as $lang => $quality) {
            // Перевірити точне збігання
            if (self::isLanguageSupported($lang)) {
                return $lang;
            }
            
            // Перевірити розширену мову (наприклад, uk-UA -> ua)
            if ($lang === 'uk' && self::isLanguageSupported('ua')) {
                return 'ua';
            }
            if ($lang === 'en' && self::isLanguageSupported('en')) {
                return 'en';
            }
        }
        
        return null;
    }
}
