<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\User;
use App\Models\Setting;

class AdminController
{
    /**
     * Перевірка прав адміністратора
     */
    private function checkAdmin()
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Головна сторінка адмінпанелі (Dashboard)
     */
    public function dashboard()
    {
        $this->checkAdmin();
        
        // Статистика (заглушки для майбутнього)
        $stats = [
            'users_count' => User::count(),
            'orders_count' => 0, // Поки немає моделі Order
            'products_count' => 0, // Поки немає моделі Product
            'total_sales' => 0
        ];

        View::render('admin/dashboard', ['stats' => $stats], 'admin');
    }

    /**
     * Сторінка налаштувань
     */
    public function settings()
    {
        $this->checkAdmin();
        
        $settings = Setting::getAllGrouped();
        
        // Доступні теми (сканування папки themes)
        $themes_dir = __DIR__ . '/../../resources/themes';
        $themes = is_dir($themes_dir) ? array_diff(scandir($themes_dir), ['.', '..']) : [];

        View::render('admin/settings', [
            'settings' => $settings,
            'themes' => $themes
        ], 'admin');
    }

    /**
     * Збереження налаштувань
     */
    public function saveSettings()
    {
        $this->checkAdmin();
        
        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            return 'CSRF token validation failed';
        }

        $settings_to_update = $_POST['settings'] ?? [];
        
        if (!empty($settings_to_update)) {
            Setting::updateMany($settings_to_update);
            $_SESSION['success'] = __('settings_saved_successfully');
        }

        header('Location: /admin/settings');
        exit;
    }
}
