<?php

namespace App\Core\Localization;

/**
 * Менеджер локалізації з підтримкою namespace перекладів.
 *
 * Namespace дозволяє плагінам та модулям мати власні файли перекладів
 * без конфліктів з ядром.
 *
 * Формат ключа: 'namespace::key' або просто 'key' (namespace = 'core')
 *
 * Приклади:
 *   __('add_to_cart')               → core namespace (lang/ua.php)
 *   __('liqpay::payment_error')     → lang/ua/liqpay.php або plugins/LiqPayGateway/lang/ua.php
 *   __('prom::sync_done')           → lang/ua/prom.php
 *
 * Реєстрація namespace плагіном:
 *   LocalizationManager::registerNamespace('liqpay', '/path/to/plugin/lang');
 */
class LocalizationManager
{
    private const CORE_NAMESPACE = 'core';
    private const NS_SEPARATOR   = '::';

    /** @var string[] */
    private static array $supportedLanguages = ['ua', 'en'];

    private static string $defaultLanguage = 'ua';

    /**
     * Завантажені переклади: [lang][namespace][key] = value
     * @var array<string, array<string, array<string, string>>>
     */
    private static array $translations = [];

    /**
     * Зареєстровані namespace -> шлях до папки з lang-файлами
     * @var array<string, string>
     */
    private static array $namespaces = [];

    // ── Публічне API ─────────────────────────────────────────────────────────

    /**
     * Отримати переклад.
     *
     * @param  string      $key   'my_key' або 'namespace::my_key'
     * @param  array       $replace  Параметри для підстановки: ['name' => 'Іван']
     * @param  string|null $lang
     */
    public static function translate(string $key, array $replace = [], ?string $lang = null): string
    {
        $lang = $lang ?? self::getCurrentLanguage();

        [$namespace, $realKey] = self::parseKey($key);

        // Завантажуємо якщо ще не в кеші
        if (!isset(self::$translations[$lang][$namespace])) {
            self::loadNamespace($lang, $namespace);
        }

        $value = self::$translations[$lang][$namespace][$realKey]
            ?? self::$translations[self::$defaultLanguage][$namespace][$realKey]
            ?? $key;

        // Підстановка параметрів: :name → значення
        if (!empty($replace)) {
            foreach ($replace as $param => $val) {
                $value = str_replace(':' . $param, (string)$val, $value);
            }
        }

        return $value;
    }

    /**
     * Зареєструвати власний namespace для плагіна або модуля.
     *
     * @param string $namespace  Наприклад: 'liqpay'
     * @param string $langPath   Абсолютний шлях до папки з lang-файлами
     *                           Файли мають бути: {langPath}/ua.php, {langPath}/en.php
     *                           або {langPath}/ua/namespace.php
     */
    public static function registerNamespace(string $namespace, string $langPath): void
    {
        self::$namespaces[$namespace] = rtrim($langPath, '/');
        // Скидаємо кеш для цього namespace якщо вже завантажено
        foreach (array_keys(self::$translations) as $lang) {
            unset(self::$translations[$lang][$namespace]);
        }
    }

    /**
     * Перевірити чи існує ключ перекладу.
     */
    public static function has(string $key, ?string $lang = null): bool
    {
        $lang = $lang ?? self::getCurrentLanguage();
        [$namespace, $realKey] = self::parseKey($key);

        if (!isset(self::$translations[$lang][$namespace])) {
            self::loadNamespace($lang, $namespace);
        }

        return isset(self::$translations[$lang][$namespace][$realKey]);
    }

    /**
     * Отримати всі переклади для namespace.
     *
     * @return array<string, string>
     */
    public static function getNamespace(string $namespace, ?string $lang = null): array
    {
        $lang = $lang ?? self::getCurrentLanguage();

        if (!isset(self::$translations[$lang][$namespace])) {
            self::loadNamespace($lang, $namespace);
        }

        return self::$translations[$lang][$namespace] ?? [];
    }

    // ── Мова ─────────────────────────────────────────────────────────────────

