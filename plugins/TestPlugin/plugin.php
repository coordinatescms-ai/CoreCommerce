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
                'label'    => 'Показувати повідомлення у футері',
                'type'     => 'checkbox',
                'default'  => '0',
                'required' => false,
                'hint'     => 'Вивести повідомлення у HTML-коментарі футера сторінки.',
            ],
            'footer_message' => [
                'label'    => 'Текст повідомлення',
                'type'     => 'text',
                'default'  => 'Hello from TestPlugin!',
                'required' => false,
                'hint'     => 'Цей текст буде виведено у <!-- коментарі --> тега footer.',
            ],
        ];
    }
};
