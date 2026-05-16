<?php
use App\Core\Localization\LocalizationManager;
use App\Models\Setting;

function __($key)
{
    return LocalizationManager::translate($key);
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
