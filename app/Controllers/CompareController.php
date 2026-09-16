<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\CompareList;
use App\Services\SeoService;

class CompareController
{
    private const FALLBACK_RETURN_URL = '/products';

    private function validateCsrfOrAbort(): void
    {
        Csrf::abortIfInvalid(__('csrf_token_invalid'));
    }

    private function isAjaxRequest(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    /**
     * Безпечне повернення на сторінку, з якої додали товар (аналогічно
     * CartController), щоб порівняння кількох товарів не переривало перегляд
     * каталогу. Приймаються тільки відносні шляхи цього сайту — жодних
     * зовнішніх адрес (захист від open redirect).
     */
    private function resolveReturnUrl(): string
    {
        $candidate = trim((string) ($_POST['return_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));
        if ($candidate === '') {
            return self::FALLBACK_RETURN_URL;
        }

        $parsed = parse_url($candidate);
        $path = (string) ($parsed['path'] ?? '');

        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return self::FALLBACK_RETURN_URL;
        }

        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return $path . $query;
    }

    /**
     * Сторінка порівняння /compare — таблиця характеристик.
     */
    public function index()
    {
        $items = CompareList::getItems();
        $productIds = array_map(static fn (array $item) => (int) $item['id'], $items);
        $attributesMatrix = CompareList::getAttributesMatrix($productIds);

        return View::render('compare/index', [
            'items' => $items,
            'attributesMatrix' => $attributesMatrix,
            'maxItems' => CompareList::MAX_ITEMS,
            'csrf' => Csrf::token(),
            'seo' => SeoService::forSystem('compare', '/compare'),
        ]);
    }

    /**
     * JSON-лічильник для бейджа в шапці сайту (той самий підхід, що й /cart/count).
     */
    public function count(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'count' => CompareList::getCount(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function add($id): void
    {
        $this->validateCsrfOrAbort();

        $result = CompareList::add((int) $id);

        if ($result['success']) {
            if ($result['category_switched']) {
                $_SESSION['success'] = __('compare_category_switched');
            } elseif ($result['message'] === null) {
                $_SESSION['success'] = __('compare_product_added');
            }
            // message === null && !category_switched означає товар вже був у списку — тиша, без флеша.
        } else {
            $_SESSION['error'] = sprintf(__((string) $result['message']), CompareList::MAX_ITEMS);
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => (bool) $result['success'],
                'message' => $result['success']
                    ? ($result['category_switched'] ? __('compare_category_switched') : __('compare_product_added'))
                    : sprintf(__((string) $result['message']), CompareList::MAX_ITEMS),
                'count' => CompareList::getCount(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . $this->resolveReturnUrl());
        exit;
    }

    public function remove($id): void
    {
        $this->validateCsrfOrAbort();

        CompareList::remove((int) $id);
        $_SESSION['success'] = __('compare_product_removed');

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'count' => CompareList::getCount(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $redirect = CompareList::getCount() > 0 ? '/compare' : $this->resolveReturnUrl();
        header('Location: ' . $redirect);
        exit;
    }

    public function clear(): void
    {
        $this->validateCsrfOrAbort();

        CompareList::clear();
        $_SESSION['success'] = __('compare_cleared');

        header('Location: /compare');
        exit;
    }
}
