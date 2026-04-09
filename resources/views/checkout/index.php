<div class="checkout-page">
    <h1>Оформлення замовлення</h1>

    <div id="checkout-status" class="checkout-status" hidden></div>

    <form id="checkout-form" class="checkout-grid" action="/place-order" method="POST" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

        <section class="checkout-card">
            <h2>Контактні дані</h2>

            <label class="field-label" for="full_name">ПІБ</label>
            <input id="full_name" name="full_name" type="text" minlength="5" required value="<?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>">
            <small class="field-error" data-error-for="full_name"></small>

            <label class="field-label" for="phone">Телефон</label>
            <input id="phone" name="phone" type="tel" required placeholder="+380...">
            <small class="field-error" data-error-for="phone"></small>

            <label class="field-label" for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            <small class="field-error" data-error-for="email"></small>
        </section>

        <section class="checkout-card">
            <h2>Доставка</h2>
            <div class="radio-group">
                <label><input type="radio" name="delivery_method" value="nova_poshta" checked> Нова Пошта</label>
                <label><input type="radio" name="delivery_method" value="courier"> Кур'єр</label>
            </div>
            <small class="field-error" data-error-for="delivery_method"></small>

            <div id="delivery-np-fields" class="delivery-block">
                <label class="field-label" for="delivery_city">Місто</label>
                <input id="delivery_city" name="delivery_city" type="text" list="np-city-list" autocomplete="off" placeholder="Введіть мінімум 3 символи">
                <input id="delivery_city_ref" type="hidden" name="delivery_city_ref">
                <datalist id="np-city-list"></datalist>
                <small class="field-error" data-error-for="delivery_city"></small>

                <label class="field-label" for="delivery_warehouse">Відділення</label>
                <select id="delivery_warehouse" name="delivery_warehouse">
                    <option value="">Оберіть місто спочатку</option>
                </select>
                <small class="field-error" data-error-for="delivery_warehouse"></small>
            </div>

            <div id="delivery-courier-fields" class="delivery-block" hidden>
                <label class="field-label" for="delivery_address">Адреса доставки</label>
                <input id="delivery_address" name="delivery_address" type="text" placeholder="Вулиця, будинок, квартира">
                <small class="field-error" data-error-for="delivery_address"></small>
            </div>
        </section>

        <section class="checkout-card">
            <h2>Оплата</h2>
            <div class="radio-group">
                <label><input type="radio" name="payment_method" value="card" checked> Карткою онлайн</label>
                <label><input type="radio" name="payment_method" value="cod"> При отриманні</label>
            </div>
            <small class="field-error" data-error-for="payment_method"></small>

            <label class="field-label" for="comment">Коментар до замовлення</label>
            <textarea id="comment" name="comment" rows="4" placeholder="За потреби додайте коментар"></textarea>
        </section>

        <section class="checkout-card checkout-summary">
            <h2>Ваше замовлення</h2>
            <ul class="summary-list">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item['name']) ?> × <?= (int) $item['quantity'] ?></span>
                        <strong><?= number_format($item['price'] * $item['quantity'], 2) ?> грн</strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="summary-total">
                <span>Разом</span>
                <strong><?= number_format($total, 2) ?> грн</strong>
            </div>

            <button type="submit" id="checkout-submit" class="checkout-submit">Підтвердити замовлення</button>
        </section>
    </form>
</div>

<style>
.checkout-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.checkout-card { border: 1px solid #dfe3e8; border-radius: 8px; padding: 1rem; background: #fff; }
.checkout-summary { grid-column: 1 / -1; }
.field-label { display: block; margin: 0.7rem 0 0.3rem; font-weight: 600; }
input, select, textarea { width: 100%; padding: 0.6rem; border: 1px solid #ccd3db; border-radius: 6px; }
.radio-group { display: flex; flex-wrap: wrap; gap: 1rem; margin: 0.4rem 0; }
.delivery-block { margin-top: 0.7rem; }
.summary-list { list-style: none; padding: 0; margin: 0; }
.summary-list li { display: flex; justify-content: space-between; border-bottom: 1px dashed #e5e7eb; padding: 0.55rem 0; }
.summary-total { display: flex; justify-content: space-between; padding-top: 0.8rem; font-size: 1.2rem; }
.checkout-submit { margin-top: 1rem; width: 100%; border: 0; border-radius: 6px; padding: 0.8rem; background: #2563eb; color: #fff; font-weight: 700; cursor: pointer; }
.checkout-status { margin-bottom: 1rem; padding: 0.7rem 0.9rem; border-radius: 6px; }
.checkout-status.success { background: #ecfdf3; color: #166534; border: 1px solid #a7f3d0; }
.checkout-status.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.field-error { color: #b91c1c; min-height: 1.1rem; display: block; margin-top: 0.2rem; font-size: 0.87rem; }
input.invalid, select.invalid, textarea.invalid { border-color: #ef4444; }
@media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }
</style>

<script src="/js/checkout.js"></script>
