<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\Cart;
use App\Models\CrmUserService;
use App\Models\Product;
use App\Services\SeoService;

class CartController
{
    private const FALLBACK_CONTINUE_SHOPPING_URL = '/products';

    private function validateCsrfOrAbort()
    {
        Csrf::abortIfInvalid('CSRF token mismatch');
    }

    private function logUserActivity(string $eventType, string $description): void
    {
        if (empty($_SESSION['user']['id'])) {
            return;
        }

        CrmUserService::recordActivity((int) $_SESSION['user']['id'], $eventType, $description);
    }

    public function index()
    {
        $items = Cart::getItems();
        $total = Cart::getTotal();
        $continueShoppingUrl = $this->resolveContinueShoppingUrl();

        return View::render('cart.index', [
            'items' => $items,
            'total' => $total,
            'continueShoppingUrl' => $continueShoppingUrl,
            'csrf' => Csrf::token(),
            'seo' => SeoService::forSystem('cart', '/cart'),
        ]);
    }

    public function add($id)
    {
        $this->validateCsrfOrAbort();

        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
        $selectedOptionIds = $_POST['selected_option_ids'] ?? [];
        if (!is_array($selectedOptionIds)) {
            $selectedOptionIds = [];
        }

        $result = Cart::add($id, $quantity, $selectedOptionIds);

        do_action('cart.add_item', (int) $id, (int) $quantity, $selectedOptionIds);

        if ($result['success']) {
            $this->storeLastShoppingUrl();
            $product = Product::findVisibleById((int) $id);
            $this->logUserActivity('cart_add', 'Додав у кошик: ' . (string) ($product['name'] ?? ('ID ' . (int) $id)));
            $_SESSION['success'] = __('product_added_to_cart');
        } else {
            $_SESSION['error'] = __($result['message']);
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => (bool) $result['success'],
                'message' => __($result['success'] ? 'product_added_to_cart' : (string) $result['message']),
                'count' => Cart::getItemsCount(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: /cart');
        exit;
    }

    public function count()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'count' => Cart::getItemsCount(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function update()
    {
        $this->validateCsrfOrAbort();

        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $quantity = (int) $_POST['quantity'];

        $result = Cart::updateQuantity($cartItemId, $quantity);

        if (!$result['success']) {
            $_SESSION['error'] = __($result['message']);
        } else {
            $this->logUserActivity('cart_update', 'Оновив кількість товару в кошику');
            $_SESSION['success'] = __('cart_updated');
        }

        header('Location: /cart');
        exit;
    }

    public function remove($id)
    {
        $this->validateCsrfOrAbort();

        Cart::remove($id);
        $this->logUserActivity('cart_remove', 'Видалив товар із кошика');
        $_SESSION['success'] = __('product_removed_from_cart');

        header('Location: /cart');
        exit;
    }

    public function clear()
    {
        $this->validateCsrfOrAbort();

        Cart::clear();
        $this->logUserActivity('cart_clear', 'Очистив кошик');
        $_SESSION['success'] = __('cart_cleared');

        header('Location: /cart');
        exit;
    }

    private function isAjaxRequest(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private function storeLastShoppingUrl(): void
    {
        $candidateUrl = trim((string) ($_POST['return_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));
        if ($candidateUrl === '') {
            return;
        }

        $parsed = parse_url($candidateUrl);
        $path = (string) ($parsed['path'] ?? '');
        $allowedPrefixes = ['/product/', '/category/'];
        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return;
        }

        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $_SESSION['cart_continue_shopping_url'] = $path . $query;
    }

    private function resolveContinueShoppingUrl(): string
    {
        $url = trim((string) ($_SESSION['cart_continue_shopping_url'] ?? ''));
        if ($url === '' || !str_starts_with($url, '/')) {
            return self::FALLBACK_CONTINUE_SHOPPING_URL;
        }

        return $url;
    }
}
