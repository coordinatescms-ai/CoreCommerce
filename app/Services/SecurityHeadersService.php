<?php

namespace App\Services;

use App\Models\Setting;

/**
 * SecurityHeadersService — відправка заголовків безпеки.
 *
 * Порядок увімкнення (важливо не порушувати):
 *   1. Встановити SSL-сертифікат на сервері
 *   2. Увімкнути «Редирект на HTTPS» → перевірити що сайт працює
 *   3. Увімкнути HSTS з коротким TTL (5 хв) → перевірити
 *   4. Збільшити TTL до 1 року → додати в preload-список
 */
class SecurityHeadersService
{
    // ── Публічний метод — викликається з index.php ─────────────────────────

    public static function apply(): void
    {
        // Захист від clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Захист від MIME-sniffing
        header('X-Content-Type-Options: nosniff');

        // XSS-захист (для старих браузерів)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // CSP
        self::applyCSP();

        // HTTPS редирект
        self::applyHttpsRedirect();

        // HSTS (лише якщо HTTPS активний)
        self::applyHSTS();
    }

    // ── CSP ────────────────────────────────────────────────────────────────

    private static function applyCSP(): void
    {
        $mode = (string) Setting::get('csp_mode', 'off');
        if ($mode === 'off') {
            return;
        }

        // Базові директиви
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com fonts.googleapis.com",
            "style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com fonts.googleapis.com fonts.gstatic.com",
            "font-src 'self' fonts.googleapis.com fonts.gstatic.com cdnjs.cloudflare.com data:",
            "img-src 'self' data: blob: *",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        $csp = implode('; ', $directives);

        if ($mode === 'report-only') {
            header("Content-Security-Policy-Report-Only: $csp");
        } else {
            header("Content-Security-Policy: $csp");
        }
    }

    // ── HTTPS редирект ────────────────────────────────────────────────────

    private static function applyHttpsRedirect(): void
    {
        if ((string) Setting::get('https_redirect', '0') !== '1') {
            return;
        }

        // Якщо вже HTTPS — нічого не робимо
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        if (!$isHttps) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $uri  = $_SERVER['REQUEST_URI'] ?? '/';
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: https://' . $host . $uri);
            exit;
        }
    }

    // ── HSTS ──────────────────────────────────────────────────────────────

    private static function applyHSTS(): void
    {
        // HSTS лише якщо https_redirect увімкнено
        if ((string) Setting::get('https_redirect', '0') !== '1') {
            return;
        }
        if ((string) Setting::get('hsts_enabled', '0') !== '1') {
            return;
        }

        // Переконуємось що з'єднання HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        if (!$isHttps) {
            return;
        }

        $maxAge = (int) Setting::get('hsts_max_age', 300);
        $allowedMaxAges = [300, 3600, 86400, 2592000, 31536000];
        if (!in_array($maxAge, $allowedMaxAges, true)) {
            $maxAge = 300; // fallback на безпечний мінімум
        }

        $hsts = "max-age=$maxAge";
        if ((string) Setting::get('hsts_subdomains', '0') === '1') {
            $hsts .= '; includeSubDomains';
        }
        if ($maxAge >= 31536000 && (string) Setting::get('hsts_preload', '0') === '1') {
            $hsts .= '; preload';
        }

        header("Strict-Transport-Security: $hsts");
    }

    // ── Хелпери для view ──────────────────────────────────────────────────

    /**
     * Варіанти терміну дії HSTS для вибору в адмінці.
     */
    public static function hstsMaxAgeOptions(): array
    {
        return [
            300     => __('hsts_age_5min')  ?: 'Тестовий режим — 5 хвилин',
            3600    => __('hsts_age_1h')    ?: '1 година',
            86400   => __('hsts_age_1d')    ?: '1 день',
            2592000 => __('hsts_age_1m')    ?: '1 місяць',
            31536000=> __('hsts_age_1y')    ?: 'Повний захист — 1 рік',
        ];
    }

    /**
     * Варіанти CSP-режиму.
     */
    public static function cspModeOptions(): array
    {
        return [
            'off'         => __('csp_off')         ?: 'Вимкнено',
            'report-only' => __('csp_report_only') ?: 'Тільки звітування (рекомендовано спочатку)',
            'enforce'     => __('csp_enforce')     ?: 'Активний захист',
        ];
    }
}
