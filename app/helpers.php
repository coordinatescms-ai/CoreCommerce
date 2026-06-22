<?php
use App\Core\Localization\LocalizationManager;
use App\Models\Setting;

/**
 * Отримати переклад.
 *
 * @param  string $key      'my_key' або 'namespace::my_key'
 * @param  array  $replace  Параметри підстановки: ['name' => 'Іван'] → :name = Іван
 */
function __(string $key, array $replace = []): string
{
    return LocalizationManager::translate($key, $replace);
}

/**
 * Псевдонім __() для зручності.
 */
function trans(string $key, array $replace = []): string
{
    return LocalizationManager::translate($key, $replace);
}

/**
 * Отримати поточну мову
 */
function get_current_language()
{
    return LocalizationManager::getCurrentLanguage();
}

/**
 * Отримати список підтримуваних мов
 */
function get_supported_languages()
{
    return LocalizationManager::getSupportedLanguages();
}

function get_setting($key, $default = null)
{
    return Setting::get($key, $default);
}

/**
 * Відформатувати ціну з символом активної валюти.
 * Використовується на всіх сторінках замість захардкодених "грн" / "₴".
 *
 * @param  float|int|string $amount
 * @param  int              $decimals
 * @return string   наприклад "1 250,00 $"
 */
function format_price($amount, int $decimals = 2): string
{
    static $symbol = null;
    if ($symbol === null) {
        // Кешуємо на час запиту — один запит до БД
        $row = \App\Core\Database\DB::query(
            'SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1'
        )->fetch(\PDO::FETCH_ASSOC);
        $symbol = $row ? $row['symbol'] : '₴';
    }
    return number_format((float)$amount, $decimals, ',', ' ') . ' ' . $symbol;
}

function product_image_variant_path(?string $path, string $variant = 'original'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    $allowedVariants = ['original', 'medium', 'thumb'];
    if (!in_array($variant, $allowedVariants, true)) {
        $variant = 'original';
    }

    if (strpos($path, '/uploads/products/gallery/') !== 0) {
        return $path;
    }

    $normalized = preg_replace('#^/uploads/products/gallery/(original|medium|thumb)/#', '/uploads/products/gallery/' . $variant . '/', $path);
    return is_string($normalized) ? $normalized : $path;
}


function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
{
    \App\Core\Plugin\PluginManager::getInstance()->addAction($hook, $callback, $priority, $acceptedArgs);
}

function do_action(string $hook, mixed ...$args): void
{
    \App\Core\Plugin\PluginManager::getInstance()->doAction($hook, ...$args);
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
{
    \App\Core\Plugin\PluginManager::getInstance()->addFilter($hook, $callback, $priority, $acceptedArgs);
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    return \App\Core\Plugin\PluginManager::getInstance()->applyFilters($hook, $value, ...$args);
}

function normalize_phone_mask(string $mask): string
{
    return preg_replace('/\s+/', ' ', trim($mask)) ?? '';
}

function is_valid_phone_mask(string $mask): bool
{
    if ($mask === '' || mb_strlen($mask) > 40) {
        return false;
    }

    if (substr_count($mask, '#') < 7) {
        return false;
    }

    return (bool) preg_match('/^[\d\#\+\(\)\-\s]+$/u', $mask);
}

function phone_mask_to_regex(string $mask): string
{
    $escaped = preg_quote($mask, '/');
    return '/^' . str_replace('\\#', '\\d', $escaped) . '$/u';
}

function is_phone_matching_mask(string $phone, string $mask): bool
{
    if (!is_valid_phone_mask($mask)) {
        return false;
    }

    return (bool) preg_match(phone_mask_to_regex($mask), trim($phone));
}

/**
 * Повернути абсолютний шлях до файлу views.
 * Напр.: view_path('components/breadcrumb') → /path/to/resources/views/components/breadcrumb.php
 */
function view_path(string $view): string
{
    return dirname(__DIR__) . '/resources/views/' . ltrim($view, '/') . '.php';
}
