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
