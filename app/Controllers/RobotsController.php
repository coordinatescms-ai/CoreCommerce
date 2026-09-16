<?php

namespace App\Controllers;

use App\Models\Setting;

/**
 * RobotsController — динамічна генерація robots.txt.
 *
 * Sitemap-посилання формується з поточного site_url,
 * тому не потребує ручного редагування при зміні домену.
 */
class RobotsController
{
    public function index(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        $siteUrl = rtrim((string) Setting::get('site_url', ''), '/');

        $lines = [
            'User-agent: *',

            // Кошик і порівняння — особисті/сесійні дані, без унікального SEO-контенту
            'Disallow: /cart',
            'Disallow: /compare',
            'Disallow: /checkout',
            'Disallow: /order-success/',

            // Акаунт користувача та автентифікація — приватне, індексувати нема сенсу
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password/', // містить одноразовий токен у URL
            'Disallow: /verify-email/',   // так само токен у URL
            'Disallow: /auth/',           // OAuth-редиректи (Google/Facebook), не сторінки контенту

            // Технічні/сервісні маршрути — не контент, індексувати не треба
            'Disallow: /theme',              // перемикач теми сайту
            'Disallow: /language/',          // перемикач мови (сам контент вже доступний за звичайними URL)
            'Disallow: /logistics/',         // AJAX-пошук відділень служб доставки
            'Disallow: /search/autocomplete',// AJAX-підказки пошуку
            'Disallow: /prom/feed.xml',      // машинний фід для Prom.ua, не для пошукових ботів

            // Результати пошуку з параметрами — тонкий/дублікатний контент;
            // сама сторінка пошуку (без параметрів) лишається доступною
            'Disallow: /search?',
            'Allow: /search$',

            // Адмін-панель — завжди закрита від сканування
            'Disallow: /admin/',
            '',
        ];

        if ($siteUrl !== '') {
            $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        }

        echo implode("\n", $lines) . "\n";
    }
}
