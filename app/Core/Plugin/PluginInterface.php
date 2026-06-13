<?php

namespace App\Core\Plugin;

interface PluginInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function register(PluginManager $pluginManager): void;

    /**
     * Повертає схему налаштувань плагіна.
     * Якщо плагін не має налаштувань — повертає порожній масив.
     *
     * Формат:
     * [
     *   'api_key' => [
     *     'label'    => 'API ключ',
     *     'type'     => 'text',          // text | password | select | checkbox | textarea
     *     'default'  => '',
     *     'options'  => [],              // тільки для type=select: ['value' => 'Label']
     *     'required' => false,
     *     'hint'     => 'Опис поля',
     *   ],
     * ]
     */
    public function getSettingsSchema(): array;
}
