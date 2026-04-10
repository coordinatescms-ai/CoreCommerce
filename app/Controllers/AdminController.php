<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\User;
use App\Models\Setting;

class AdminController
{
    private function checkAdmin()
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function dashboard()
    {
        $this->checkAdmin();

        $stats = [
            'users_count' => User::count(),
            'orders_count' => 0,
            'products_count' => 0,
            'total_sales' => 0
        ];

        View::render('admin/dashboard', ['stats' => $stats], 'admin');
    }

    public function settings()
    {
        $this->checkAdmin();

        $settings = Setting::getAllGrouped();
        $themesDir = __DIR__ . '/../../resources/themes';
        $themes = is_dir($themesDir) ? array_values(array_diff(scandir($themesDir), ['.', '..'])) : [];

        View::render('admin/settings', [
            'settings' => $settings,
            'themes' => $themes,
        ], 'admin');
    }

    public function settingsTab($tab)
    {
        $this->checkAdmin();

        $settings = Setting::getAllGrouped();
        $themesDir = __DIR__ . '/../../resources/themes';
        $themes = is_dir($themesDir) ? array_values(array_diff(scandir($themesDir), ['.', '..'])) : [];

        $tab = trim((string) $tab);
        if ($tab === 'media') {
            View::renderPartial('admin/settings/tabs/media', ['settings' => $settings]);
            return;
        }

        View::renderPartial('admin/settings/tabs/general', ['settings' => $settings, 'themes' => $themes]);
    }

    public function saveSettings()
    {
        $this->checkAdmin();

        if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? null)) {
            $_SESSION['error'] = 'CSRF token validation failed';
            header('Location: /admin/settings');
            exit;
        }

        $settingsToUpdate = $_POST['settings'] ?? [];
        if (!is_array($settingsToUpdate)) {
            $settingsToUpdate = [];
        }

        [$settingsToUpdate, $validationError] = $this->validateAndNormalizeSettings($settingsToUpdate);
        if ($validationError !== null) {
            $_SESSION['error'] = $validationError;
            header('Location: /admin/settings');
            exit;
        }

        $watermarkUploadError = $this->processWatermarkUpload($settingsToUpdate);
        if ($watermarkUploadError !== null) {
            $_SESSION['error'] = $watermarkUploadError;
            header('Location: /admin/settings');
            exit;
        }

        $metadata = $this->settingsMetadata();
        foreach ($settingsToUpdate as $key => $value) {
            $group = $metadata[$key]['group'] ?? 'general';
            $type = $metadata[$key]['type'] ?? 'text';
            Setting::setWithMeta((string) $key, (string) $value, $group, $type);
        }

        $_SESSION['success'] = __('settings_saved_successfully');
        header('Location: /admin/settings');
        exit;
    }

    private function validateAndNormalizeSettings(array $settings): array
    {
        $numericKeys = [
            'media_thumb_width',
            'media_thumb_height',
            'media_medium_width',
            'media_medium_height',
            'media_large_width',
            'media_large_height',
        ];

        foreach ($numericKeys as $key) {
            if (!isset($settings[$key]) || $settings[$key] === '') {
                continue;
            }

            $value = (int) $settings[$key];
            if ($value < 0) {
                return [$settings, 'Розміри зображень не можуть бути відʼємними.'];
            }
            $settings[$key] = (string) $value;
        }

        if (isset($settings['media_quality'])) {
            $quality = (int) $settings['media_quality'];
            if ($quality < 10 || $quality > 100) {
                return [$settings, 'Якість зображень має бути в межах 10-100.'];
            }
            $settings['media_quality'] = (string) $quality;
        }

        $settings['media_auto_webp'] = !empty($settings['media_auto_webp']) ? '1' : '0';
        $settings['media_apply_watermark'] = !empty($settings['media_apply_watermark']) ? '1' : '0';

        $allowedWatermarkPositions = ['top-left', 'top-right', 'center', 'bottom-left', 'bottom-right'];
        $position = (string) ($settings['media_watermark_position'] ?? 'bottom-right');
        if (!in_array($position, $allowedWatermarkPositions, true)) {
            $position = 'bottom-right';
        }
        $settings['media_watermark_position'] = $position;

        return [$settings, null];
    }

    private function processWatermarkUpload(array &$settings): ?string
    {
        if (empty($_FILES['watermark_file']) || !is_array($_FILES['watermark_file'])) {
            return null;
        }

        $file = $_FILES['watermark_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'Помилка завантаження файлу водяного знака.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5242880) {
            return 'Файл водяного знака має бути до 5MB.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, (string) ($file['tmp_name'] ?? '')) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime !== 'image/png') {
            return 'Водяний знак має бути у форматі PNG з прозорістю.';
        }

        $dir = __DIR__ . '/../../public/uploads/watermarks/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return 'Не вдалося створити папку для водяних знаків.';
        }

        $filename = str_replace('.', '', uniqid('watermark_', true)) . '.png';
        $target = $dir . $filename;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $target)) {
            return 'Не вдалося зберегти водяний знак.';
        }

        $settings['media_watermark_path'] = '/uploads/watermarks/' . $filename;
        return null;
    }

    private function settingsMetadata(): array
    {
        return [
            'site_name' => ['group' => 'general', 'type' => 'text'],
            'site_description' => ['group' => 'general', 'type' => 'textarea'],
            'store_status' => ['group' => 'general', 'type' => 'select'],
            'maintenance_message' => ['group' => 'general', 'type' => 'textarea'],
            'default_language' => ['group' => 'localization', 'type' => 'select'],
            'default_currency' => ['group' => 'localization', 'type' => 'select'],
            'active_theme' => ['group' => 'appearance', 'type' => 'select'],
            'contact_email' => ['group' => 'contact', 'type' => 'text'],
            'contact_phone' => ['group' => 'contact', 'type' => 'text'],
            'media_thumb_width' => ['group' => 'media', 'type' => 'number'],
            'media_thumb_height' => ['group' => 'media', 'type' => 'number'],
            'media_medium_width' => ['group' => 'media', 'type' => 'number'],
            'media_medium_height' => ['group' => 'media', 'type' => 'number'],
            'media_large_width' => ['group' => 'media', 'type' => 'number'],
            'media_large_height' => ['group' => 'media', 'type' => 'number'],
            'media_quality' => ['group' => 'media', 'type' => 'number'],
            'media_auto_webp' => ['group' => 'media', 'type' => 'checkbox'],
            'media_apply_watermark' => ['group' => 'media', 'type' => 'checkbox'],
            'media_watermark_position' => ['group' => 'media', 'type' => 'select'],
            'media_watermark_path' => ['group' => 'media', 'type' => 'text'],
        ];
    }
}
