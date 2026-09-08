<div class="checkout-success-page">
    <h1><?= htmlspecialchars(__('checkout_order_success'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars(__('checkout_order_number', ['number' => $orderNumber]), ENT_QUOTES, 'UTF-8') ?></p>
    <a class="checkout-success-button" href="/"><?= htmlspecialchars(__('continue_shopping'), ENT_QUOTES, 'UTF-8') ?></a>
</div>

<style>
.checkout-success-page {
    max-width: 640px;
    margin: 3rem auto;
    padding: 2rem;
    text-align: center;
}

.checkout-success-page h1 {
    margin: 0 0 1rem;
}

.checkout-success-page p {
    margin: 0 0 1.5rem;
}

.checkout-success-button {
    display: inline-block;
    padding: 0.8rem 1.25rem;
    border-radius: 6px;
    background: #2563eb;
    color: #fff;
    text-decoration: none;
    font-weight: 700;
}

.checkout-success-button:hover {
    background: #1d4ed8;
}
</style>
