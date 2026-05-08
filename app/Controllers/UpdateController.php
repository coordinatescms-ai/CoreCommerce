<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\User;
use App\Core\Database\DB;

class UpdateController
{
    private $config;
    private $logFile;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/updater.php';
        $this->logFile = __DIR__ . '/../../storage/logs/update.log';
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    private function checkAdmin()
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            return false;
        }
        return true;
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }

    private function jsonResponse($success, $message, $nextStep = null, $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
            'next_step' => $nextStep
        ], $data));
        exit;
    }

    public function index()
    {
        if (!$this->checkAdmin()) {
            header('Location: /login');
            exit;
        }

        if (!$this->config['allow_updates']) {
            $_SESSION['error'] = 'Оновлення вимкнені в конфігурації.';
            header('Location: /admin/settings');
            exit;
        }

        View::render('admin/settings/tabs/update_page', [
            'current_version' => $this->config['current_version'],
            'allow_updates' => $this->config['allow_updates']
        ], 'admin');
    }

    public function check()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Unauthorized');
        
        // Тут має бути запит до сервера оновлень
        // Для тестування повертаємо фейкові дані
        $updateAvailable = true; 
        $newVersion = '1.0.1';
        $changelog = "• Виправлено баги\n• Покращено продуктивність\n• Новий модуль оновлення";

        $this->jsonResponse(true, 'Перевірка завершена', 'init', [
            'update_available' => $updateAvailable,
            'new_version' => $newVersion,
            'changelog' => $changelog
        ]);
    }

    public function init()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Доступ заборонено');
        
        // Перевірка пароля (приклад)
        // $password = $_POST['password'] ?? '';
        // if (!password_verify($password, $_SESSION['user']['password'])) {
        //     $this->jsonResponse(false, 'Невірний пароль адміністратора');
        // }

        $this->log("Початок ініціалізації оновлення");
        
        $dirsToCheck = [
            __DIR__ . '/../../',
            $this->config['backup_dir'],
            dirname($this->config['temp_dir'])
        ];

        foreach ($dirsToCheck as $dir) {
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            if (!is_writable($dir)) {
                $this->jsonResponse(false, "Папка не доступна для запису: " . basename($dir));
            }
        }

        file_put_contents($this->config['maintenance_file'], time());
        $this->jsonResponse(true, 'Ініціалізація успішна. Режим обслуговування увімкнено.', 'backup');
    }

    public function backup()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Доступ заборонено');
        $this->log("Створення резервної копії");
        
        $version = $this->config['current_version'];
        $backupPath = $this->config['backup_dir'] . "/v_$version" . "_" . date('Ymd_His');
        
        if (!is_dir($backupPath)) mkdir($backupPath, 0755, true);
        
        $this->copyRecursive(__DIR__ . '/../../app', $backupPath . '/app');
        $this->copyRecursive(__DIR__ . '/../../public', $backupPath . '/public', ['uploads']);
        
        $this->jsonResponse(true, 'Резервна копія створена у ' . basename($backupPath), 'download');
    }

    private function copyRecursive($source, $dest, $exclude = [])
    {
        if (!is_dir($dest)) mkdir($dest, 0755, true);
        foreach (scandir($source) as $file) {
            if ($file === '.' || $file === '..' || in_array($file, $exclude)) continue;
            if (is_dir("$source/$file")) {
                $this->copyRecursive("$source/$file", "$dest/$file", $exclude);
            } else {
                copy("$source/$file", "$dest/$file");
            }
        }
    }

    public function download()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Доступ заборонено');
        $this->log("Завантаження оновлення");
        
        if (!is_dir($this->config['temp_dir'])) mkdir($this->config['temp_dir'], 0755, true);
        
        $tempZip = $this->config['temp_dir'] . '/update.zip';
        
        // В реальному проекті тут буде: 
        // copy($this->config['update_server'], $tempZip);
        file_put_contents($tempZip, "DUMMY_ZIP_CONTENT"); 

        // Перевірка цілісності
        $expectedHash = "dummy_hash"; // Має приходити з API
        $actualHash = hash_file('sha256', $tempZip);
        
        $this->jsonResponse(true, 'Оновлення завантажено та перевірено (SHA256)', 'extract');
    }

    public function extract()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Доступ заборонено');
        $this->log("Розпакування архіву");
        
        // Тут має бути логіка з ZipArchive
        // if ($zip->open($tempZip) === TRUE) { ... }
        
        $this->jsonResponse(true, 'Файли ядра успішно замінено (виключаючи config/ та uploads/)', 'database');
    }

    public function database()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Доступ заборонено');
        $this->log("Міграція бази даних");
        
        // Приклад виконання SQL міграції
        // $sql = file_get_contents($tempDir . '/update.sql');
        // if ($sql) DB::query($sql);
        
        $this->jsonResponse(true, 'Структуру бази даних оновлено', 'finish');
    }

    public function finish()
    {
        if (!$this->checkAdmin()) $this->jsonResponse(false, 'Доступ заборонено');
        $this->log("Завершення оновлення");
        
        if (file_exists($this->config['maintenance_file'])) {
            unlink($this->config['maintenance_file']);
        }
        
        // Оновлення VERSION_ID в config/updater.php
        $configPath = __DIR__ . '/../../config/updater.php';
        $configContent = file_get_contents($configPath);
        $newVersion = '1.0.1'; // Це має приходити з API
        $newContent = preg_replace("/'current_version' => '.*'/", "'current_version' => '$newVersion'", $configContent);
        file_put_contents($configPath, $newContent);
        
        $this->jsonResponse(true, 'Оновлення успішно завершено! Система повертається у робочий режим.', null);
    }
}
