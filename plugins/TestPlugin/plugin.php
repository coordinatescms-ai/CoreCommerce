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
            $lang = function_exists('get_current_language') ? get_current_language() : 'ua';
            $file = __DIR__ . '/lang/' . ($lang === 'en' ? 'en' : 'ua') . '.json';
            $translations = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }

        return $translations[$key] ?? $default;
    }
};
