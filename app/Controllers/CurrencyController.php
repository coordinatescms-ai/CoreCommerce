<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Http\Csrf;
use App\Services\BankCurrencyService;

class CurrencyController
{
    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    private function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------------------------------------------------------
    // CRUD валют
    // -------------------------------------------------------------------------

    /**
     * POST /admin/currencies/store
     * Додати нову валюту або оновити існуючу.
     */
    public function store(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $_SESSION['error'] = 'CSRF токен недійсний.';
            header('Location: /admin/system');
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $code   = strtoupper(trim((string)($_POST['code']   ?? '')));
        $symbol = trim((string)($_POST['symbol'] ?? ''));
        $rate   = (float)str_replace(',', '.', trim((string)($_POST['rate'] ?? '1')));

        if ($code === '' || $symbol === '') {
            $_SESSION['error'] = 'Код та символ валюти є обов\'язковими.';
            header('Location: /admin/system');
            exit;
        }
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            $_SESSION['error'] = 'Код валюти має складатися з 3 латинських літер (напр. USD).';
            header('Location: /admin/system');
            exit;
        }
        if ($rate <= 0) {
            $_SESSION['error'] = 'Курс валюти має бути більшим за нуль.';
            header('Location: /admin/system');
            exit;
        }

        if ($id > 0) {
            // Редагування
            DB::query(
                'UPDATE currencies SET code = ?, symbol = ?, rate = ? WHERE id = ?',
                [$code, $symbol, $rate, $id]
            );
            $_SESSION['success'] = "Валюту {$code} оновлено.";
        } else {
            // Перевірка на дублікат коду
            $exists = DB::query('SELECT id FROM currencies WHERE code = ?', [$code])
                        ->fetch(\PDO::FETCH_ASSOC);
            if ($exists) {
                $_SESSION['error'] = "Валюта з кодом «{$code}» вже існує.";
                header('Location: /admin/system');
                exit;
            }
            DB::query(
                'INSERT INTO currencies (code, symbol, rate, is_active) VALUES (?, ?, ?, 0)',
                [$code, $symbol, $rate]
            );
            $_SESSION['success'] = "Валюту {$code} додано.";
        }

