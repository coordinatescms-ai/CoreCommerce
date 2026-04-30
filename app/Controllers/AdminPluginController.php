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
}
