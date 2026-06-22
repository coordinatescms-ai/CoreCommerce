<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Services\SearchService;

class SearchController
{
    public function index(): void
    {
        $rawQuery = trim((string) ($_GET['q'] ?? ''));
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $limit    = 20;

        // XSS: виводимо тільки через htmlspecialchars
        $safeQuery = htmlspecialchars($rawQuery, ENT_QUOTES, 'UTF-8');

        if ($rawQuery === '') {
            View::render('search.index', [
                'results'        => [],
                'total'          => 0,
                'page'           => 1,
                'pages'          => 0,
                'query'          => '',
                'safeQuery'      => '',
                'tokens'         => [],
                'suggestion'     => null,
                'popularQueries' => SearchService::getPopularQueries(),
                'seo'            => [
                    'meta_title'       => __('search') . ' — ' . get_setting('site_name', ''),
                    'meta_description' => '',
                ],
            ]);
            return;
        }

        $data = SearchService::search($rawQuery, $page, $limit);

        View::render('search.index', array_merge($data, [
            'safeQuery'      => $safeQuery,
            'popularQueries' => $data['total'] === 0
                ? SearchService::getPopularQueries()
                : [],
            'seo'            => [
                'meta_title'       => $safeQuery . ' — ' . __('search') . ' — ' . get_setting('site_name', ''),
                'meta_description' => '',
                'robots'           => 'noindex,follow',
            ],
        ]));
    }

    /**
     * AJAX автодоповнення — повертає JSON.
     */
    public function autocomplete(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $rawQuery = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($rawQuery) < 2) {
            echo json_encode([]);
            return;
        }

        $data    = SearchService::search($rawQuery, 1, 5);
        $results = array_map(fn($r) => [
            'id'    => $r['id'],
            'name'  => $r['name'],
            'price' => format_price($r['price']),
            'image' => !empty($r['image']) ? product_image_variant_path($r['image'], 'thumb') : null,
            'url'   => '/product/' . $r['slug'],
        ], $data['results']);

        echo json_encode($results, JSON_UNESCAPED_UNICODE);
    }
}
