<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Database\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\CrmUserService;
use App\Models\Setting;
use App\Services\SlugHelper;
use App\Services\ProductFilterService;
use App\Services\SeoService;
use App\Models\Review;
use App\Core\Mail\MailService;

class ProductController
{
    private function resolveCategorySeoPage(array $category, ?array $seoSettings): array
    {
        return SeoService::forCategory($category);
    }

    private function resolveProductSeoPage(array $product, ?array $category): array
    {
        return SeoService::forProduct($product, $category);
    }

    private function renderProductSeoTemplate(string $template, array $product, ?array $category): string
    {
        // Залишено для зворотної сумісності; SeoService::forProduct() використовує власну логіку
        return '';
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
        $perPage = (int)($queryParams['limit'] ?? 12);
        $perPage = max(1, $perPage);

        $filters = ProductFilterService::parseFiltersFromUrl($queryParams);
        $filters['category_id']  = $category['id'];
        $filters['limit']        = $perPage;
        $filters['sort_by']      = $queryParams['sort_by']    ?? 'name';
        $filters['sort_order']   = $queryParams['sort_order'] ?? 'ASC';

        $pager = \App\Core\Pagination\Paginator::fromRequest('page', $perPage);
        $filters['offset'] = $pager->offset;

        $products      = ProductFilterService::filter($filters);
        $totalProducts = ProductFilterService::count($filters);

        $pager = $pager->setTotal($totalProducts);

        $filterOptions = ProductFilterService::getFilterOptions($category['id'], $filters);
        $priceRange    = ProductFilterService::getPriceRange($category['id']);
        $breadcrumbs   = Category::getBreadcrumbs($category['id']);
        $seoSettings   = Category::getSeoSettings($category['id']);

        return [
            'category'       => $category,
            'products'       => $products,
            'totalProducts'  => $totalProducts,
            'pager'          => $pager,
            // Зворотна сумісність для partial що ще використовує $page/$pages
            'page'           => $pager->page,
            'pages'          => $pager->totalPages,
            'filterOptions'  => $filterOptions,
            'priceRange'     => $priceRange,
            'currentFilters' => $filters,
            'breadcrumbs'    => $breadcrumbs,
            'categoryTree'   => Category::getTree(),
            'childCategories'=> Category::getChildren($category['id']),
            'seoSettings'    => $seoSettings,
            'seo'            => $this->resolveCategorySeoPage($category, $seoSettings),
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
            'seo'   => SeoService::forSystem('admin_products', '/products'),
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

        $stockRow = DB::query(
            'SELECT COALESCE(quantity, 0) AS quantity FROM product_stocks WHERE sku = ? AND option_id IS NULL LIMIT 1',
            [(string) ($product['sku'] ?? '')]
        )->fetch();
        $product['stock'] = (int) ($stockRow['quantity'] ?? 0);

        // Отримати атрибути товару
        $attributes = ProductAttribute::getByProduct($product['id']);
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
     * Показати категорію за вкладеним path або slug.
     * Параметр $path може бути:
     *   - 'smartphones'            (старий slug)
     *   - 'electronics/smartphones' (новий вкладений path)
     */
    public function showCategory(string $path): void
    {
        // Спочатку шукаємо за повним path (вкладені категорії)
        $category = Category::findByPath($path);

        // Якщо не знайшли за path — шукаємо за slug (зворотна сумісність)
        if (!$category) {
            $lastSlug = basename($path);

            // Перевіряємо 301 редирект
            $redirect = SlugHelper::getRedirect($lastSlug, 'category');
            if ($redirect) {
                $newCategory = Category::findBySlug($redirect['new_slug']);
                $newPath     = ltrim($newCategory['path'] ?? $redirect['new_slug'], '/');
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: /category/' . $newPath);
                exit;
            }

            $category = Category::findBySlug($lastSlug);
        }

        if (!$category) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        // Якщо URL не відповідає канонічному path — 301
        $canonicalPath = ltrim($category['path'] ?? $category['slug'], '/');
        if ($path !== $canonicalPath && !empty($category['path'])) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: /category/' . $canonicalPath);
            exit;
        }

        $data   = $this->buildCategoryPageData($category, $_GET);
        $isAjax = (!empty($_GET['ajax']) && (int) $_GET['ajax'] === 1)
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'html'  => $this->renderCategoryProductsHtml($data),
                'total' => $data['totalProducts'],
                'pages' => $data['pages'],
                'page'  => $data['page'],
            ]);
            return;
        }

        View::render('products/category', $data);
    }

    /**
     * AJAX endpoint для фільтрації товарів категорії.
     */
    public function filterCategory(string $path): void
    {
        $this->showCategory($path);
    }

    public function reviews($slug)
    {
        $product = Product::findVisibleBySlug($slug);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Товар не знайдено']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $items = Review::getThreadedByProduct((int) $product['id'], $limit, $offset);
        $total = Review::countRootsByProduct((int) $product['id']);

        echo json_encode([
            'success' => true,
            'items' => $items,
            'has_more' => $total > ($offset + count($items)),
        ]);
    }

    public function addReview($slug)
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Потрібна авторизація']);
            return;
        }

        if (!\App\Core\Http\Csrf::isValid()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Невірний CSRF токен']);
            return;
        }

        $product = Product::findVisibleBySlug($slug);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Товар не знайдено']);
            return;
        }

        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;
        $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int) $_POST['rating'] : null;
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($body === '' || mb_strlen($body) < 3 || mb_strlen($body) > 2000) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Текст від 3 до 2000 символів']);
            return;
        }

        if ($parentId === null && ($rating === null || $rating < 1 || $rating > 5)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Оцінка від 1 до 5 обовʼязкова']);
            return;
        }

        if ($parentId !== null) {
            $parent = Review::findById($parentId);
            if (!$parent || (int) $parent['product_id'] !== (int) $product['id'] || !empty($parent['parent_id'])) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Некоректний батьківський коментар']);
                return;
            }
        }

        $authorName = trim((string) (($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
        if ($authorName === '') {
            $authorName = (string) ($_SESSION['user']['email'] ?? 'User');
        }

        $id = Review::create([
            'product_id' => (int) $product['id'],
            'user_id' => (int) $_SESSION['user']['id'],
            'parent_id' => $parentId,
            'rating' => $parentId === null ? $rating : null,
            'author_name' => $authorName,
            'body' => $body,
        ]);

        if ($parentId !== null) {
            $parent = Review::findById($parentId);
            if ($parent && (int) $parent['user_id'] !== (int) $_SESSION['user']['id']) {
                $author = \App\Models\User::findById((int) $parent['user_id']);
                if (!empty($author['email'])) {
                    $mail = new MailService();
                    $link = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/product/' . $slug;
                    $mail->send((string) $author['email'], 'Нова відповідь на ваш відгук', 'Вам відповіли на відгук до товару <b>' . htmlspecialchars((string) $product['name']) . '</b>.<br><a href="' . $link . '">Переглянути</a>');
                }
            }
        }

        echo json_encode(['success' => true, 'id' => $id]);
    }
}