        header('Location: /admin/system');
        exit;
    }

    /**
     * POST /admin/currencies/delete/{id}
     */
    public function delete(int $id): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $_SESSION['error'] = 'CSRF токен недійсний.';
            header('Location: /admin/system');
            exit;
        }

        $currency = DB::query('SELECT * FROM currencies WHERE id = ?', [$id])
                      ->fetch(\PDO::FETCH_ASSOC);

        if (!$currency) {
            $_SESSION['error'] = 'Валюту не знайдено.';
            header('Location: /admin/system');
            exit;
        }
        if ((int)$currency['is_active'] === 1) {
            $_SESSION['error'] = 'Не можна видалити активну валюту сайту.';
            header('Location: /admin/system');
            exit;
        }

        DB::query('DELETE FROM currencies WHERE id = ?', [$id]);
        $_SESSION['success'] = "Валюту {$currency['code']} видалено.";
        header('Location: /admin/system');
        exit;
    }

    // -------------------------------------------------------------------------
    // Оновлення курсу та перерахунок цін
    // -------------------------------------------------------------------------

    /**
     * POST /admin/currencies/update
     *
     * Очікує POST-поля:
     *   currency_source  — 'manual' | 'api'
     *   target_currency  — код цільової валюти (USD, EUR…)
     *   manual_rate      — курс (тільки якщо source == 'manual')
     */
    public function update(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $_SESSION['error'] = 'CSRF токен недійсний.';
            header('Location: /admin/settings?tab=general');
            exit;
        }

        $source         = $_POST['currency_source']  === 'api' ? 'api' : 'manual';
        $targetCode     = strtoupper(trim((string)($_POST['target_currency'] ?? '')));
        $manualRateRaw  = trim((string)($_POST['manual_rate'] ?? ''));
        $apiKey         = trim((string)($_POST['currency_api_key'] ?? ''));

        if ($targetCode === '') {
            $_SESSION['error'] = 'Не вказано цільову валюту.';
            header('Location: /admin/settings?tab=general');
            exit;
        }

        try {
            DB::beginTransaction();

            // 1. Зберігаємо вибір джерела курсу в settings
            //    Якщо source == 'api' — зберігаємо API-ключ окремо,
            //    значення currency_source = 'api' (або ключ, якщо він переданий)
            if ($source === 'api') {
                // Якщо передали новий ключ — зберігаємо його
                if ($apiKey !== '') {
                    DB::query(
                        "INSERT INTO settings (`key`, `value`, `group`, `type`, updated_at)
                         VALUES ('currency_source', ?, 'currency', 'select', NOW())
                         ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
                        [$apiKey, $apiKey]
                    );
                }
                // Якщо ключ не передали — читаємо поточний з БД
                $storedKey = DB::query(
                    "SELECT `value` FROM settings WHERE `key` = 'currency_source' LIMIT 1"
                )->fetchColumn();

                if (empty($storedKey) || $storedKey === 'manual') {
                    throw new \RuntimeException(
                        'API-ключ НБУ не заповнено. Вкажіть ключ у полі «API-ключ НБУ».'
                    );
                }
                $apiKey = $storedKey;
            } else {
                // manual — зберігаємо просто 'manual'
                DB::query(
                    "INSERT INTO settings (`key`, `value`, `group`, `type`, updated_at)
                     VALUES ('currency_source', 'manual', 'currency', 'select', NOW())
                     ON DUPLICATE KEY UPDATE `value` = 'manual', updated_at = NOW()"
                );
            }

            // 2. Поточна активна валюта (до зміни)
            $activeCurrency = DB::query(
                'SELECT * FROM currencies WHERE is_active = 1 LIMIT 1'
            )->fetch(\PDO::FETCH_ASSOC);

            if (!$activeCurrency) {
                throw new \RuntimeException('В базі немає активної валюти.');
            }

            $oldRateInUah = (float)$activeCurrency['rate'];

            // 3. Цільова валюта
            $targetCurrency = DB::query(
                'SELECT * FROM currencies WHERE code = ?', [$targetCode]
            )->fetch(\PDO::FETCH_ASSOC);

            if (!$targetCurrency) {
                throw new \RuntimeException("Валюту «{$targetCode}» не знайдено в базі.");
            }

            // 4. Визначаємо новий курс
            if ($source === 'api') {
                $service      = new BankCurrencyService();
                $newRateInUah = $service->fetchRate($targetCode, $apiKey);
            } else {
                $newRateInUah = (float)str_replace(',', '.', $manualRateRaw);
                if ($newRateInUah <= 0) {
                    throw new \RuntimeException('Курс валюти має бути більшим за нуль.');
                }
            }

            // 5. Оновлюємо курс у таблиці currencies
            DB::query(
                'UPDATE currencies SET rate = ? WHERE code = ?',
                [$newRateInUah, $targetCode]
            );

            // 6. Перерахунок цін (крос-курс)
            // coefficient = oldRate / newRate
            // Якщо була UAH (1.0) → USD (41.5): 1/41.5 = 0.024 — ціни зменшуються
            // Якщо була USD (41.5) → UAH (1.0): 41.5/1 = 41.5 — ціни збільшуються
            $coefficient = $oldRateInUah / $newRateInUah;

            DB::query(
                'UPDATE products SET price = ROUND(price * ?, 2)',
                [$coefficient]
            );

            // 7. Перемикаємо is_active
            DB::query('UPDATE currencies SET is_active = 0');
            DB::query(
                'UPDATE currencies SET is_active = 1 WHERE code = ?',
                [$targetCode]
            );

            // 8. Синхронізуємо setting default_currency
            DB::query(
                "INSERT INTO settings (`key`, `value`, `group`, `type`, updated_at)
                 VALUES ('default_currency', ?, 'localization', 'select', NOW())
                 ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
                [$targetCode, $targetCode]
            );

            DB::commit();

            $_SESSION['success'] = sprintf(
                'Валюту змінено на %s. Курс: 1 %s = %.4f UAH. Ціни перераховано (коефіцієнт: %.6f).',
                $targetCode,
                $targetCode,
                $newRateInUah,
                $coefficient
            );

        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            $_SESSION['error'] = 'Помилка перерахунку: ' . $e->getMessage();
        }

        header('Location: /admin/settings?tab=general');
        exit;
    }
}