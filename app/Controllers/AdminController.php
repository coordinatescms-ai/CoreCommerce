<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\User;
use App\Models\Setting;
use App\Core\Database\DB;

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

    // Отримуємо дані для графіка
    $results = [];

    try {
        $stmt = DB::query("
            SELECT DATE(created_at) as d, SUM(total) as daily_sum 
            FROM orders 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
        ");
        // DB::query вже виконав запит, тому просто отримуємо результат
        $results = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    } catch (\Exception $e) {
        $results = [];
    }

    // 2. Отримуємо 5 останніх замовлень
    $recentOrders = [];
    try {
        $stmt = DB::query("
            SELECT o.id, o.total, o.status, o.created_at, u.first_name as customer_name 
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentOrders = $stmt->fetchAll();
    } catch (\Exception $e) {
        $recentOrders = [];
    }

    // 1. Створюємо список останніх 7 днів (для заповнення нулями, якщо продажів не було)
    $week_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $week_data[$date] = [
            'label' => date('d.m', strtotime($date)), // формат 11.04
            'day_name' => '', 
            'sum' => 0
        ];
    }

    // Масив назв днів тижня
    $days_ua = ['Нд', 'Пн', 'Вв', 'Ср', 'Чт', 'Пт', 'Сб'];

    // 3. Об'єднуємо дані
    $final_labels = [];
    $final_values = [];

    foreach ($week_data as $date => $info) {
        $sum = $results[$date] ?? 0; // Якщо дати немає в базі, ставимо 0
        $day_index = date('w', strtotime($date));
    
        $final_labels[] = $days_ua[$day_index] . ' (' . $info['label'] . ')';
        $final_values[] = (float)$sum;  
    }

        $stats = [
            'users_count' => User::count(),
            'orders_count' => 0,
            'products_count' => 0,
            'total_sales' => 0
        ];

        View::render('admin/dashboard', [
        'stats' => $stats,
        'chartData' => $results,  // Передаємо дані для графіка
        'recentOrders' => $recentOrders,
        'final_labels' => $final_labels,
        'final_values' => $final_values
        ], 'admin');
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

    public function analytics($period)
    {
        $this->checkAdmin();
    
        $title_text = ""; // Початкове значення
        $period = $period;
        $labels = [];
        $values = [];
        $popular_products = [];
        $low_stock_products = [];

        // Масиви для перекладу
       $months_ua = ['01'=>'Січ','02'=>'Лют','03'=>'Бер','04'=>'Квіт','05'=>'Трав','06'=>'Черв','07'=>'Лип','08'=>'Серп','09'=>'Вер','10'=>'Жовт','11'=>'Лист','12'=>'Груд'];
       $days_ua = [0=>'Нд', 1=>'Пн', 2=>'Вв', 3=>'Ср', 4=>'Чт', 5=>'Пт', 6=>'Сб'];
       // Додаємо кількість замовлень для кожного дня

        switch ($period) {
            case 'year':
            // Запит за останні 12 місяців
            $title_text = "Продажі за останні 12 місяців";
            $stmt = DB::query("SELECT DATE_FORMAT(created_at, '%m') as m_num, SUM(total) as rev, COUNT(id) as cnt 
                    FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) 
                    AND status = 'completed' GROUP BY m_num ORDER BY MIN(created_at) ASC");
            break;
            case 'month':
                // За останні 30 днів (групуємо по днях)
                $title_text = "Продажі за останні 30 днів";
                $stmt = DB::query("SELECT DATE_FORMAT(created_at, '%d.%m') as day_label, SUM(total) as rev, COUNT(id) as cnt 
                    FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    AND status = 'completed' GROUP BY DATE(created_at), day_label ORDER BY DATE(created_at) ASC");
            break;
            default: // week
                // За останні 7 днів (використовуємо дні тижня)
                $title_text = "Продажі за поточний тиждень";
                $stmt = DB::query("SELECT (DAYOFWEEK(created_at)-1) as d_idx, DATE_FORMAT(created_at, '%d.%m') as d_date, SUM(total) as rev, COUNT(id) as cnt 
                    FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                    AND status = 'completed' GROUP BY d_idx, d_date, DATE(created_at) ORDER BY DATE(created_at) ASC");
            break;
        }

        $db_data = $stmt->fetchAll();

        foreach ($db_data as $row) {
            if ($period == 'year') {
                $labels[] = $months_ua[$row['m_num']];
            } elseif ($period == 'month') {
                $labels[] = $row['day_label'];
            } else {
                $labels[] = $days_ua[$row['d_idx']] . ' (' . $row['d_date'] . ')';
            }
                $values[] = (float)$row['rev'];
                $counts[] = (int)$row['cnt'];
        }

         // Якщо даних немає, ініціалізуємо порожніми значеннями для Chart.js
        if (empty($db_data)) { 
            $labels = ['Немає даних']; 
            $values = [0]; 
            $counts = [0]; 
        }

        // Використовуємо той самий $period, що і для графіка
        switch ($period) {
            case 'year':  $interval = "INTERVAL 1 YEAR"; break;
            case 'month': $interval = "INTERVAL 30 DAY"; break;
            default:      $interval = "INTERVAL 7 DAY"; break;
        }

        $stmt = DB::query("SELECT 
    p.id, 
    p.name, 
    SUM(oi.qty) as total_qty, 
    SUM(oi.price * oi.qty) as total_revenue
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
JOIN products p ON oi.product_id = p.id
WHERE o.created_at >= DATE_SUB(NOW(), $interval) 
  AND o.status != 'canceled'
GROUP BY p.id, p.name
ORDER BY total_revenue DESC
LIMIT 5");

        $popular_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 1. ПІДСТАВЛЯЄМО ДАНІ (Тимчасово замість SQL)
        $popular_products = [
            ['id' => 1, 'name' => 'Тестовий товар №1', 'total_qty' => 10, 'total_revenue' => 5000.00],
            ['id' => 2, 'name' => 'Популярний гаджет', 'total_qty' => 5, 'total_revenue' => 12500.50]
        ];   

        // Дані для таблиці (PHP використовує їх у foreach)
        // Це для прикладу, в майбутньому видалити цих два рядка
        $labels = ['Пн', 'Вв', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'];
        $values = [12500, 18200, 14000, 25000, 21000, 32000, 28500];
        // Це для прикладу, в майбутньому видалити цей рядок
        $counts = [5, 8, 4, 12, 7, 15, 10];
        $title_text = "Демонстраційний звіт (Тестові дані)";

        $stmt = DB::query("
            SELECT id, name, stock, price 
            FROM products 
            WHERE stock <= 5 
            ORDER BY stock ASC 
            LIMIT 5");

        $low_stock_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        View::render('admin/analytics/index', ['title_text' => $title_text,
            'labels' => $labels,
            'values' => $values,
            'counts' => $counts,
            'popular_products' => $popular_products,
            'low_stock_products' => $low_stock_products],
            'admin');
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

        if (!Csrf::isValid()) {
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
            'seo_title_template' => ['group' => 'seo', 'type' => 'text'],
            'seo_desc_template' => ['group' => 'seo', 'type' => 'textarea'],
            'site_timezone' => ['group' => 'general', 'type' => 'text'],
            'smtp_pass' => ['group' => 'general', 'type' => 'text'],
            'smtp_port' => ['group' => 'general', 'type' => 'text'],
            'smtr' => ['group' => 'general', 'type' => 'text'],
            'nova_poshta_api_key' => ['group' => 'general', 'type' => 'text'],
        ];
    }
}
