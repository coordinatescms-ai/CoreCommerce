<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\User;
use App\Models\Setting;
use App\Core\Database\DB;
use App\Models\Page;
use App\Models\Review;
use App\Core\Mail\MailService;
use PHPMailer\PHPMailer\PHPMailer;

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
        $recentOrders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $recentOrders = [];
    }

    // 1. Створюємо список останніх 7 днів
    $week_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $week_data[$date] = [
            'label' => date('d.m', strtotime($date)), // формат 11.04
            'day_name' => '', 
            'sum' => 0
        ];
    }

    // 3. Об'єднуємо дані
    $final_labels = [];
    $final_values = [];

    // Масив англійських назв (ключів), за якими ми будемо звертатися до хелпера __()
    $day_keys = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    foreach ($week_data as $date => $info) {
        $sum = $results[$date] ?? 0; 
        $day_index = (int)date('w', strtotime($date));
    
        // Отримуємо ключ дня (наприклад, 'sun', 'mon')
        $day_key = $day_keys[$day_index] ?? 'mon';

        // Перекладаємо конкретний день як рядок через ваш хелпер
        $day_title = __($day_key); 

        $final_labels[] = $day_title . ' (' . $info['label'] . ')';
        $final_values[] = (float)$sum;  
    }

    $stats = [
        'users_count' => User::count(),
        'orders_count' => Setting::count_order(),
        'products_count' => Setting::count_products(),
        'total_sales' => Setting::total_sales()
    ];

    View::render('admin/dashboard', [
        'stats' => $stats,
        'chartData' => $results,  
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

        $labels = [];
        $values = [];
        $counts = [];
        $popular_products = [];
        $low_stock_products = [];

        // Масиви відповідностей номерів до текстових ключів у мовних файлах
        $month_keys = [
            '01'=>'m01', '02'=>'m02', '03'=>'m03', '04'=>'m04', '05'=>'m05', '06'=>'m06',
            '07'=>'m07', '08'=>'m08', '09'=>'m09', '10'=>'m10', '11'=>'m11', '12'=>'m12'
        ];
        $day_keys = [0=>'sun', 1=>'mon', 2=>'tue', 3=>'wed', 4=>'thu', 5=>'fri', 6=>'sat'];

        // --- Обробка довільного діапазону дат ---
        $date_from_raw = trim($_GET['from'] ?? '');
        $date_to_raw   = trim($_GET['to']   ?? '');

        // Валідація формату YYYY-MM-DD
        $isValidDate = static function (string $d): bool {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                return false;
            }
            [$y, $m, $day] = explode('-', $d);
            return checkdate((int)$m, (int)$day, (int)$y);
        };

        $use_custom_range = $isValidDate($date_from_raw) && $isValidDate($date_to_raw)
                            && $date_from_raw <= $date_to_raw;

        if ($use_custom_range) {
            $date_from = $date_from_raw;
            $date_to   = $date_to_raw;

            // Локалізація заголовку для довільного діапазону
            $title_text = __('analytics_sales_from') . ' ' . date('d.m.Y', strtotime($date_from))
                        . ' — ' . date('d.m.Y', strtotime($date_to));

            // Якщо діапазон > 60 днів — групуємо по місяцях, інакше по днях
            $diff_days = (int)((strtotime($date_to) - strtotime($date_from)) / 86400);
            $group_by_month = $diff_days > 60;

            if ($group_by_month) {
                $stmt = DB::query(
                    "SELECT DATE_FORMAT(created_at, '%m') as m_num,
                            DATE_FORMAT(created_at, '%m.%Y') as period_label,
                            SUM(total) as rev, COUNT(id) as cnt
                     FROM orders
                     WHERE DATE(created_at) BETWEEN ? AND ?
                       AND status = 'completed'
                     GROUP BY DATE_FORMAT(created_at, '%Y-%m'),
                              DATE_FORMAT(created_at, '%m'),
                              DATE_FORMAT(created_at, '%m.%Y')
                     ORDER BY MIN(created_at) ASC",
                    [$date_from, $date_to]
                );
            } else {
                $stmt = DB::query(
                    "SELECT DATE_FORMAT(created_at, '%d.%m') as day_label,
                            SUM(total) as rev, COUNT(id) as cnt
                     FROM orders
                     WHERE DATE(created_at) BETWEEN ? AND ?
                       AND status = 'completed'
                     GROUP BY DATE(created_at), DATE_FORMAT(created_at, '%d.%m')
                     ORDER BY DATE(created_at) ASC",
                    [$date_from, $date_to]
                );
            }

            $db_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($db_data as $row) {
                if ($group_by_month) {
                    // Перекладаємо місяць через хелпер __()
                    $m_key = $month_keys[$row['m_num']] ?? 'm01';
                    $translated_month = __($m_key);
                    $labels[] = $translated_month . ' ' . substr($row['period_label'], 3);
                } else {
                    $labels[] = $row['day_label'];
                }
                
                $values[] = (float)$row['rev'];
                $counts[] = (int)$row['cnt'];
            }

            // Популярні товари за довільний діапазон
            $stmt = DB::query(
                "SELECT p.id, p.name,
                        SUM(oi.qty) as total_qty,
                        SUM(oi.price * oi.qty) as total_revenue
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 JOIN products p ON oi.product_id = p.id
                 WHERE DATE(o.created_at) BETWEEN ? AND ?
                   AND o.status = 'completed'
                 GROUP BY p.id, p.name
                 ORDER BY total_revenue DESC
                 LIMIT 5",
                [$date_from, $date_to]
            );
            $popular_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } else {
            // --- Стандартні періоди: week / month / year ---
            switch ($period) {
                case 'year':
                    $title_text = __('analytics_title_year'); // 'Продажі за останні 12 місяців'
                    $stmt = DB::query(
                        "SELECT DATE_FORMAT(created_at, '%m') as m_num, SUM(total) as rev, COUNT(id) as cnt
                         FROM orders
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                           AND status = 'completed'
                         GROUP BY m_num
                         ORDER BY MIN(created_at) ASC"
                    );
                    break;
                case 'month':
                    $title_text = __('analytics_title_month'); // 'Продажі за останні 30 днів'
                    $stmt = DB::query(
                        "SELECT DATE_FORMAT(created_at, '%d.%m') as day_label, SUM(total) as rev, COUNT(id) as cnt
                         FROM orders
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           AND status = 'completed'
                         GROUP BY DATE(created_at), day_label
                         ORDER BY DATE(created_at) ASC"
                    );
                    break;
                default: // week
                    $title_text = __('analytics_title_week'); // 'Продажі за поточний тиждень'
                    $stmt = DB::query(
                        "SELECT (DAYOFWEEK(created_at)-1) as d_idx, DATE_FORMAT(created_at, '%d.%m') as d_date,
                                SUM(total) as rev, COUNT(id) as cnt
                         FROM orders
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                           AND status = 'completed'
                         GROUP BY d_idx, d_date, DATE(created_at)
                         ORDER BY DATE(created_at) ASC"
                    );
                    break;
            }

            $db_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($db_data as $row) {
                if ($period === 'year') {
                    // Перекладаємо місяць через хелпер __() по його ключу (m01 - m12)
                    $m_key = $month_keys[$row['m_num']] ?? 'm01';
                    $labels[] = __($m_key);
                } elseif ($period === 'month') {
                    $labels[] = $row['day_label'];
                } else {
                    // Перекладаємо день тижня через хелпер __() по його індексу (sun - sat)
                    $d_key = $day_keys[$row['d_idx']] ?? 'mon';
                    $labels[] = __($d_key) . ' (' . $row['d_date'] . ')';
                }
                $values[] = (float)$row['rev'];
                $counts[] = (int)$row['cnt'];
            }

            // Популярні товари за стандартний період
            switch ($period) {
                case 'year':  $interval = 'INTERVAL 1 YEAR'; break;
                case 'month': $interval = 'INTERVAL 30 DAY'; break;
                default:      $interval = 'INTERVAL 7 DAY';  break;
            }

            $stmt = DB::query(
                "SELECT p.id, p.name,
                        SUM(oi.qty) as total_qty,
                        SUM(oi.price * oi.qty) as total_revenue
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 JOIN products p ON oi.product_id = p.id
                 WHERE o.created_at >= DATE_SUB(NOW(), $interval)
                   AND o.status = 'completed'
                 GROUP BY p.id, p.name
                 ORDER BY total_revenue DESC
                 LIMIT 5"
            );
            $popular_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Якщо даних немає — порожні масиви для Chart.js
        if (empty($labels)) {
            $labels = array(__('no_data'));
            $values = array(0);
            $counts = array(0);
        }

        // Експорт в CSV (враховує і довільний діапазон)
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $filename = 'analytics_' . $period . '_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Локалізуємо заголовки колонок CSV
            fputcsv($output, [
                __('csv_period'),
                __('csv_orders'),
                __('csv_revenue'),
                __('csv_share')
            ]);

            $total_sum = array_sum($values);
            foreach ($labels as $key => $label) {
                $val     = $values[$key];
                $count   = $counts[$key];
                $percent = $total_sum > 0 ? round(($val / $total_sum) * 100, 1) : 0;
                fputcsv($output, [
                    strip_tags($label),
                    $count . ' ' . __('pcs'),
                    number_format($val, 2, '.', ''),
                    $percent . '%',
                ]);
            }
            $total_orders = array_sum($counts);
            
            // Локалізуємо підсумковий рядок CSV
            fputcsv($output, [
                __('csv_total'),
                $total_orders . ' ' . __('pcs'),
                number_format($total_sum, 2, '.', ''),
                '100%'
            ]);
            fclose($output);
            exit;
        }
        // Товари, що закінчуються (незалежно від діапазону)
        $stmt = DB::query("
        SELECT p.id, p.name, COALESCE(ps.quantity, 0) AS stock, p.price
        FROM products p
        LEFT JOIN product_stocks ps
        ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
        AND ps.option_id IS NULL
        WHERE COALESCE(ps.quantity, 0) <= 5
        ORDER BY COALESCE(ps.quantity, 0) ASC
        LIMIT 5");
        
        $low_stock_products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        View::render('admin/analytics/index', [
            'period'             => $period,
            'title_text'         => $title_text,
            'labels'             => $labels,
            'values'             => $values,
            'counts'             => $counts,
            'popular_products'   => $popular_products,
            'low_stock_products' => $low_stock_products,
            'use_custom_range'   => $use_custom_range ?? false,
            'date_from'          => $date_from_raw,
            'date_to'            => $date_to_raw,
        ], 'admin');
    }

    public function clearCache()
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin');
            exit;
        }

        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        $errors = [];
        $cleared = [];

        $targets = [
            $cacheDir . '/active_plugins.json',
            $cacheDir . '/asset_version',
        ];

        foreach ($targets as $file) {
            if (!file_exists($file)) {
                continue;
            }

            if (is_file($file)) {
                if (@unlink($file)) {
                    $cleared[] = basename($file);
                } else {
                    $errors[] = sprintf(__('admin_cache_file_delete_error'), basename($file));
                }
            }
        }

        if (is_dir($cacheDir)) {
            $entries = @scandir($cacheDir);
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }

                    $path = $cacheDir . '/' . $entry;
                    if (!is_file($path)) {
                        continue;
                    }

                    if (in_array($path, $targets, true)) {
                        continue;
                    }

                    if (@unlink($path)) {
                        $cleared[] = $entry;
                    } else {
                        $errors[] = sprintf(__('admin_cache_file_delete_error'), $entry);
                    }
                }
            } else {
                $errors[] = __('admin_cache_directory_read_error');
            }
        }

        try {
            Setting::setWithMeta('asset_version', (string) time(), 'system', 'text');
            $cleared[] = 'asset_version(setting)';
        } catch (\Throwable $e) {
            $errors[] = sprintf(__('admin_asset_version_update_error'), $e->getMessage());
        }

        $admin = $_SESSION['user'] ?? [];
        $adminId = (int) ($admin['id'] ?? 0);
        $adminEmail = (string) ($admin['email'] ?? 'unknown');
        $timestamp = date('Y-m-d H:i:s');
        $logLine = sprintf(
            "[%s] admin_id=%d admin_email=%s action=clear_cache cleared=%s errors=%s
",
            $timestamp,
            $adminId,
            $adminEmail,
            json_encode(array_values(array_unique($cleared)), JSON_UNESCAPED_UNICODE),
            json_encode($errors, JSON_UNESCAPED_UNICODE)
        );

        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @file_put_contents($logDir . '/admin_actions.log', $logLine, FILE_APPEND);

        if (empty($errors)) {
            $_SESSION['success'] = __('admin_cache_cleared');
        } else {
            $_SESSION['error'] = sprintf(__('admin_cache_partial_clear'), implode(' ', $errors));
        }

        $redirectUrl = (strpos((string)($_SERVER['HTTP_REFERER'] ?? ''), '/admin/system') !== false) ? '/admin/system' : '/admin';
        header('Location: ' . $redirectUrl);
        exit;
    }



    public function system()
    {
        $this->checkAdmin();

        $systemInfo = $this->collectSystemInfo();
        $logs = $this->collectSystemLogs();
        $cronTasks = $this->collectCronTasks();
        $environment = [
            'display_errors' => (string) Setting::get('display_errors', ini_get('display_errors') ?: '0'),
            'store_status' => (string) Setting::get('store_status', 'open'),
        ];

        $currencies = DB::query(
            'SELECT * FROM currencies ORDER BY is_active DESC, code ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);

        View::render('admin/system', [
            'systemInfo'  => $systemInfo,
            'logs'        => $logs,
            'cronTasks'   => $cronTasks,
            'environment' => $environment,
            'currencies'  => $currencies,
        ], 'admin');
    }

    public function saveSystemEnvironment()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin/system');
            exit;
        }

        $mode = (string) ($_POST['mode'] ?? 'release');
        if (!in_array($mode, ['development', 'release', 'maintenance'], true)) {
            $mode = 'release';
        }

        $debugMode = $mode === 'development' ? '1' : '0';
        $maintenanceMode = $mode === 'maintenance' ? 'closed' : 'open';
        Setting::setWithMeta('display_errors', $debugMode, 'system', 'checkbox');
        Setting::setWithMeta('store_status', $maintenanceMode, 'general', 'select');

        @ini_set('display_errors', $debugMode);
        $_SESSION['success'] = __('admin_system_modes_updated');
        header('Location: /admin/system');
        exit;
    }

    public function saveSecuritySettings(): void
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin/system');
            exit;
        }

        // HTTPS редирект
        $httpsRedirect = isset($_POST['https_redirect']) ? '1' : '0';
        Setting::setWithMeta('https_redirect', $httpsRedirect, 'security', 'checkbox');

        // HSTS (лише якщо HTTPS увімкнено)
        if ($httpsRedirect === '1') {
            $hstsEnabled   = isset($_POST['hsts_enabled'])   ? '1' : '0';
            $hstsSubdomains= isset($_POST['hsts_subdomains'])? '1' : '0';
            $hstsPreload   = isset($_POST['hsts_preload'])   ? '1' : '0';

            $allowedMaxAges = [300, 3600, 86400, 2592000, 31536000];
            $maxAge = (int) ($_POST['hsts_max_age'] ?? 300);
            if (!in_array($maxAge, $allowedMaxAges, true)) {
                $maxAge = 300;
            }

            Setting::setWithMeta('hsts_enabled',    $hstsEnabled,    'security', 'checkbox');
            Setting::setWithMeta('hsts_max_age',    (string) $maxAge,'security', 'number');
            Setting::setWithMeta('hsts_subdomains', $hstsSubdomains, 'security', 'checkbox');
            Setting::setWithMeta('hsts_preload',    $hstsPreload,    'security', 'checkbox');
        } else {
            // Якщо HTTPS вимкнено — HSTS теж вимикаємо
            Setting::setWithMeta('hsts_enabled', '0', 'security', 'checkbox');
        }

        // CSP
        $cspMode = $_POST['csp_mode'] ?? 'off';
        if (!in_array($cspMode, ['off', 'report-only', 'enforce'], true)) {
            $cspMode = 'off';
        }
        Setting::setWithMeta('csp_mode', $cspMode, 'security', 'select');

        $this->logAdminAction('security_settings_saved', [
            'https_redirect' => $httpsRedirect,
            'hsts_enabled'   => Setting::get('hsts_enabled', '0'),
            'csp_mode'       => $cspMode,
        ]);

        $_SESSION['success'] = __('security_settings_saved');
        header('Location: /admin/system');
        exit;
    }

    public function generateSitemap(): void
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin/system');
            exit;
        }

        try {
            $outputDir = dirname(__DIR__, 2) . '/public/sitemaps/';
            $result    = \App\Services\SitemapService::generate($outputDir);

            Setting::setWithMeta('sitemap_last_generated', date('Y-m-d H:i:s'), 'system', 'text');

            $total = array_sum($result['counts']);
            $files = count($result['files']);
            $time  = $result['time'];

            $this->logAdminAction('sitemap_generated', [
                'files'  => $files,
                'total'  => $total,
                'time'   => $time,
            ]);

            $_SESSION['success'] = sprintf(
                __('sitemap_generated_ok'),
                $total, $files, $time
            );
        } catch (\Throwable $e) {
            $_SESSION['error'] = __('sitemap_error') . ': ' . $e->getMessage();
        }

        header('Location: /admin/system');
        exit;
    }

    public function sendSystemTestEmail(): void
    {
        $this->checkAdmin();

        $to = trim((string) ($_POST['test_email'] ?? ''));
        $useDb = isset($_POST['test_email_use_db']);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = __('admin_test_email_invalid');
            header('Location: /admin/system');
            exit;
        }

        $subject = __('admin_test_email_subject');
        $bodyText = sprintf(__('admin_test_email_body'), $useDb ? __('admin_test_email_db_source') : __('admin_test_email_file_source'));
        $bodyHtml = nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8'));

        if ($useDb) {
            $result = (new MailService())->sendWithDiagnostics($to, $subject, $bodyHtml);
        } else {
            $result = $this->sendMailFromFileConfigWithDiagnostics($to, $subject, $bodyHtml);
        }

        if ($result['success']) {
            $_SESSION['success'] = sprintf(__('admin_test_email_sent'), htmlspecialchars($to));
        } else {
            $_SESSION['error'] = sprintf(__('admin_test_email_error'), htmlspecialchars($result['error']));
        }

        $this->logAdminAction('system_test_mail', [
            'to'      => $to,
            'source'  => $useDb ? 'db' : 'file',
            'success' => $result['success'],
            'error'   => $result['error'] ?? '',
        ]);
        header('Location: /admin/system');
        exit;
    }

    public function backupDatabase()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin/system');
            exit;
        }

        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0775, true)) {
            $_SESSION['error'] = __('admin_backup_dir_error');
            header('Location: /admin/system');
            exit;
        }

        $filePath = $backupDir . '/backup_' . date('Ymd_His') . '.sql';
        $tables = DB::query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $dump = "-- CoreCommerce SQL Backup\n-- " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $create = DB::query('SHOW CREATE TABLE `' . str_replace('`', '``', (string) $table) . '`')->fetch(\PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? array_values($create)[1] ?? '';
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n" . $createSql . ";\n\n";

            $rows = DB::query('SELECT * FROM `' . str_replace('`', '``', (string) $table) . '`')->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $cols = array_map(static fn($c) => '`' . str_replace('`', '``', (string) $c) . '`', array_keys($row));
                    $vals = array_map(static function ($v) {
                        return $v === null ? 'NULL' : DB::quote((string) $v);
                    }, array_values($row));
                    $dump .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
                }
                $dump .= "\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (@file_put_contents($filePath, $dump) === false) {
            $_SESSION['error'] = __('admin_backup_write_error');
        } else {
            $_SESSION['success'] = sprintf(__('admin_backup_created'), basename($filePath));
            $this->logAdminAction('backup_database', ['file' => basename($filePath)]);
        }

        header('Location: /admin/system');
        exit;
    }

    public function optimizeDatabase()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin/system');
            exit;
        }

        $tables = DB::query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $optimized = [];
        $errors = [];

        foreach ($tables as $table) {
            try {
                DB::query('OPTIMIZE TABLE `' . str_replace('`', '``', (string) $table) . '`');
                $optimized[] = $table;
            } catch (\Throwable $e) {
                $errors[] = $table . ': ' . $e->getMessage();
            }
        }

        $this->logAdminAction('optimize_database', ['optimized' => $optimized, 'errors' => $errors]);
        $_SESSION['success'] = sprintf(__('admin_optimize_success'), count($optimized));
        if (!empty($errors)) {
            $_SESSION['error'] = sprintf(__('admin_optimize_partial'), implode('; ', $errors));
        }

        header('Location: /admin/system');
        exit;
    }

    public function clearLogs()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /admin/system');
            exit;
        }

        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        $deleted = [];
        if (is_dir($logDir)) {
            $entries = scandir($logDir) ?: [];
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') { continue; }
                $path = $logDir . '/' . $entry;
                if (is_file($path) && @unlink($path)) {
                    $deleted[] = $entry;
                }
            }
        }

        $this->logAdminAction('clear_logs', ['deleted' => $deleted]);
        $_SESSION['success'] = sprintf(__('admin_logs_cleared'), count($deleted));
        header('Location: /admin/system');
        exit;
    }

    private function collectSystemInfo(): array
    {
        $updaterConfig = require dirname(__DIR__, 2) . '/config/updater.php';
        $diskTotal = @disk_total_space(dirname(__DIR__, 2));
        $diskFree = @disk_free_space(dirname(__DIR__, 2));

        return [
            'php_version' => PHP_VERSION,
            'mysql_version' => DB::query('SELECT VERSION()')->fetchColumn(),
            'engine_version' => $updaterConfig['current_version'] ?? 'unknown',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: 'unknown',
            'memory_limit' => ini_get('memory_limit') ?: 'unknown',
            'extensions' => [
                'gd' => extension_loaded('gd'),
                'curl' => extension_loaded('curl'),
                'mbstring' => extension_loaded('mbstring'),
            ],
            'disk_total' => $diskTotal,
            'disk_free' => $diskFree,
            'disk_used' => ($diskTotal !== false && $diskFree !== false) ? $diskTotal - $diskFree : false,
        ];
    }

    private function collectSystemLogs(): array
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        $phpErrorLog = $logDir . '/error.log';
        $adminActionsLog = $logDir . '/admin_actions.log';

        return [
            'php_errors' => $this->tailFile($phpErrorLog, 150),
            'admin_actions' => $this->tailFile($adminActionsLog, 150),
            'log_files' => is_dir($logDir) ? array_values(array_filter(scandir($logDir) ?: [], static fn($f) => $f !== '.' && $f !== '..')) : [],
        ];
    }

    private function collectCronTasks(): array
    {
        try {
            $stmt = DB::query('SELECT id, name, command, schedule, last_run, next_run, status, last_result, error_message, params FROM cron_tasks ORDER BY id ASC');
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array{success: bool, error: string}
     */
    private function sendMailFromFileConfigWithDiagnostics(string $to, string $subject, string $body): array
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = (string) ($config['host'] ?? '');
            $mail->SMTPAuth   = true;
            $mail->Username   = (string) ($config['username'] ?? '');
            $mail->Password   = (string) ($config['password'] ?? '');
            $mail->SMTPSecure = (string) ($config['encryption'] ?? 'tls');
            $mail->Port       = (int)    ($config['port'] ?? 587);
            $mail->CharSet    = 'UTF-8';

            // Таймаут — щоб сторінка не зависала при недосяжному SMTP
            $mail->Timeout    = 10;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom(
                (string) ($config['from_email'] ?? 'admin@example.com'),
                (string) ($config['from_name']  ?? 'CoreCommerce')
            );
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();

            return ['success' => true, 'error' => ''];
        } catch (\Throwable $e) {
            $detail = $mail->ErrorInfo ?: $e->getMessage();
            error_log('sendMailFromFileConfig error: ' . $detail);
            return ['success' => false, 'error' => $detail];
        }
    }

    private function tailFile(string $path, int $maxLines = 100): array
    {
        if (!is_file($path)) {
            return [];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        return array_slice($lines, -$maxLines);
    }

    private function logAdminAction(string $action, array $meta = []): void
    {
        $admin = $_SESSION['user'] ?? [];
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $line = sprintf("[%s] admin_id=%d admin_email=%s action=%s meta=%s\n",
            date('Y-m-d H:i:s'),
            (int) ($admin['id'] ?? 0),
            (string) ($admin['email'] ?? 'unknown'),
            $action,
            json_encode($meta, JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($logDir . '/admin_actions.log', $line, FILE_APPEND);
    }

    public function settingsTab($tab)
    {
    $this->checkAdmin();
    $tab = trim((string) $tab);

    // Використовуємо switch для зручного перемикання
    switch ($tab) {
        case 'media':
            $settings = Setting::getAllGrouped();
            View::renderPartial('admin/settings/tabs/media', ['settings' => $settings]);
            break;

        case 'shipping':
            // Отримуємо всі методи з типом shipping
            $methods = Setting::getShopMethods('shipping');
            View::renderPartial('admin/settings/tabs/shipping', ['methods' => $methods]);
            break;

        case 'payment':
            // Отримуємо методи оплати з таблиці shop_methods
            $methods = Setting::getShopMethods('payment');
    
            // Рендеримо частковий шаблон вкладки оплати
            View::renderPartial('admin/settings/tabs/payment', [
                'methods' => $methods
            ]);
            break;
        case 'reviews':
            $filters = [
                'product' => trim((string) ($_GET['product'] ?? '')),
                'author' => trim((string) ($_GET['author'] ?? '')),
                'status' => (string) ($_GET['status'] ?? ''),
            ];
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $result = Review::getAdminList($filters, $limit, $offset);
            View::renderPartial('admin/settings/tabs/reviews', [
                'reviews' => $result['rows'],
                'filters' => $filters,
                'page' => $page,
                'pages' => max(1, (int) ceil($result['total'] / $limit)),
            ]);
            break;

        case 'update':
            $config = require __DIR__ . '/../../config/updater.php';
            View::renderPartial('admin/settings/tabs/update', [
                'updaterConfig'   => $config,
                'current_version' => $config['current_version'],
                'phpVersion'      => PHP_VERSION,
            ]);
            break;

        case 'integrations':
            $promSettings = [
                'prom_enabled'        => (string) Setting::get('prom_enabled',        '0'),
                'prom_api_key'        => (string) Setting::get('prom_api_key',        ''),
                'prom_sync_method'    => (string) Setting::get('prom_sync_method',    'xml'),
                'prom_webhook_secret' => (string) Setting::get('prom_webhook_secret', ''),
                'prom_last_sync'      => (string) Setting::get('prom_last_sync',      ''),
            ];
            $siteUrl  = rtrim((string) Setting::get('site_url', ''), '/');
            $queueStats = [];
            if ($promSettings['prom_enabled'] === '1') {
                $queueStats = (new \App\Services\PromSyncService())->getQueueStats();
            }
            View::renderPartial('admin/settings/tabs/integrations', [
                'prom'       => $promSettings,
                'siteUrl'    => $siteUrl,
                'queueStats' => $queueStats,
            ]);
            break;

        case 'footer':
            $socialLinks = \App\Models\SocialLink::getAll();
            View::renderPartial('admin/settings/tabs/footer', [
                'socialLinks' => $socialLinks
            ]);
            break;

        case 'general':
        default:
            $settings = Setting::getAllGrouped();
            $themesDir = __DIR__ . '/../../resources/themes';
            $themes = is_dir($themesDir) ? array_values(array_diff(scandir($themesDir), ['.', '..'])) : [];
            View::renderPartial('admin/settings/tabs/general', [
                'settings' => $settings, 
                'themes' => $themes
            ]);
            break;
    }
    }

    public function saveSettings()
    {
        $this->checkAdmin();

        // Визначаємо вкладку відразу, щоб знати, куди повертати при помилці
        $currentTab = $_POST['current_tab'] ?? 'general';
        $redirectUrl = '/admin/settings?tab=' . urlencode($currentTab);

        if (!Csrf::isValid()) {
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: ' . $redirectUrl); // Повертаємо на ту ж вкладку
            exit;
        }

        $settingsToUpdate = $_POST['settings'] ?? [];
        if (!is_array($settingsToUpdate)) {
            $settingsToUpdate = [];
        }

        [$settingsToUpdate, $validationError] = $this->validateAndNormalizeSettings($settingsToUpdate);
        if ($validationError !== null) {
            $_SESSION['error'] = $validationError;
            header('Location: ' . $redirectUrl); // Повертаємо на ту ж вкладку
            exit;
        }

        $watermarkUploadError = $this->processWatermarkUpload($settingsToUpdate);
        if ($watermarkUploadError !== null) {
            $_SESSION['error'] = $watermarkUploadError;
            header('Location: ' . $redirectUrl); // Повертаємо на ту ж вкладку
            exit;
        }

        $logotypeUploadError = $this->processLogotypeUpload($settingsToUpdate);
        if ($logotypeUploadError !== null) {
            $_SESSION['error'] = $logotypeUploadError;
            header('Location: ' . $redirectUrl);
            exit;
        }

        $metadata = $this->settingsMetadata();
        foreach ($settingsToUpdate as $key => $value) {
            $group = $metadata[$key]['group'] ?? 'general';
            $type = $metadata[$key]['type'] ?? 'text';
            Setting::setWithMeta((string) $key, (string) $value, $group, $type);

            // Якщо змінюється активна тема, синхронізуємо з ThemeManager
            if ($key === 'active_theme') {
                \App\Core\Theme\ThemeManager::setActiveTheme($value);
            }

            // Якщо змінюється статус магазину, очищуємо кеш налаштувань
            if ($key === 'store_status') {
                try {
                    $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
                    if (is_dir($cacheDir)) {
                        $entries = @scandir($cacheDir);
                        if (is_array($entries)) {
                            foreach ($entries as $entry) {
                                if ($entry === '.' || $entry === '..') {
                                    continue;
                                }
                                $path = $cacheDir . '/' . $entry;
                                if (is_file($path)) {
                                    @unlink($path);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('Error clearing cache after store_status change: ' . $e->getMessage());
                }
            }
        }

        // 2. Нове збереження для доставки та оплати (таблиця shop_methods)
        if (isset($_POST['methods']) && is_array($_POST['methods'])) {
        foreach ($_POST['methods'] as $id => $data) {
            // Викликаємо метод з Setting.php, який ми створили раніше
            Setting::updateShopMethod((int)$id, $data);
        }
        }

        // 3. Збереження соціальних мереж
        if (isset($_POST['social']) && is_array($_POST['social'])) {
            foreach ($_POST['social'] as $id => $data) {
                \App\Models\SocialLink::updateLink((int)$id, $data);
            }
        }

        $_SESSION['success'] = __('settings_saved_successfully');
        header('Location: ' . $redirectUrl); // Успішне повернення
        exit;
    }

    public function addMethod()
    {
    $this->checkAdmin();
    
    $type = $_POST['type'] ?? 'shipping'; // 'shipping' або 'payment'
    
    // Повертаємо твій масив даних — він важливий для коректного створення запису
    $data = [
        'type'        => $type,
        'code'        => 'custom_' . time(),
        'name'        => ($type === 'shipping') ? __('admin_new_delivery_method') : __('admin_new_payment_method'),
        'description' => __('admin_new_method_description'),
        'is_active'   => 0,
        'sort_order'  => 10,
        'settings'    => json_encode([])
    ];

    // Використовуємо твій метод execute. ВАЖЛИВО: порядок знаків ? має збігатися з array_values($data)
    Setting::execute(
        "INSERT INTO shop_methods (`type`, `code`, `name`, `description`, `is_active`, `sort_order`, `settings`) VALUES (?, ?, ?, ?, ?, ?, ?)",
        array_values($data)
    );

    // Вказуємо правильну назву вкладки для редіректу
    $tab = ($type === 'shipping') ? 'shipping' : 'payment';

    $_SESSION['success'] = __('admin_method_added');
    header('Location: /admin/settings?tab=' . $tab);
    exit;
    }

    public function deleteMethod($id)
    {
    $this->checkAdmin();
    
    // Визначаємо вкладку на основі типу методу, що видаляється
    $type = $_POST['type'] ?? 'shipping'; 
    $tab = ($type === 'shipping') ? 'shipping' : 'payment';

    Setting::execute("DELETE FROM shop_methods WHERE id = ?", [(int)$id]);
    
    $_SESSION['success'] = __("admin_settings_method_deleted");
    header('Location: /admin/settings?tab=' . $tab);
    exit;
    }

    public function updateReview(int $id)
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) { http_response_code(422); $_SESSION['error'] = __('csrf_token_invalid'); header('Location: /admin/settings?tab=reviews'); exit; }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) < 3 || mb_strlen($body) > Review::MAX_BODY_LENGTH) {
            $_SESSION['error'] = __('admin_review_text_length');
            header('Location: /admin/settings?tab=reviews');
            exit;
        }
        Review::updateBody($id, $body);
        $_SESSION['success'] = __('admin_review_updated');
        header('Location: /admin/settings?tab=reviews');
        exit;
    }

    public function deleteReview(int $id)
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) { http_response_code(422); $_SESSION['error'] = __('csrf_token_invalid'); header('Location: /admin/settings?tab=reviews'); exit; }
        Review::deleteById($id);
        $_SESSION['success'] = __('admin_review_deleted');
        header('Location: /admin/settings?tab=reviews');
        exit;
    }

    public function toggleReviewVisibility(int $id)
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) { http_response_code(422); $_SESSION['error'] = __('csrf_token_invalid'); header('Location: /admin/settings?tab=reviews'); exit; }
        $visible = !empty($_POST['is_visible']) ? 1 : 0;
        Review::setVisibility($id, $visible);
        $_SESSION['success'] = $visible ? __('admin_review_unblocked') : __('admin_review_blocked');
        header('Location: /admin/settings?tab=reviews');
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
                return [$settings, __('admin_image_dimensions_negative')];
            }
            $settings[$key] = (string) $value;
        }

        if (isset($settings['media_quality'])) {
            $quality = (int) $settings['media_quality'];
            if ($quality < 10 || $quality > 100) {
                return [$settings, __('admin_image_quality_range')];
            }
            $settings['media_quality'] = (string) $quality;
        }


        if (isset($settings['contact_address'])) {
            $address = trim(strip_tags((string) $settings['contact_address']));
            if (mb_strlen($address) > 250) {
                return [$settings, __('admin_settings_contact_address_too_long')];
            }
            $settings['contact_address'] = $address;
        }

        if (isset($settings['phone_mask'])) {
            $settings['phone_mask'] = normalize_phone_mask((string) $settings['phone_mask']);
            if (!is_valid_phone_mask($settings['phone_mask'])) {
                return [$settings, __('admin_phone_mask_invalid')];
            }
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
            return __('admin_watermark_upload_error');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5242880) {
            return __('admin_watermark_size_error');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, (string) ($file['tmp_name'] ?? '')) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime !== 'image/png') {
            return __('admin_watermark_format_error');
        }

        $dir = __DIR__ . '/../../public/uploads/watermarks/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return __('admin_watermark_dir_error');
        }

        $filename = str_replace('.', '', uniqid('watermark_', true)) . '.png';
        $target = $dir . $filename;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $target)) {
            return __('admin_watermark_save_error');
        }

        $settings['media_watermark_path'] = '/uploads/watermarks/' . $filename;
        return null;
    }

    private function settingsMetadata(): array
    {
        return [
            'site_url'  => ['group' => 'general', 'type' => 'text'],
            'site_name' => ['group' => 'general', 'type' => 'text'],
            'site_description' => ['group' => 'general', 'type' => 'textarea'],
            'store_status' => ['group' => 'general', 'type' => 'select'],
            'maintenance_message' => ['group' => 'general', 'type' => 'textarea'],
            'default_language' => ['group' => 'localization', 'type' => 'select'],
            'default_currency' => ['group' => 'localization', 'type' => 'select'],
            'active_theme' => ['group' => 'appearance', 'type' => 'select'],
            'contact_email' => ['group' => 'contact', 'type' => 'text'],
            'contact_phone' => ['group' => 'contact', 'type' => 'text'],
            'contact_address' => ['group' => 'contact', 'type' => 'text'],
            'phone_mask' => ['group' => 'contact', 'type' => 'text'],
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
            'active_logotype' => ['group' => 'general', 'type' => 'text'],
            'smtp_pass' => ['group' => 'general', 'type' => 'text'],
            'smtp_port' => ['group' => 'general', 'type' => 'text'],
            'smtr' => ['group' => 'general', 'type' => 'text'],
            'google_client_id' => ['group' => 'social_auth', 'type' => 'text'],
            'google_client_secret' => ['group' => 'social_auth', 'type' => 'text'],
            'google_redirect_url' => ['group' => 'social_auth', 'type' => 'text'],
            'google_auth_enabled' => ['group' => 'social_auth', 'type' => 'checkbox'],
            'facebook_client_id' => ['group' => 'social_auth', 'type' => 'text'],
            'facebook_client_secret' => ['group' => 'social_auth', 'type' => 'text'],
            'facebook_redirect_url' => ['group' => 'social_auth', 'type' => 'text'],
            'facebook_auth_enabled' => ['group' => 'social_auth', 'type' => 'checkbox'],
        ];
    }

    private function processLogotypeUpload(array &$settings): ?string
    {
        if (empty($_FILES['logotype_file']) || !is_array($_FILES['logotype_file'])) {
            return null;
        }

        $file = $_FILES['logotype_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return __('admin_logotype_upload_error');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 1048576) {
            return __('admin_logotype_size_error');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, (string) ($file['tmp_name'] ?? '')) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$mime])) {
            return __('admin_logotype_format_error');
        }

        $dir = __DIR__ . '/../../public/uploads/logotypes/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return __('admin_logotype_dir_error');
        }

        $currentPath = trim((string) get_setting('active_logotype', ''));
        if ($currentPath !== '' && strpos($currentPath, '/uploads/logotypes/') === 0) {
            $oldFullPath = __DIR__ . '/../../public' . $currentPath;
            if (is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }

        $filename = str_replace('.', '', uniqid('logotype_', true)) . '.' . $allowedMimes[$mime];
        $target = $dir . $filename;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $target)) {
            return __('admin_logotype_save_error');
        }

        $settings['active_logotype'] = '/uploads/logotypes/' . $filename;
        return null;
    }
}
