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
            'Disallow: /admin/',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /search?',
            'Allow: /search$',
            '',
        ];

        if ($siteUrl !== '') {
            $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        }

        echo implode("\n", $lines) . "\n";
    }
}
