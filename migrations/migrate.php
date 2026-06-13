#!/usr/bin/env php
<?php
/**
 * CLI-скрипт для запуску міграцій.
 *
 * Використання:
 *   php migrations/migrate.php          — запустити нові міграції
 *   php migrations/migrate.php status   — показати статус усіх міграцій
 *   php migrations/migrate.php reset create_login_attempts_table.sql — скинути міграцію
 */

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

// Ініціалізація DB
$config = require ROOT_PATH . '/config/database.php';
\App\Core\Database\DB::connect($config['dsn'], $config['user'], $config['pass']);

$runner  = new \App\Core\Database\MigrationRunner();
$command = $argv[1] ?? 'run';

echo "\n=== CoreCommerce Migrations ===\n\n";

switch ($command) {
    case 'status':
        $statuses = $runner->status();
        if (empty($statuses)) {
            echo "Файлів міграцій не знайдено.\n";
            break;
        }
        printf("%-50s %-12s %s\n", 'Міграція', 'Статус', 'Виконано');
        echo str_repeat('-', 80) . "\n";
        foreach ($statuses as $m) {
            $icon = match ($m['status']) {
                'applied'      => '✓',
                'pending'      => '○',
                'missing_file' => '?',
                default        => ' ',
            };
            printf(
                "%s %-48s %-12s %s\n",
                $icon,
                $m['name'],
                $m['status'],
                $m['applied_at'] ?? '—'
            );
        }
        break;

    case 'reset':
        $name = $argv[2] ?? '';
        if ($name === '') {
            echo "Вкажіть назву міграції: php migrate.php reset <filename.sql>\n";
            exit(1);
        }
        $runner->reset($name);
        echo "Міграцію «{$name}» скинуто. Наступний запуск виконає її знову.\n";
        break;

    case 'run':
    default:
        echo "Запуск міграцій...\n\n";
        $result = $runner->run();

        if (!empty($result['run'])) {
            echo "✓ Виконано (" . count($result['run']) . "):\n";
            foreach ($result['run'] as $m) {
                echo "  + $m\n";
            }
            echo "\n";
        }

        if (!empty($result['skipped'])) {
            echo "○ Пропущено (" . count($result['skipped']) . "):\n";
            foreach ($result['skipped'] as $m) {
                echo "  - $m\n";
            }
            echo "\n";
        }

        if (!empty($result['failed'])) {
            echo "✗ Помилки (" . count($result['failed']) . "):\n";
            foreach ($result['failed'] as $name => $error) {
                echo "  ! $name\n    $error\n";
            }
            echo "\n";
            exit(1);
        }

        if (empty($result['run'])) {
            echo "Нових міграцій немає.\n";
        } else {
            echo "Готово!\n";
        }
        break;
}

echo "\n";
