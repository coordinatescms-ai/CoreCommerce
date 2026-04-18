<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Database\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttributeValue;
use App\Models\ProductImage;
use App\Services\SlugHelper;
use App\Services\ProductFilterService;

class ProductController
{
    /**
     * Підготувати дані сторінки категорії.
     *
     * @param array $category
     * @param array $queryParams
     * @return array
     */
    private function buildCategoryPageData(array $category, array $queryParams): array
    {
        $filters = ProductFilterService::parseFiltersFromUrl($queryParams);
        $filters['category_id'] = $category['id'];
        $filters['limit'] = (int) ($queryParams['limit'] ?? 12);
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $filters['offset'] = ($page - 1) * $filters['limit'];
        $filters['sort_by'] = $queryParams['sort_by'] ?? 'name';
        $filters['sort_order'] = $queryParams['sort_order'] ?? 'ASC';

        $products = ProductFilterService::filter($filters);
        $totalProducts = ProductFilterService::count($filters);
        $pages = max(1, (int) ceil($totalProducts / max(1, $filters['limit'])));
        $filterOptions = ProductFilterService::getFilterOptions($category['id'], $filters);
        $priceRange = ProductFilterService::getPriceRange($category['id']);
        $breadcrumbs = Category::getBreadcrumbs($category['id']);

        return [
            'category' => $category,
            'products' => $products,
            'totalProducts' => $totalProducts,
            'page' => $page,
            'pages' => $pages,
            'filterOptions' => $filterOptions,
            'priceRange' => $priceRange,
            'currentFilters' => $filters,
            'breadcrumbs' => $breadcrumbs,
            'categoryTree' => Category::getTree(),
            'childCategories' => Category::getChildren($category['id']),
            'seoSettings' => Category::getSeoSettings($category['id'])
        ];
    }

    /**
     * Зрендерити html блок зі списком товарів категорії.
     *
     * @param array $data
     * @return string
     */
    private function renderCategoryProductsHtml(array $data): string
    {
        extract($data);
        ob_start();
        include __DIR__ . '/../../resources/views/products/partials/category_products.php';
        return ob_get_clean();
    }

    /**
     * Показати список товарів
     */
    public function index()
    {
        $filters = ProductFilterService::parseFiltersFromUrl($_GET);
        $filters['limit'] = (int) ($_GET['limit'] ?? 12);
        $filters['sort_by'] = $_GET['sort_by'] ?? 'created_at';
        $filters['sort_order'] = $_GET['sort_order'] ?? 'DESC';

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters['offset'] = ($page - 1) * $filters['limit'];

        $products = ProductFilterService::filter($filters);
        $total = ProductFilterService::count($filters);
        $pages = max(1, (int) ceil($total / $filters['limit']));

        // Отримати категорії для навігації
        $categories = Category::getTree();
        $filterOptions = ProductFilterService::getCatalogFilterOptions();
        $priceRange = ProductFilterService::getGlobalPriceRange();

        return View::render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $filters['limit'],
            'filterOptions' => $filterOptions,
            'priceRange' => $priceRange,
            'currentFilters' => $filters
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
        $product = Product::findVisibleBySlug($slug);
        
        if (!$product) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        // Отримати атрибути товару
        $attributes = ProductAttributeValue::getByProduct($product['id']);
        $selectableAttributes = [];
        $detailAttributes = [];

        foreach ($attributes as $attribute) {
            if (!empty($attribute['is_selectable'])) {
                $selectableAttributes[] = $attribute;
                continue;
            }

            $detailAttributes[] = $attribute;
        }

        $galleryImages = ProductImage::getByProduct((int) $product['id']);

        // Отримати SEO-налаштування
        $seoSettings = Product::getSeoSettings($product['id']);

        // Отримати категорію та хлібні крихти
        $category = null;
        $breadcrumbs = [];
        
        if ($product['category_id']) {
            $category = Category::findById($product['category_id']);
            $breadcrumbs = Category::getBreadcrumbs($product['category_id']);
        }

        $similarProducts = Product::getSimilar((int) $product['id'], isset($product['category_id']) ? (int) $product['category_id'] : null, 4);

        return View::render('products/show', [
            'product' => $product,
            'attributes' => $attributes,
            'selectableAttributes' => $selectableAttributes,
            'detailAttributes' => $detailAttributes,
            'category' => $category,
            'breadcrumbs' => $breadcrumbs,
            'galleryImages' => $galleryImages,
            'seoSettings' => $seoSettings,
            'similarProducts' => $similarProducts,
            'categoryTree' => Category::getTree()
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

        $data = $this->buildCategoryPageData($category, $_GET);

        $isAjax = (!empty($_GET['ajax']) && (int) $_GET['ajax'] === 1)
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'html' => $this->renderCategoryProductsHtml($data),
                'total' => $data['totalProducts'],
                'pages' => $data['pages'],
                'page' => $data['page']
            ]);
            return;
        }

        return View::render('products/category', $data);
    }

    /**
     * AJAX endpoint для фільтрації товарів категорії.
     *
     * @param string $slug
     * @return void
     */
    public function filterCategory($slug)
    {
        return $this->showCategory($slug);
    }
}
