<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\Theme\ThemeManager;
use App\Core\View\View;
use App\Middleware\AuthMiddleware;

class AdminThemeController
{
    /**
     * Показати список тем
     * 
     * @return void
     */
    public function index()
    {
        AuthMiddleware::isAdmin();
        
        $themes = ThemeManager::getAvailableThemes();
        $active_theme = ThemeManager::getActiveTheme();
        
        View::render('admin.themes.index', [
            'themes' => $themes,
            'active_theme' => $active_theme,
        ], 'admin');
    }

    /**
     * Активувати тему з адмін-панелі
     * 
     * @param string $theme
     * @return void
     */
    public function switch($theme)
    {
        AuthMiddleware::isAdmin();
        Csrf::abortIfInvalid();
        
        if (ThemeManager::setActiveTheme($theme)) {
            $_SESSION['success'] = sprintf(__('admin_theme_activated'), $theme);
        } else {
            $_SESSION['error'] = sprintf(__('admin_theme_activation_failed'), $theme);
        }
        
        header('Location: /admin/themes');
        exit;
    }

    /**
     * Завантажити нову тему
     */
    public function upload()
    {
        AuthMiddleware::isAdmin();
        Csrf::abortIfInvalid();

        if (!empty($_FILES['theme_zip'])) {
            $theme_id = ThemeManager::uploadTheme($_FILES['theme_zip']);
            if ($theme_id) {
                $_SESSION['success'] = sprintf(__('admin_theme_uploaded'), $theme_id);
            } else {
                $_SESSION['error'] = __('admin_theme_upload_failed');
            }
        }

        header('Location: /admin/themes');
        exit;
    }

    /**
     * Форма редагування параметрів теми
     */
    public function edit($theme_id)
    {
        AuthMiddleware::isAdmin();
        
        $theme_info = ThemeManager::getThemeInfo($theme_id);
        if (!$theme_info) {
            $_SESSION['error'] = __('admin_theme_not_found');
            header('Location: /admin/themes');
            exit;
        }

        $config = ThemeManager::getThemeConfig($theme_id);

        View::render('admin.themes.edit', [
            'theme' => $theme_info,
            'config' => $config
        ], 'admin');
    }

    /**
     * Зберегти зміни параметрів теми
     */
    public function update($theme_id)
    {
        AuthMiddleware::isAdmin();
        Csrf::abortIfInvalid();

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'parent' => $_POST['parent'] ?? '',
            'colors' => $_POST['colors'] ?? [],
            'fonts' => $_POST['fonts'] ?? []
        ];

        if (ThemeManager::updateThemeMetadata($theme_id, $data)) {
            $_SESSION['success'] = sprintf(__('admin_theme_updated'), $theme_id);
        } else {
            $_SESSION['error'] = __('admin_theme_update_failed');
        }

        header('Location: /admin/themes/edit/' . $theme_id);
        exit;
    }

    /**
     * Видалити тему
     */
    public function delete($theme_id)
    {
        AuthMiddleware::isAdmin();
        Csrf::abortIfInvalid();

        if (ThemeManager::deleteTheme($theme_id)) {
            $_SESSION['success'] = sprintf(__('admin_theme_deleted'), $theme_id);
        } else {
            $_SESSION['error'] = __('admin_theme_delete_failed');
        }

        header('Location: /admin/themes');
        exit;
    }

    /**
     * Попередній перегляд теми
     */
    public function preview($theme_id)
    {
        AuthMiddleware::isAdmin();
        Csrf::abortIfInvalid();
        
        if (ThemeManager::setPreviewTheme($theme_id)) {
            $_SESSION['success'] = sprintf(__('admin_theme_preview_activated'), $theme_id);
        } else {
            $_SESSION['error'] = __('admin_theme_preview_failed');
        }
        
        header('Location: /');
        exit;
    }

    /**
     * Скасувати попередній перегляд
     */
    public function cancelPreview()
    {
        AuthMiddleware::isAdmin();
        Csrf::abortIfInvalid();
        ThemeManager::cancelPreview();
        header('Location: /admin/themes');
        exit;
    }
}
