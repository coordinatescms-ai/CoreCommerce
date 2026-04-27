<?php
namespace App\Controllers;

use App\Models\Page;
use App\Core\View\View;

class PageController 
{
    public function show($slug) 
    {
        $pageModel = new Page();
        $page = $pageModel->getBySlug($slug);

        // Якщо сторінки немає або вона не опублікована — 404
        if (!$page || !$page['is_active']) {
            header("HTTP/1.0 404 Not Found");
            View::render('errors/404'); // або просто die('Сторінку не знайдено');
            exit;
        }

        // Виводимо сторінку, використовуючи загальний шаблон сайту
        View::render('site/static_page', [
            'page' => $page
        ]);
    }
}
