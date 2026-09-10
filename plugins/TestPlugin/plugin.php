<?php

use App\Core\Plugin\PluginInterface;
use App\Core\Plugin\PluginManager;

return new class implements PluginInterface {

    public function getName(): string
    {
        return 'TestPlugin';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function register(PluginManager $pluginManager): void
    {
        $pluginManager->addAction('theme.footer', static function () use ($pluginManager): void {
            // Приклад читання налаштувань через PluginDB (пісочниця)
            $db      = $pluginManager->getPluginDB('TestPlugin');
            $showMsg = $db->getSetting('show_footer_message', '0');
            $message = $db->getSetting('footer_message', '');

            if ($showMsg && $message !== '') {
                echo '<!-- TestPlugin: ' . htmlspecialchars($message) . ' -->';
            }
        });
    }

    public function getSettingsSchema(): array
    {
        return [
            'show_footer_message' => [
                'label'    => $this->t('settings_show_footer_message_label', 'Показувати повідомлення у футері'),
                'type'     => 'checkbox',
                'default'  => '0',
                'required' => false,
                'hint'     => $this->t('settings_show_footer_message_hint', 'Вивести повідомлення у HTML-коментарі футера сторінки.'),
            ],
            'footer_message' => [
                'label'    => $this->t('settings_footer_message_label', 'Текст повідомлення'),
                'type'     => 'text',
                'default'  => $this->t('footer_message_default', 'Hello from TestPlugin!'),
                'required' => false,
                'hint'     => $this->t('settings_footer_message_hint', 'Цей текст буде виведено у <!-- коментарі --> тега footer.'),
            ],
        ];
    }

    /**
     * Переклад customer/admin-facing текстів (label/hint у getSettingsSchema()).
     * Той самий підхід, що й у LiqPayGateway/StripeGateway.
     */
    private function t(string $key, string $default = ''): string
    {
        static $translations = null;

        if ($translations === null) {
            $lang = $this->getCurrentLanguage();
            $file = __DIR__ . '/lang/' . $lang . '.json';
            $translations = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];

            // Fallback до української якщо файл не знайдено
            if (empty($translations)) {
                $fallbackFile = __DIR__ . '/lang/ua.json';
                $translations = is_file($fallbackFile) ? (json_decode((string) file_get_contents($fallbackFile), true) ?: []) : [];
            }
        }

        return $translations[$key] ?? $default;
    }

    /**
     * Отримати поточну мову з підтримкою всіх налаштованих мов
     */
    private function getCurrentLanguage(): string
    {
        // Пробуємо отримати через LocalizationManager
        if (function_exists('get_current_language')) {
            return get_current_language();
        }

        // Fallback через сесію
        $lang = $_SESSION['lang'] ?? 'ua';

        // Перевіряємо, чи існує файл перекладу для цієї мови
        $supportedLangs = $this->getSupportedLanguages();
        if (!in_array($lang, $supportedLangs)) {
            $lang = 'ua'; // дефолтна мова
        }

        return $lang;
    }

    /**
     * Отримати список підтримуваних мов плагіна
     */
    private function getSupportedLanguages(): array
    {
        $langDir = __DIR__ . '/lang';
        $languages = [];

        if (is_dir($langDir)) {
            foreach (glob($langDir . '/*.json') as $file) {
                $langCode = basename($file, '.json');
                $languages[] = $langCode;
            }
        }

        return $languages ?: ['ua']; // fallback якщо папка порожня
    }
};
