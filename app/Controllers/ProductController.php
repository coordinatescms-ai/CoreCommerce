<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Database\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttributeValue;
use App\Models\ProductImage;
use App\Models\CrmUserService;
use App\Models\Setting;
use App\Services\SlugHelper;
use App\Services\ProductFilterService;

class ProductController
{
    private function renderProductSeoTemplate(string $template, array $product, ?array $category): string
    {
        $template = trim($template);
        if ($template === '') {
            return '';
        }

        $replacements = [
            '{name}' => (string) ($product['name'] ?? ''),
            '{price}' => (string) ($product['price'] ?? ''),
            '{category}' => (string) ($category['name'] ?? ''),
        ];

        return trim(strtr($template, $replacements));
    }

    private function resolveCategorySeoPage(array $category, ?array $seoSettings): array
    {
        $metaTitle = trim((string) ($category['meta_title'] ?? ''));
        $metaDescription = trim((string) ($category['meta_description'] ?? ''));

        if ($metaTitle === '') {
            $metaTitle = trim((string) ($seoSettings['title'] ?? ''));
        }

        if ($metaDescription === '') {
            $metaDescription = trim((string) ($seoSettings['description'] ?? ''));
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ];
    }

    private function resolveProductSeoPage(array $product, ?array $category): array
    {
        $metaTitle = trim((string) ($product['meta_title'] ?? ''));
        $metaDescription = trim((string) ($product['meta_description'] ?? ''));

        if ($metaTitle === '') {
            $metaTitleTemplate = (string) Setting::get('seo_title_template', '');
            $metaTitle = $this->renderProductSeoTemplate($metaTitleTemplate, $product, $category);
        }

        if ($metaDescription === '') {
            $metaDescriptionTemplate = (string) Setting::get('seo_desc_template', '');
            $metaDescription = $this->renderProductSeoTemplate($metaDescriptionTemplate, $product, $category);
        }

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ];
    }

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

        $seoSettings = Category::getSeoSettings($category['id']);

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
            'seoSettings' => $seoSettings,
            'seo' => $this->resolveCategorySeoPage($category, $seoSettings),
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
     * Добавити товар в обране
     */
    public function toggleFavorite() {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Авторизуйтесь']);
        exit;
    }

    $userId = (int)$_SESSION['user']['id'];
    $productId = (int)($_POST['product_id'] ?? 0);

    try {
        // Перевіряємо, чи вже є в обраному
        $check = DB::query("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?", [$userId, $productId])->fetch();

        if ($check) {
            // Видаляємо
            DB::execute("DELETE FROM favorites WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
            echo json_encode(['status' => 'removed', 'message' => 'Видалено']);
        } else {
            // Додаємо
            DB::execute("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)", [$userId, $productId]);
            echo json_encode(['status' => 'added', 'message' => 'Додано']);
        }
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Помилка бази даних']);
    }
    exit;
}

    /**
     * Показати список товарів
     */
    public function index()
    {
        $limit = 12;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $totalPopularByOrders = (int) (DB::query(
            "SELECT COUNT(DISTINCT p.id) AS total
             FROM products p
             INNER JOIN order_items oi ON oi.product_id = p.id
             WHERE p.is_visible = 1"
        )->fetch()['total'] ?? 0);

        if ($totalPopularByOrders > 0) {
            $products = Product::query(
                "SELECT p.*, SUM(oi.qty) AS popularity_score
                 FROM products p
                 INNER JOIN order_items oi ON oi.product_id = p.id
                 WHERE p.is_visible = 1
                 GROUP BY p.id
                 ORDER BY popularity_score DESC, p.id DESC
                 LIMIT {$limit} OFFSET {$offset}"
            ) ?? [];
            $total = $totalPopularByOrders;
        } else {
            $total = (int) (DB::query(
                "SELECT COUNT(DISTINCT p.id) AS total
                 FROM products p
                 INNER JOIN favorites f ON f.product_id = p.id
                 WHERE p.is_visible = 1"
            )->fetch()['total'] ?? 0);

            $products = Product::query(
                "SELECT p.*, COUNT(f.id) AS popularity_score
                 FROM products p
                 INNER JOIN favorites f ON f.product_id = p.id
                 WHERE p.is_visible = 1
                 GROUP BY p.id
                 ORDER BY popularity_score DESC, p.id DESC
                 LIMIT {$limit} OFFSET {$offset}"
            ) ?? [];
        }

        if (empty($products)) {
            $total = (int) (DB::query(
                "SELECT COUNT(*) AS total
                 FROM products
                 WHERE is_visible = 1"
            )->fetch()['total'] ?? 0);

            $products = Product::query(
                "SELECT *
                 FROM products
                 WHERE is_visible = 1
                 ORDER BY id DESC
                 LIMIT {$limit} OFFSET {$offset}"
            ) ?? [];
        }

        $pages = max(1, (int) ceil($total / $limit));
        $categories = Category::getTree();

        return View::render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
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

        $product['price'] = apply_filters('product.price', (float) ($product['price'] ?? 0), $product);
        $product['description'] = (string) apply_filters('product.description', (string) ($product['description'] ?? ''), (int) $product['id']);

        if (!empty($_SESSION['user']['id'])) {
            CrmUserService::recordActivity((int) $_SESSION['user']['id'], 'product_view', 'Перегляд товару: ' . (string) ($product['name'] ?? ''));
        }

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
            'categoryTree' => Category::getTree(),
            'seo' => $this->resolveProductSeoPage($product, $category),
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
