<?php

namespace App\Controllers;

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
        AuthMiddleware::handle();
        
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
        AuthMiddleware::handle();
        
        if (ThemeManager::setActiveTheme($theme)) {
            $_SESSION['success'] = "Тема '$theme' успішно активована!";
        } else {
            $_SESSION['error'] = "Не вдалося активувати тему '$theme'.";
        }
        
        header('Location: /admin/themes');
        exit;
    }

    /**
     * Завантажити нову тему
     */
    public function upload()
    {
        AuthMiddleware::handle();

        if (!empty($_FILES['theme_zip'])) {
            $theme_id = ThemeManager::uploadTheme($_FILES['theme_zip']);
            if ($theme_id) {
                $_SESSION['success'] = "Тема '$theme_id' успішно завантажена!";
            } else {
                $_SESSION['error'] = "Помилка при завантаженні теми. Перевірте формат ZIP та наявність theme.json.";
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
        AuthMiddleware::handle();
        
        $theme_info = ThemeManager::getThemeInfo($theme_id);
        if (!$theme_info) {
            $_SESSION['error'] = "Тема не знайдена.";
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
        AuthMiddleware::handle();

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'parent' => $_POST['parent'] ?? '',
            'colors' => $_POST['colors'] ?? [],
            'fonts' => $_POST['fonts'] ?? []
        ];

        if (ThemeManager::updateThemeMetadata($theme_id, $data)) {
            $_SESSION['success'] = "Параметри теми '$theme_id' оновлено!";
        } else {
            $_SESSION['error'] = "Не вдалося оновити параметри теми.";
        }

        header('Location: /admin/themes/edit/' . $theme_id);
        exit;
    }

    /**
     * Видалити тему
     */
    public function delete($theme_id)
    {
        AuthMiddleware::handle();

        if (ThemeManager::deleteTheme($theme_id)) {
            $_SESSION['success'] = "Тему '$theme_id' видалено.";
        } else {
            $_SESSION['error'] = "Не вдалося видалити тему. Можливо вона активна або це тема за замовчуванням.";
        }

        header('Location: /admin/themes');
        exit;
    }

    /**
     * Попередній перегляд теми
     */
    public function preview($theme_id)
    {
        AuthMiddleware::handle();
        
        if (ThemeManager::setPreviewTheme($theme_id)) {
            $_SESSION['success'] = "Режим попереднього перегляду для теми '$theme_id' активовано. Тепер ви бачите сайт з цією темою.";
        } else {
            $_SESSION['error'] = "Не вдалося активувати попередній перегляд.";
        }
        
        header('Location: /');
        exit;
    }

    /**
     * Скасувати попередній перегляд
     */
    public function cancelPreview()
    {
        AuthMiddleware::handle();
        ThemeManager::cancelPreview();
        header('Location: /admin/themes');
        exit;
    }
}
