<?php

namespace App\Services;

use App\Core\Database\DB;

/**
 * Захист від brute-force атак на форму входу.
 *
 * Логіка блокування (два незалежних ліміти):
 *   — По IP:    MAX_ATTEMPTS_IP    невдалих спроб за WINDOW_MINUTES хвилин
 *   — По email: MAX_ATTEMPTS_EMAIL невдалих спроб за WINDOW_MINUTES хвилин
 *
 * Якщо хоча б один ліміт перевищено — вхід заблоковано.
 * Після успішного входу лічильник для цього IP+email обнуляється.
 */
class LoginRateLimiter
{
    // Максимальна кількість невдалих спроб за вікно часу
    private const MAX_ATTEMPTS_IP    = 10;
    private const MAX_ATTEMPTS_EMAIL = 5;

    // Вікно в хвилинах
    private const WINDOW_MINUTES = 15;

    // Час блокування в хвилинах (після перевищення ліміту)
    private const BLOCK_MINUTES = 30;

    // Зберігати записи не довше (для автоочистки)
    private const CLEANUP_AFTER_HOURS = 24;

    private string $ip;

    public function __construct()
    {
        $this->ip = $this->resolveIp();
    }

    /**
     * Перевірити чи заблокований поточний IP або email.
     * Повертає масив з інформацією або null якщо все ок.
     *
     * @return array{blocked: true, by: string, retry_after: int}|null
     */
    public function check(string $email): ?array
    {
        $windowStart = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);

        // Перевірка по IP
        $attemptsIp = (int) DB::query(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND success = 0 AND created_at >= ?",
            [$this->ip, $windowStart]
        )->fetchColumn();

        if ($attemptsIp >= self::MAX_ATTEMPTS_IP) {
            return [
                'blocked'     => true,
                'by'          => 'ip',
                'retry_after' => $this->retryAfter($this->ip, null),
            ];
        }

        // Перевірка по email
        $attemptsEmail = (int) DB::query(
            "SELECT COUNT(*) FROM login_attempts
             WHERE email = ? AND success = 0 AND created_at >= ?",
            [mb_strtolower(trim($email)), $windowStart]
        )->fetchColumn();

        if ($attemptsEmail >= self::MAX_ATTEMPTS_EMAIL) {
            return [
                'blocked'     => true,
                'by'          => 'email',
                'retry_after' => $this->retryAfter(null, $email),
            ];
        }

        return null;
    }

    /**
     * Записати невдалу спробу входу.
     */
    public function recordFailure(string $email): void
    {
        DB::query(
            "INSERT INTO login_attempts (ip, email, success, created_at)
             VALUES (?, ?, 0, NOW())",
            [$this->ip, mb_strtolower(trim($email))]
        );

        $this->cleanup();
    }

    /**
     * Записати успішний вхід та очистити невдалі спроби для цього IP+email.
     */
    public function recordSuccess(string $email): void
    {
        DB::query(
            "INSERT INTO login_attempts (ip, email, success, created_at)
             VALUES (?, ?, 1, NOW())",
            [$this->ip, mb_strtolower(trim($email))]
        );

        // Обнуляємо невдалі спроби для цієї пари IP+email
        DB::query(
            "DELETE FROM login_attempts
             WHERE ip = ? AND email = ? AND success = 0",
            [$this->ip, mb_strtolower(trim($email))]
        );
    }

    /**
     * Скільки невдалих спроб залишилось до блокування (для UI).
     */
    public function remainingAttempts(string $email): array
    {
        $windowStart = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);

        $byIp = (int) DB::query(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND success = 0 AND created_at >= ?",
            [$this->ip, $windowStart]
        )->fetchColumn();

        $byEmail = (int) DB::query(
            "SELECT COUNT(*) FROM login_attempts
             WHERE email = ? AND success = 0 AND created_at >= ?",
            [mb_strtolower(trim($email)), $windowStart]
        )->fetchColumn();

        return [
            'by_ip'    => max(0, self::MAX_ATTEMPTS_IP    - $byIp),
            'by_email' => max(0, self::MAX_ATTEMPTS_EMAIL - $byEmail),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Порахувати через скільки секунд закінчиться блокування.
     */
    private function retryAfter(?string $ip, ?string $email): int
    {
        if ($ip !== null) {
            $row = DB::query(
                "SELECT created_at FROM login_attempts
                 WHERE ip = ? AND success = 0
                 ORDER BY created_at ASC
                 LIMIT 1",
                [$ip]
            )->fetch(\PDO::FETCH_ASSOC);
        } else {
            $row = DB::query(
                "SELECT created_at FROM login_attempts
                 WHERE email = ? AND success = 0
                 ORDER BY created_at ASC
                 LIMIT 1",
                [mb_strtolower(trim($email ?? ''))]
            )->fetch(\PDO::FETCH_ASSOC);
        }

        if (!$row) {
            return self::BLOCK_MINUTES * 60;
        }

        $unlocksAt = strtotime($row['created_at']) + self::BLOCK_MINUTES * 60;
        return max(0, $unlocksAt - time());
    }

    /**
     * Видалити старі записи (запускається з імовірністю 5% при кожному запиті).
     */
    private function cleanup(): void
    {
        if (random_int(1, 20) !== 1) {
            return;
        }

        DB::query(
            "DELETE FROM login_attempts
             WHERE created_at < ?",
            [date('Y-m-d H:i:s', time() - self::CLEANUP_AFTER_HOURS * 3600)]
        );
    }

    /**
     * Визначити реальний IP-адрес клієнта.
     * Враховує проксі/балансувальники.
     */
    private function resolveIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP']  ?? '', // Cloudflare
            $_SERVER['HTTP_X_REAL_IP']          ?? '', // nginx proxy
            $_SERVER['HTTP_X_FORWARDED_FOR']    ?? '', // load balancer (перший IP)
            $_SERVER['REMOTE_ADDR']             ?? '',
        ];

        foreach ($candidates as $candidate) {
            // X-Forwarded-For може містити кілька IP через кому
            $ip = trim(explode(',', $candidate)[0]);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
