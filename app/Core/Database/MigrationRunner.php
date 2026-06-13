<?php

namespace App\Core\Database;

/**
 * Система міграцій бази даних.
 *
 * Принцип роботи:
 *  — Кожен .sql файл у папці migrations/ виконується рівно один раз
 *  — Виконані міграції записуються в таблицю `migrations`
 *  — Повторний запуск безпечний — вже виконані пропускаються
 *  — Порядок виконання: алфавітний (тому файли краще нумерувати: 001_..., 002_...)
 *
 * Запуск з адмінки: POST /admin/migrations/run
 * Або через CLI:   php migrations/migrate.php
 */
class MigrationRunner
{
    private string $migrationsPath;
    private string $logFile;

    public function __construct()
    {
        $this->migrationsPath = dirname(__DIR__, 3) . '/migrations';
        $this->logFile        = dirname(__DIR__, 3) . '/storage/logs/migrations.log';
    }

    /**
     * Запустити всі нові міграції.
     *
     * @return array{run: string[], skipped: string[], failed: array<string,string>}
     */
    public function run(): array
    {
        $this->ensureMigrationsTable();

        $files   = $this->getPendingMigrations();
        $run     = [];
        $skipped = [];
        $failed  = [];

        foreach ($files as $file) {
            $name = basename($file);

            if ($this->isApplied($name)) {
                $skipped[] = $name;
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                $skipped[] = $name . ' (порожній файл)';
                continue;
            }

            try {
                DB::$pdo->beginTransaction();
                $this->executeSql($sql);
                $this->markApplied($name);
                DB::$pdo->commit();

                $run[] = $name;
                $this->log("OK: $name");

            } catch (\Throwable $e) {
                if (DB::$pdo->inTransaction()) {
                    DB::$pdo->rollBack();
                }
                $failed[$name] = $e->getMessage();
                $this->log("FAIL: $name | " . $e->getMessage());
            }
        }

        return compact('run', 'skipped', 'failed');
    }

    /**
     * Показати статус усіх міграцій.
     *
     * @return array<array{name: string, status: string, applied_at: string|null}>
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();

        $applied = DB::query(
            'SELECT migration, applied_at FROM migrations ORDER BY applied_at ASC'
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        $files  = $this->getAllMigrationFiles();
        $result = [];

        foreach ($files as $file) {
            $name = basename($file);
            $result[] = [
                'name'       => $name,
                'status'     => isset($applied[$name]) ? 'applied' : 'pending',
                'applied_at' => $applied[$name] ?? null,
            ];
        }

        // Додаємо застосовані що більше не мають файлів
        foreach ($applied as $name => $appliedAt) {
            if (!in_array($name, array_column($result, 'name'), true)) {
                $result[] = [
                    'name'       => $name,
                    'status'     => 'missing_file',
                    'applied_at' => $appliedAt,
                ];
            }
        }

        return $result;
    }

    /**
     * Скинути конкретну міграцію (дозволяє перезапустити її).
     */
    public function reset(string $name): bool
    {
        DB::query('DELETE FROM migrations WHERE migration = ?', [$name]);
        $this->log("RESET: $name");
        return true;
    }

    // ── Приватні методи ───────────────────────────────────────────────────────

    /**
     * Створити таблицю migrations якщо не існує.
     */
    private function ensureMigrationsTable(): void
    {
        DB::exec(
            "CREATE TABLE IF NOT EXISTS `migrations` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migration`  VARCHAR(255) NOT NULL,
                `applied_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_migration` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Отримати файли міграцій що ще не виконані.
     */
    private function getPendingMigrations(): array
    {
        $all     = $this->getAllMigrationFiles();
        $applied = DB::query('SELECT migration FROM migrations')
                     ->fetchAll(\PDO::FETCH_COLUMN);

        return array_filter(
            $all,
            static fn(string $f) => !in_array(basename($f), $applied, true)
        );
    }

    /**
     * Отримати всі .sql файли з папки migrations/ (відсортовані).
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.sql') ?: [];
        sort($files);
        return $files;
    }

    private function isApplied(string $name): bool
    {
        return (bool) DB::query(
            'SELECT id FROM migrations WHERE migration = ? LIMIT 1',
            [$name]
        )->fetch();
    }

    private function markApplied(string $name): void
    {
        DB::query(
            'INSERT INTO migrations (migration, applied_at) VALUES (?, NOW())',
            [$name]
        );
    }

    /**
     * Виконати SQL-файл: розбиваємо на окремі statements по крапці з комою.
     * Ігноруємо порожні statements та коментарі.
     */
    private function executeSql(string $sql): void
    {
        // Прибираємо коментарі -- та # (рядкові)
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('/^#.*$/m', '', $sql);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            static fn(string $s) => $s !== ''
        );

        foreach ($statements as $statement) {
            DB::exec($statement);
        }
    }

    private function log(string $message): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $this->logFile,
            sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message),
            FILE_APPEND | LOCK_EX
        );
    }
}
