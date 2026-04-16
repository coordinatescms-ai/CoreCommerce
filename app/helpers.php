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
