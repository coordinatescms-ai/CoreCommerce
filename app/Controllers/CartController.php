<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\Cart;
use App\Models\CrmUserService;
use App\Models\Product;

class CartController
{
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

        return View::render('cart.index', [
            'items' => $items,
            'total' => $total,
            'csrf' => Csrf::token()
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

        if ($result['success']) {
            $product = Product::findVisibleById((int) $id);
            $this->logUserActivity('cart_add', 'Додав у кошик: ' . (string) ($product['name'] ?? ('ID ' . (int) $id)));
            $_SESSION['success'] = __('product_added_to_cart');
        } else {
            $_SESSION['error'] = __($result['message']);
        }

        header('Location: /cart');
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
}