    public static function getCurrentLanguage(): string
    {
        if (!empty($_SESSION['lang']) && self::isLanguageSupported($_SESSION['lang'])) {
            return $_SESSION['lang'];
        }

        if (!empty($_COOKIE['lang']) && self::isLanguageSupported($_COOKIE['lang'])) {
            $_SESSION['lang'] = $_COOKIE['lang'];
            return $_COOKIE['lang'];
        }

        $lang = self::parseAcceptLanguage();
        if ($lang) {
            $_SESSION['lang'] = $lang;
            return $lang;
        }

        $_SESSION['lang'] = self::$defaultLanguage;
        return self::$defaultLanguage;
    }

    public static function setLanguage(string $lang): bool
    {
        if (!self::isLanguageSupported($lang)) {
            return false;
        }
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + 365 * 24 * 3600, '/', '', false, true);
        return true;
    }

    public static function isLanguageSupported(string $lang): bool
    {
        return in_array($lang, self::$supportedLanguages, true);
    }

    public static function getSupportedLanguages(): array
    {
        return self::$supportedLanguages;
    }

    /**
     * Додати нову підтримувану мову (наприклад плагін локалізації).
     */
    public static function addLanguage(string $lang): void
    {
        if (!in_array($lang, self::$supportedLanguages, true)) {
            self::$supportedLanguages[] = $lang;
        }
    }

    // ── Завантаження перекладів ───────────────────────────────────────────────

    private static function parseKey(string $key): array
    {
        if (str_contains($key, self::NS_SEPARATOR)) {
            [$namespace, $realKey] = explode(self::NS_SEPARATOR, $key, 2);
            return [$namespace, $realKey];
        }
        return [self::CORE_NAMESPACE, $key];
    }

    private static function loadNamespace(string $lang, string $namespace): void
    {
        self::$translations[$lang][$namespace] = [];

        if ($namespace === self::CORE_NAMESPACE) {
            self::loadCoreTranslations($lang);
            return;
        }

        // Шукаємо зареєстрований namespace
        if (isset(self::$namespaces[$namespace])) {
            $path = self::$namespaces[$namespace];

            // Варіант 1: {langPath}/ua.php
            $file1 = $path . '/' . $lang . '.php';
            // Варіант 2: {langPath}/ua/namespace.php (підпапка per-namespace)
            $file2 = $path . '/' . $lang . '/' . $namespace . '.php';

            $file = is_file($file1) ? $file1 : (is_file($file2) ? $file2 : null);

            if ($file) {
                $data = require $file;
                if (is_array($data)) {
                    self::$translations[$lang][$namespace] = $data;
                }
            }
            return;
        }

        // Fallback: шукаємо lang/{lang}/{namespace}.php у папці ядра
        $fallbackFile = dirname(__DIR__, 3) . '/lang/' . $lang . '/' . $namespace . '.php';
        if (is_file($fallbackFile)) {
            $data = require $fallbackFile;
            if (is_array($data)) {
                self::$translations[$lang][$namespace] = $data;
            }
        }
    }

    private static function loadCoreTranslations(string $lang): void
    {
        // Основний файл: lang/ua.php
        $mainFile = dirname(__DIR__, 3) . '/lang/' . $lang . '.php';
        if (is_file($mainFile)) {
            $data = require $mainFile;
            if (is_array($data)) {
                self::$translations[$lang][self::CORE_NAMESPACE] = $data;
            }
        }

        // Додаткові файли з підпапки: lang/ua/*.php (merge)
        $subDir = dirname(__DIR__, 3) . '/lang/' . $lang;
        if (is_dir($subDir)) {
            foreach (glob($subDir . '/*.php') ?: [] as $file) {
                $data = require $file;
                if (is_array($data)) {
                    self::$translations[$lang][self::CORE_NAMESPACE] = array_merge(
                        self::$translations[$lang][self::CORE_NAMESPACE] ?? [],
                        $data
                    );
                }
            }
        }
    }

    private static function parseAcceptLanguage(): ?string
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $languages = [];
        foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
            $parts   = explode(';', $part);
            $code    = trim($parts[0]);
            $quality = isset($parts[1]) ? (float)str_replace('q=', '', trim($parts[1])) : 1.0;
            $primary = explode('-', $code)[0];
            $languages[$primary] = max($languages[$primary] ?? 0, $quality);
        }

        arsort($languages);

        foreach (array_keys($languages) as $lang) {
            if (self::isLanguageSupported($lang)) {
                return $lang;
            }
            if ($lang === 'uk' && self::isLanguageSupported('ua')) {
                return 'ua';
            }
        }

        return null;
    }
}
