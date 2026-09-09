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

    /** @var string[]|null null = ще не завантажено з config/languages.php */
    private static ?array $supportedLanguages = null;

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
        // Явний вибір через перемикач діє до завершення поточної сесії —
        // однаково для гостя та авторизованого користувача.
        if (!empty($_SESSION['lang']) && self::isLanguageSupported($_SESSION['lang'])) {
            return $_SESSION['lang'];
        }

        // Для нового гостьового сеансу завжди використовуємо мову магазину,
        // задану адміністратором: не відновлюємо старий cookie і не
        // автовизначаємо мову браузера.
        if (empty($_SESSION['user']['id'])) {
            return self::resolveDefaultLanguage();
        }

        // Далі — запам'ятований вибір з попереднього візиту.
        if (!empty($_COOKIE['lang']) && self::isLanguageSupported($_COOKIE['lang'])) {
            $_SESSION['lang'] = $_COOKIE['lang'];
            return $_COOKIE['lang'];
        }

        // Якщо вибору ще не було, використовуємо мову браузера.
        $browserLanguage = self::parseAcceptLanguage();
        if ($browserLanguage !== null) {
            $_SESSION['lang'] = $browserLanguage;
            return $browserLanguage;
        }

        // Останній fallback для авторизованого користувача — мова магазину.
        $_SESSION['lang'] = self::resolveDefaultLanguage();
        return $_SESSION['lang'];
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
        return in_array($lang, self::loadSupportedLanguages(), true);
    }

    public static function getSupportedLanguages(): array
    {
        return self::loadSupportedLanguages();
    }

    /**
     * Додати нову підтримувану мову динамічно (наприклад, плагін
     * локалізації реєструє мову без редагування config/languages.php).
     */
    public static function addLanguage(string $lang): void
    {
        self::loadSupportedLanguages();
        if (!in_array($lang, self::$supportedLanguages, true)) {
            self::$supportedLanguages[] = $lang;
        }
    }

    /**
     * Код мови => назва мови ЇЇ ЖЕ мовою (для рендеру перемикачів,
     * замінює раніше захардкожені списки в темах/адмінці).
     *
     * @return array<string, string>
     */
    public static function getLanguageNames(): array
    {
        $file = dirname(__DIR__, 3) . '/config/languages.php';
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data)) {
                return $data;
            }
        }
        return array_combine(self::loadSupportedLanguages(), self::loadSupportedLanguages());
    }

    /**
     * @return string[]
     */
    private static function loadSupportedLanguages(): array
    {
        if (self::$supportedLanguages !== null) {
            return self::$supportedLanguages;
        }

        $file = dirname(__DIR__, 3) . '/config/languages.php';
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data) && !empty($data)) {
                self::$supportedLanguages = array_keys($data);
                return self::$supportedLanguages;
            }
        }

        // Файл конфігурації відсутній/порожній (не мало б трапитись) —
        // безпечний хардкод-фолбек, щоб сайт не впав без мов узагалі.
        self::$supportedLanguages = ['ua', 'en'];
        return self::$supportedLanguages;
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

    /**
     * Визначити мову за замовчуванням: спершу з admin-налаштування
     * (settings.default_language, вкладка "Загальні" → "Локалізація"),
     * і лише якщо БД недоступна (напр. під час інсталяції) — хардкод-константа.
     */
    private static function resolveDefaultLanguage(): string
    {
        try {
            $configured = \App\Models\Setting::get('default_language', self::$defaultLanguage);
            if (is_string($configured) && self::isLanguageSupported($configured)) {
                return $configured;
            }
        } catch (\Throwable $e) {
            // БД недоступна або таблиця settings ще не створена — тихо падаємо на хардкод-дефолт.
        }

        return self::$defaultLanguage;
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
            $quality = isset($parts[1]) ? (float) str_replace('q=', '', trim($parts[1])) : 1.0;
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
