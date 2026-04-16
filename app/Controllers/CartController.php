<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\Cart;
use App\Models\Product;

class CartController
{
    private function validateCsrfOrAbort()
    {
        Csrf::abortIfInvalid('CSRF token mismatch');
    }

    /**
     * Сторінка перегляду кошика
     */
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

    /**
     * Додати товар до кошика
     */
    public function add($id)
    {
        $this->validateCsrfOrAbort();

        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $result = Cart::add($id, $quantity);

        if ($result['success']) {
            $_SESSION['success'] = __('product_added_to_cart');
        } else {
            $_SESSION['error'] = __($result['message']);
        }

        header('Location: /cart');
        exit;
    }

    /**
     * Оновити кількість товару в кошику
     */
    public function update()
    {
        $this->validateCsrfOrAbort();

        $productId = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        $result = Cart::updateQuantity($productId, $quantity);

        if (!$result['success']) {
            $_SESSION['error'] = __($result['message']);
        } else {
            $_SESSION['success'] = __('cart_updated');
        }

        header('Location: /cart');
        exit;
    }

    /**
     * Видалити товар з кошика
     */
    public function remove($id)
    {
        $this->validateCsrfOrAbort();

        Cart::remove($id);
        $_SESSION['success'] = __('product_removed_from_cart');

        header('Location: /cart');
        exit;
    }

    /**
     * Повністю очистити кошик
     */
    public function clear()
    {
        $this->validateCsrfOrAbort();

        Cart::clear();
        $_SESSION['success'] = __('cart_cleared');

        header('Location: /cart');
        exit;
    }
}
