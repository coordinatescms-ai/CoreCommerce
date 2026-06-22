<?php

namespace App\Controllers;

use App\Models\Page;
use App\Core\View\View;
use App\Services\SeoService;

class PageController
{
    public function show($slug)
    {
        $pageModel = new Page();
        $page = $pageModel->getBySlug($slug);

        if (!$page || !$page['is_active']) {
            header('HTTP/1.0 404 Not Found');
            View::render('errors/404');
            exit;
        }

        View::render('site/static_page', [
            'page' => $page,
            'seo'  => SeoService::forPage($page),
        ]);
    }
}

