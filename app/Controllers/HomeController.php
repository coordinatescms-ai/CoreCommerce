<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\Category;
use App\Models\Product;

class HomeController
{
    public function index()
    {
        $topCategories = Category::query(
            "SELECT c.*, COUNT(p.id) AS products_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id
             ORDER BY products_count DESC, c.name ASC
             LIMIT 6"
        ) ?? [];

        $newArrivals = Product::query(
            "SELECT *
             FROM products
             ORDER BY id DESC
             LIMIT 8"
        ) ?? [];

        $popularProducts = Product::query(
            "SELECT p.*, COUNT(oi.id) AS orders_count
             FROM products p
             LEFT JOIN order_items oi ON oi.product_id = p.id
             GROUP BY p.id
             ORDER BY orders_count DESC, p.id DESC
             LIMIT 8"
        ) ?? [];

        if (empty($popularProducts)) {
            $popularProducts = Product::query(
                "SELECT *
                 FROM products
                 ORDER BY id DESC
                 LIMIT 8"
            ) ?? [];
        }

        return View::render('home.index', [
            'topCategories' => $topCategories,
            'newArrivals' => $newArrivals,
            'popularProducts' => $popularProducts,
        ]);
    }
}
