<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\Plugin\PluginManager;
use App\Core\View\View;

class AdminPluginController
{
    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->checkAdmin();

        $plugins = PluginManager::getInstance()->getPluginsForAdmin();
        View::render('admin/plugins/index', [
            'plugins' => $plugins,
            'flash' => $_SESSION['plugins_flash'] ?? null,
            'maxUploadSizeMb' => 10,
        ], 'admin');

        unset($_SESSION['plugins_flash']);
    }

    public function toggle(): void
    {
        $this->checkAdmin();
        Csrf::abortIfInvalid();

        $slug = (string) ($_POST['slug'] ?? '');
        $action = (string) ($_POST['action'] ?? '');

        $result = PluginManager::getInstance()->togglePlugin($slug, $action === 'activate');
        $_SESSION['plugins_flash'] = $result;

        header('Location: /admin/plugins');
        exit;
    }

    public function upload(): void
    {
        $this->checkAdmin();
        Csrf::abortIfInvalid();

        $result = PluginManager::getInstance()->uploadPlugin($_FILES['plugin_zip'] ?? []);
        $_SESSION['plugins_flash'] = $result;

        header('Location: /admin/plugins');
        exit;
    }

    public function settings(string $slug): void
    {
        $this->checkAdmin();

        $slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        $manager  = PluginManager::getInstance();
        $settings = $manager->getPluginSettings($slug);
        $meta     = $manager->getPluginsForAdmin();
        $plugin   = current(array_filter($meta, fn($p) => $p['slug'] === $slug));

        if (!$plugin) {
            $_SESSION['plugins_flash'] = ['success' => false, 'message' => 'Плагін не знайдено.'];
            header('Location: /admin/plugins');
            exit;
        }

        if (empty($settings)) {
            $_SESSION['plugins_flash'] = ['success' => false, 'message' => 'Цей плагін не має налаштувань.'];
            header('Location: /admin/plugins');
            exit;
        }

        View::render('admin/plugins/settings', [
            'plugin'   => $plugin,
            'settings' => $settings,
            'flash'    => $_SESSION['plugins_flash'] ?? null,
        ], 'admin');

        unset($_SESSION['plugins_flash']);
    }

    public function saveSettings(string $slug): void
    {
        $this->checkAdmin();
        Csrf::abortIfInvalid();

        $slug   = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        $result = PluginManager::getInstance()->savePluginSettings($slug, $_POST);

        $_SESSION['plugins_flash'] = $result;
        header('Location: /admin/plugins/settings/' . $slug);
        exit;
    }
}
