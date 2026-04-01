<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Database\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Services\SlugHelper;
use App\Services\ProductFilterService;

class ProductController
{
    /**
     * Показати список товарів
     */
    public function index()
    {
        $page = $_GET['page'] ?? 1;
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Отримати товари
        $products = Product::query(
            "SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
        $products = !empty($products) ? $products : [];

        // Отримати загальну кількість товарів
        $totalResult = Product::query("SELECT COUNT(*) as count FROM products");
        $total = !empty($totalResult) ? $totalResult[0]['count'] : 0;
        $pages = ceil($total / $limit);

        // Отримати категорії для навігації
        $categories = Category::getTree();

        return View::render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit
        ]);
    }

    /**
     * Показати товар за slug
     * 
     * @param string $slug
     */
    public function show($slug)
    {
        // Перевірити, чи є редирект для цього slug
        $redirect = SlugHelper::getRedirect($slug, 'product');
        
        if ($redirect) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: /product/" . $redirect['new_slug']);
            exit;
        }

        // Отримати товар за slug
        $product = Product::findBySlug($slug);
        
        if (!$product) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        // Отримати атрибути товару
        $attributes = ProductAttribute::getByProduct($product['id']);

        // Отримати SEO-налаштування
        $seoSettings = Product::getSeoSettings($product['id']);

        // Отримати категорію та хлібні крихти
        $category = null;
        $breadcrumbs = [];
        
        if ($product['category_id']) {
            $category = Category::findById($product['category_id']);
            $breadcrumbs = Category::getBreadcrumbs($product['category_id']);
        }

        return View::render('products/show', [
            'product' => $product,
            'attributes' => $attributes,
            'category' => $category,
            'breadcrumbs' => $breadcrumbs,
            'seoSettings' => $seoSettings
        ]);
    }

    /**
     * Показати категорію за slug
     * 
     * @param string $slug
     */
    public function showCategory($slug)
    {
        // Перевірити, чи є редирект для цього slug
        $redirect = SlugHelper::getRedirect($slug, 'category');
        
        if ($redirect) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: /category/" . $redirect['new_slug']);
            exit;
        }

        // Отримати категорію за slug
        $category = Category::findBySlug($slug);
        
        if (!$category) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        // Розпарсити фільтри з URL
        $filters = ProductFilterService::parseFiltersFromUrl($_GET);
        $filters['category_id'] = $category['id'];
        $filters['limit'] = $_GET['limit'] ?? 12;
        $filters['offset'] = (($_GET['page'] ?? 1) - 1) * $filters['limit'];
        $filters['sort_by'] = $_GET['sort_by'] ?? 'name';
        $filters['sort_order'] = $_GET['sort_order'] ?? 'ASC';

        // Отримати відфільтровані товари
        $products = ProductFilterService::filter($filters);
        $totalProducts = ProductFilterService::count($filters);
        $pages = ceil($totalProducts / $filters['limit']);

        // Отримати опції фільтрів
        $filterOptions = ProductFilterService::getFilterOptions($category['id'], $filters);
        $priceRange = ProductFilterService::getPriceRange($category['id']);

        // Отримати хлібні крихти
        $breadcrumbs = Category::getBreadcrumbs($category['id']);
        
        // Додати поточну категорію до хлібних крихт
        $breadcrumbs[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'url' => '/category/' . $category['slug']
        ];

        // Отримати дочірні категорії
        $childCategories = Category::getChildren($category['id']);

        // Отримати SEO-налаштування
        $seoSettings = Category::getSeoSettings($category['id']);

        return View::render('products/category', [
            'category' => $category,
            'products' => $products,
            'totalProducts' => $totalProducts,
            'page' => $_GET['page'] ?? 1,
            'pages' => $pages,
            'filterOptions' => $filterOptions,
            'priceRange' => $priceRange,
            'currentFilters' => $filters,
            'breadcrumbs' => $breadcrumbs,
            'childCategories' => $childCategories,
            'seoSettings' => $seoSettings
        ]);
    }
}
