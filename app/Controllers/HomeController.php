<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SeoService;

class HomeController
{
    public function index()
    {
        $heroCta = [
            'url' => '/products',
            'label' => __('shop_now'),
        ];

        $popularCategories = Category::query(
            "WITH RECURSIVE category_tree AS (
                SELECT id, parent_id, id as root_id
                FROM categories
                WHERE parent_id IS NULL
                
                UNION ALL
                
                SELECT c.id, c.parent_id, ct.root_id
                FROM categories c
                INNER JOIN category_tree ct ON c.parent_id = ct.id
            )
            SELECT c.*, COUNT(p.id) AS products_count
            FROM categories c
            INNER JOIN category_tree ct ON c.id = ct.root_id
            LEFT JOIN products p ON p.category_id = ct.id AND p.is_visible = 1
            WHERE c.parent_id IS NULL
            GROUP BY c.id
            ORDER BY products_count DESC, c.name ASC
            LIMIT 8"
        ) ?? [];

        $newArrivals = Product::query(
            "SELECT *
             FROM products
             WHERE is_visible = 1
             ORDER BY id DESC
             LIMIT 12"
        ) ?? [];

        $recommendedProducts = Product::query(
            "SELECT p.*, COUNT(oi.id) AS orders_count
             FROM products p
             LEFT JOIN order_items oi ON oi.product_id = p.id
             WHERE p.is_visible = 1
             GROUP BY p.id
             ORDER BY orders_count DESC, p.id DESC
             LIMIT 8"
        ) ?? [];

        if (empty($recommendedProducts)) {
            $recommendedProducts = Product::query(
                "SELECT *
                 FROM products
                 WHERE is_visible = 1
                 ORDER BY id DESC
                 LIMIT 8"
            ) ?? [];
        }

        return View::render('home.index', [
            'heroCta' => $heroCta,
            'popularCategories' => $popularCategories,
            'newArrivals' => attach_stock_status(apply_product_price_filter($newArrivals)),
            'recommendedProducts' => attach_stock_status(apply_product_price_filter($recommendedProducts)),
            'seo' => SeoService::forHome(),
        ]);
    }
}
