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
        $pluginManager->addAction('theme.footer', static function (): void {
            echo '<!-- TestPlugin footer hook -->';
        });
    }
};
