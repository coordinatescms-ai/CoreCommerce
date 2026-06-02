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
        $recentOrders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
            'orders_count' => Setting::count_order(),
            'products_count' => Setting::count_products(),
            'total_sales' => Setting::total_sales()
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

        $labels = [];
        $values = [];
        $counts = [];
        $popular_products = [];
        $low_stock_products = [];

        // Масиви для перекладу
        $months_ua = ['01'=>'Січ','02'=>'Лют','03'=>'Бер','04'=>'Квіт','05'=>'Трав','06'=>'Черв','07'=>'Лип','08'=>'Серп','09'=>'Вер','10'=>'Жовт','11'=>'Лист','12'=>'Груд'];
        $days_ua = [0=>'Нд', 1=>'Пн', 2=>'Вв', 3=>'Ср', 4=>'Чт', 5=>'Пт', 6=>'Сб'];

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

            $title_text = 'Продажі за ' . date('d.m.Y', strtotime($date_from))
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
                $labels[] = $group_by_month
                    ? ($months_ua[$row['m_num']] . ' ' . substr($row['period_label'], 3))
                    : $row['day_label'];
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
                    $title_text = 'Продажі за останні 12 місяців';
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
                    $title_text = 'Продажі за останні 30 днів';
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
                    $title_text = 'Продажі за поточний тиждень';
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
                    $labels[] = $months_ua[$row['m_num']];
                } elseif ($period === 'month') {
                    $labels[] = $row['day_label'];
                } else {
                    $labels[] = $days_ua[$row['d_idx']] . ' (' . $row['d_date'] . ')';
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
            $labels = ['Немає даних'];
            $values = [0];
            $counts = [0];
        }

        // Експорт в CSV (враховує і довільний діапазон)
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $filename = 'analytics_' . $period . '_' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['Період', 'Замовлень', 'Сума виручки (₴)', 'Частка (%)']);

            $total_sum = array_sum($values);
            foreach ($labels as $key => $label) {
                $val     = $values[$key];
                $count   = $counts[$key];
                $percent = $total_sum > 0 ? round(($val / $total_sum) * 100, 1) : 0;
                fputcsv($output, [
                    strip_tags($label),
                    $count . ' шт.',
                    number_format($val, 2, '.', ''),
                    $percent . '%',
                ]);
            }
            $total_orders = array_sum($counts);
            fputcsv($output, ['РАЗОМ', $total_orders . ' шт.', number_format($total_sum, 2, '.', ''), '100%']);
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
            $_SESSION['error'] = 'CSRF token validation failed';
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
                    $errors[] = 'Не вдалося видалити файл кешу: ' . basename($file);
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
                        $errors[] = 'Не вдалося видалити файл кешу: ' . $entry;
                    }
                }
            } else {
                $errors[] = 'Не вдалося прочитати cache-директорію.';
            }
        }

        try {
            Setting::set('asset_version', (string) time(), 'system', 'text');
            $cleared[] = 'asset_version(setting)';
        } catch (\Throwable $e) {
            $errors[] = 'Не вдалося оновити asset version: ' . $e->getMessage();
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
            $_SESSION['success'] = 'Кеш успішно очищено.';
        } else {
            $_SESSION['error'] = 'Кеш очищено частково: ' . implode(' ', $errors);
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

        View::render('admin/system', [
            'systemInfo' => $systemInfo,
            'logs' => $logs,
            'cronTasks' => $cronTasks,
            'environment' => $environment,
        ], 'admin');
    }

    public function saveSystemEnvironment()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = 'CSRF token validation failed';
            header('Location: /admin/system');
            exit;
        }

        $debugMode = isset($_POST['display_errors']) ? '1' : '0';
        $maintenanceMode = isset($_POST['maintenance_mode']) ? 'closed' : 'open';
        Setting::setWithMeta('display_errors', $debugMode, 'system', 'checkbox');
        Setting::setWithMeta('store_status', $maintenanceMode, 'general', 'select');

        @ini_set('display_errors', $debugMode);
        $_SESSION['success'] = 'Системні режими оновлено.';
        header('Location: /admin/system');
        exit;
    }

    public function sendSystemTestEmail()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = 'CSRF token validation failed';
            header('Location: /admin/system');
            exit;
        }

        $to = trim((string) ($_POST['test_email'] ?? ''));
        $useDb = isset($_POST['test_email_use_db']);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Вкажіть коректний email для тестового листа.';
            header('Location: /admin/system');
            exit;
        }

        $subject = 'Тест листа CoreCommerce';
        $body = 'Це тестовий лист для перевірки Mail Settings (' . ($useDb ? 'DB settings' : 'config/mail.php') . ').';
        $sent = $useDb ? (new MailService())->send($to, $subject, nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'))) : $this->sendMailFromFileConfig($to, $subject, $body);

        $_SESSION[$sent ? 'success' : 'error'] = $sent ? 'Тестовий лист успішно відправлено.' : 'Не вдалося відправити тестовий лист. Перевірте SMTP налаштування.';
        $this->logAdminAction('system_test_mail', ['to' => $to, 'source' => $useDb ? 'db' : 'file', 'success' => $sent]);
        header('Location: /admin/system');
        exit;
    }

    public function backupDatabase()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = 'CSRF token validation failed';
            header('Location: /admin/system');
            exit;
        }

        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0775, true)) {
            $_SESSION['error'] = 'Не вдалося створити папку для резервних копій.';
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
                        return $v === null ? 'NULL' : DB::$pdo->quote((string) $v);
                    }, array_values($row));
                    $dump .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
                }
                $dump .= "\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (@file_put_contents($filePath, $dump) === false) {
            $_SESSION['error'] = 'Не вдалося записати SQL-дамп.';
        } else {
            $_SESSION['success'] = 'Резервну копію створено: ' . basename($filePath);
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
            $_SESSION['error'] = 'CSRF token validation failed';
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
        $_SESSION['success'] = 'Оптимізацію таблиць завершено. Успішно: ' . count($optimized);
        if (!empty($errors)) {
            $_SESSION['error'] = 'Частина таблиць не оптимізована: ' . implode('; ', $errors);
        }

        header('Location: /admin/system');
        exit;
    }

    public function clearLogs()
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) {
            http_response_code(422);
            $_SESSION['error'] = 'CSRF token validation failed';
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
        $_SESSION['success'] = 'Логи очищено. Видалено файлів: ' . count($deleted);
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

    private function sendMailFromFileConfig(string $to, string $subject, string $body): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) ($config['host'] ?? '');
            $mail->SMTPAuth = true;
            $mail->Username = (string) ($config['username'] ?? '');
            $mail->Password = (string) ($config['password'] ?? '');
            $mail->SMTPSecure = (string) ($config['encryption'] ?? 'tls');
            $mail->Port = (int) ($config['port'] ?? 587);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom((string) ($config['from_email'] ?? 'admin@example.com'), (string) ($config['from_name'] ?? 'CoreCommerce'));
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
            $mail->AltBody = $body;
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            return false;
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
                'current_version' => $config['current_version']
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
            $_SESSION['error'] = 'CSRF token validation failed';
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
    }

    // 2. Нове збереження для доставки та оплати (таблиця shop_methods)
    if (isset($_POST['methods']) && is_array($_POST['methods'])) {
        foreach ($_POST['methods'] as $id => $data) {
            // Викликаємо метод з Setting.php, який ми створили раніше
            Setting::updateShopMethod((int)$id, $data);
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
        'name'        => ($type === 'shipping') ? 'Новий метод доставки' : 'Новий метод оплати',
        'description' => 'Опис методу для клієнта',
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

    $_SESSION['success'] = 'Метод додано. Тепер налаштуйте його.';
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
    
    $_SESSION['success'] = 'Метод видалено';
    header('Location: /admin/settings?tab=' . $tab);
    exit;
    }

    public function updateReview(int $id)
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) { http_response_code(422); $_SESSION['error'] = 'CSRF token validation failed'; header('Location: /admin/settings?tab=reviews'); exit; }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) < 3 || mb_strlen($body) > Review::MAX_BODY_LENGTH) {
            $_SESSION['error'] = 'Текст від 3 до 2000 символів.';
            header('Location: /admin/settings?tab=reviews');
            exit;
        }
        Review::updateBody($id, $body);
        $_SESSION['success'] = 'Відгук оновлено';
        header('Location: /admin/settings?tab=reviews');
        exit;
    }

    public function deleteReview(int $id)
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) { http_response_code(422); $_SESSION['error'] = 'CSRF token validation failed'; header('Location: /admin/settings?tab=reviews'); exit; }
        Review::deleteById($id);
        $_SESSION['success'] = 'Відгук видалено';
        header('Location: /admin/settings?tab=reviews');
        exit;
    }

    public function toggleReviewVisibility(int $id)
    {
        $this->checkAdmin();
        if (!Csrf::isValid()) { http_response_code(422); $_SESSION['error'] = 'CSRF token validation failed'; header('Location: /admin/settings?tab=reviews'); exit; }
        $visible = !empty($_POST['is_visible']) ? 1 : 0;
        Review::setVisibility($id, $visible);
        $_SESSION['success'] = $visible ? 'Відгук розблоковано' : 'Відгук заблоковано';
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


        if (isset($settings['phone_mask'])) {
            $settings['phone_mask'] = normalize_phone_mask((string) $settings['phone_mask']);
            if (!is_valid_phone_mask($settings['phone_mask'])) {
                return [$settings, 'Маска телефону некоректна. Використайте формат на кшталт +38 (###) ###-##-##'];
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
            return 'Помилка завантаження логотипу.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 1048576) {
            return 'Логотип має бути розміром до 1MB.';
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
            return 'Логотип має бути у форматі JPG, PNG або WEBP.';
        }

        $dir = __DIR__ . '/../../public/uploads/logotypes/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return 'Не вдалося створити папку для логотипів.';
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
            return 'Не вдалося зберегти логотип.';
        }

        $settings['active_logotype'] = '/uploads/logotypes/' . $filename;
        return null;
    }
}
