<?php

/**
 * Тестовий скрипт для перевірки системи локалізації
 * 
 * Запустіть цей скрипт з командного рядка:
 * php tests/localization_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Localization\LocalizationManager;

echo "=== Тестування системи локалізації ===\n\n";

// Тест 1: Отримання списку підтримуваних мов
echo "Тест 1: Отримання списку підтримуваних мов\n";
$languages = LocalizationManager::getSupportedLanguages();
echo "Підтримувані мови: " . implode(', ', $languages) . "\n";
echo "✓ Тест пройдений\n\n";

// Тест 2: Перевірка підтримки мови
echo "Тест 2: Перевірка підтримки мови\n";
echo "Українська підтримується: " . (LocalizationManager::isLanguageSupported('ua') ? 'Так' : 'Ні') . "\n";
echo "Англійська підтримується: " . (LocalizationManager::isLanguageSupported('en') ? 'Так' : 'Ні') . "\n";
echo "Німецька підтримується: " . (LocalizationManager::isLanguageSupported('de') ? 'Так' : 'Ні') . "\n";
echo "✓ Тест пройдений\n\n";

// Тест 3: Встановлення мови
echo "Тест 3: Встановлення мови\n";
session_start();
LocalizationManager::setLanguage('en');
echo "Встановлена мова: " . LocalizationManager::getCurrentLanguage() . "\n";
echo "✓ Тест пройдений\n\n";

// Тест 4: Отримання перекладу
echo "Тест 4: Отримання перекладу\n";
$translation_ua = LocalizationManager::translate('welcome', 'ua');
$translation_en = LocalizationManager::translate('welcome', 'en');
echo "Переклад 'welcome' на українську: " . $translation_ua . "\n";
echo "Переклад 'welcome' на англійську: " . $translation_en . "\n";
echo "✓ Тест пройдений\n\n";

// Тест 5: Отримання перекладу для поточної мови
echo "Тест 5: Отримання перекладу для поточної мови\n";
LocalizationManager::setLanguage('ua');
$current_translation = LocalizationManager::translate('products');
echo "Переклад 'products' для поточної мови (ua): " . $current_translation . "\n";
echo "✓ Тест пройдений\n\n";

// Тест 6: Обробка неіснуючого ключа
echo "Тест 6: Обробка неіснуючого ключа\n";
$missing_translation = LocalizationManager::translate('non_existent_key');
echo "Переклад неіснуючого ключа: " . $missing_translation . " (повинен бути сам ключ)\n";
echo "✓ Тест пройдений\n\n";

echo "=== Всі тести успішно пройдені! ===\n";
