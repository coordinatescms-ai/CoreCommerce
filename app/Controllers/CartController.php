<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\Cart;
use App\Models\CrmUserService;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SeoService;

class CartController
{
    private const FALLBACK_CONTINUE_SHOPPING_URL = '/products';

    private function validateCsrfOrAbort()
    {
        Csrf::abortIfInvalid(__('csrf_token_invalid'));
    }

    private function logUserActivity(string $eventType, string $description): void
    {
        if (empty($_SESSION['user']['id'])) {
            return;
        }

        CrmUserService::recordActivity((int) $_SESSION['user']['id'], $eventType, $description);
    }

    /**
     * На /cart клієнт ще НЕ обрав конкретний спосіб доставки (це відбувається
     * на /checkout), тож показати єдину фіксовану ціну неможливо. Але кажемо
     * "Безкоштовно" тільки якщо це правда для УСІХ активних методів доставки —
     * інакше показуємо нейтральне повідомлення, а не хибну обіцянку.
     */
    private function isShippingActuallyFree(): bool
    {
        $shippingMethods = Setting::getShopMethods('shipping');

        foreach ($shippingMethods as $method) {
            if ((int) ($method['is_active'] ?? 0) !== 1) {
                continue;
            }

            $settings = json_decode((string) ($method['settings'] ?? ''), true);
            $cost = is_array($settings) ? (float) ($settings['cost'] ?? 0) : 0.0;

            if ($cost > 0.0) {
                return false;
            }
        }

        return true;
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
            'shippingIsFree' => $this->isShippingActuallyFree(),
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
            $this->logUserActivity('cart_add', __('added_to_cart') . ' ' . (string) ($product['name'] ?? sprintf(__('cart_product_id_fallback'), (int) $id)));
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
            $this->logUserActivity('cart_update', __('updated_cart_quantity'));
            $_SESSION['success'] = __('cart_updated');
        }

        header('Location: /cart');
        exit;
    }

    public function remove($id)
    {
        $this->validateCsrfOrAbort();

        Cart::remove($id);
        $this->logUserActivity('cart_remove', __('removed_from_cart'));
        $_SESSION['success'] = __('product_removed_from_cart');

        header('Location: /cart');
        exit;
    }

    public function clear()
    {
        $this->validateCsrfOrAbort();

        Cart::clear();
        $this->logUserActivity('cart_clear', __('cleared_cart'));
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
