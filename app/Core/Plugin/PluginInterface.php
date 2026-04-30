<?php

namespace App\Core\Plugin;

interface PluginInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function register(PluginManager $pluginManager): void;
}
