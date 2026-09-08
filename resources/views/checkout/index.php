<div class="checkout-page">
    <h1><?= __('checkout_title') ?></h1>

    <div id="checkout-status" class="checkout-status" hidden></div>

    <form id="checkout-form" class="checkout-grid" action="/place-order" method="POST" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

        <section class="checkout-card">
            <h2><?= __('checkout_contact_info') ?></h2>

            <label class="field-label" for="full_name"><?= __('checkout_full_name') ?></label>
            <input id="full_name" name="full_name" type="text" minlength="5" required value="<?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>">
            <small class="field-error" data-error-for="full_name"></small>

            <label class="field-label" for="phone"><?= __('checkout_phone') ?></label>
            <input id="phone" name="phone" type="tel" required
                   data-phone-mask="<?= htmlspecialchars($phoneMask ?? '+38 (###) ###-##-##') ?>"
                   placeholder="<?= htmlspecialchars($phoneMask ?? '+38 (###) ###-##-##') ?>"
                   value="<?= htmlspecialchars((string)($user['phone'] ?? '')) ?>">
            <small class="field-error" data-error-for="phone"></small>

            <label class="field-label" for="email"><?= __('checkout_email') ?></label>
            <input id="email" name="email" type="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            <small class="field-error" data-error-for="email"></small>
        </section>

        <section class="checkout-card">
            <h2><?= __('checkout_delivery') ?></h2>
            <div class="radio-group">
                <?php foreach ($deliveryMethods as $idx => $method): ?>
                    <?php $deliveryCost = (float) ($method['settings']['cost'] ?? 0); ?>
                    <label>
                        <input
                            type="radio"
                            name="delivery_id"
                            value="<?= (int) $method['id'] ?>"
                            data-code="<?= htmlspecialchars((string) ($method['code'] ?? '')) ?>"
                            data-pickup-address="<?= htmlspecialchars((string) ($method['settings']['address'] ?? '')) ?>"
                            <?= $idx === 0 ? 'checked' : '' ?>
                        >
                        <?= htmlspecialchars((string) $method['name']) ?>
                        <?php if ($deliveryCost > 0): ?>
                            <small style="display:block; color:#999; font-weight:400;"><?= __('checkout_delivery_extra_cost', ['amount' => format_price($deliveryCost)]) ?></small>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="field-error" data-error-for="delivery_id"></small>

            <div id="delivery-np-fields" class="delivery-block">
                <label class="field-label" for="delivery_city"><?= __('checkout_city') ?></label>
                <input id="delivery_city" name="delivery_city" type="text" list="np-city-list" autocomplete="off" placeholder="<?= __('checkout_city_placeholder') ?>">
                <input id="delivery_city_ref" type="hidden" name="delivery_city_ref">
                <datalist id="np-city-list"></datalist>
                <small class="field-error" data-error-for="delivery_city"></small>

                <label class="field-label" for="delivery_warehouse"><?= __('checkout_warehouse') ?></label>
                <select id="delivery_warehouse" name="delivery_warehouse">
                    <option value=""><?= __('checkout_select_city_first') ?></option>
                </select>
                <small class="field-error" data-error-for="delivery_warehouse"></small>
            </div>

            <div id="delivery-courier-fields" class="delivery-block" hidden>
                <label class="field-label" for="delivery_address"><?= __('checkout_delivery_address') ?></label>
                <input id="delivery_address" name="delivery_address" type="text" placeholder="<?= __('checkout_delivery_address_placeholder') ?>">
                <small class="field-error" data-error-for="delivery_address"></small>
            </div>

            <div id="delivery-pickup-fields" class="delivery-block" hidden>
                <p class="field-label"><?= __('settings_shop_address') ?></p>
                <p id="pickup-address-text" style="margin:0; padding: 0.5rem 0.75rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;"></p>
            </div>

            <?php do_action('checkout.delivery_fields', $deliveryMethods); ?>
        </section>

        <section class="checkout-card">
            <h2><?= __('checkout_payment') ?></h2>
            <div class="radio-group">
                <?php foreach ($paymentMethods as $idx => $method): ?>
                    <label>
                        <input
                            type="radio"
                            name="payment_id"
                            value="<?= (int) $method['id'] ?>"
                            <?= $idx === 0 ? 'checked' : '' ?>
                        >
                        <?= htmlspecialchars((string) $method['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="field-error" data-error-for="payment_id"></small>

            <label class="field-label" for="comment"><?= __('checkout_comment') ?></label>
            <textarea id="comment" name="comment" rows="4" placeholder="<?= __('checkout_comment_placeholder') ?>"></textarea>
        </section>

        <section class="checkout-card checkout-summary">
            <h2><?= __('checkout_your_order') ?></h2>
            <ul class="summary-list">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span>
                            <?= htmlspecialchars($item['name']) ?> × <?= (int) $item['quantity'] ?>
                            <?php if (!empty($item['selected_options'])): ?>
                                <small style="display:block; color:#6b7280;">
                                    <?php foreach ($item['selected_options'] as $idx => $option): ?>
                                        <?= $idx > 0 ? '; ' : '' ?><?= htmlspecialchars((string) ($option['name'] ?? '')) ?>: <?= htmlspecialchars((string) ($option['value'] ?? '')) ?>
                                    <?php endforeach; ?>
                                </small>
                            <?php endif; ?>
                        </span>
                        <strong><?= format_price($item['price'] * $item['quantity']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php do_action('checkout.summary.before_total', $total); ?>

            <div class="summary-total">
                <span><?= __('checkout_total') ?></span>
                <strong><?= format_price($total) ?></strong>
            </div>

            <button type="submit" id="checkout-submit" class="checkout-submit"><?= __('checkout_submit') ?></button>
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

<script>
window.CHECKOUT_TRANSLATIONS = {
    specify_pib: <?= json_encode(__('checkout_specify_pib')) ?>,
    specify_phone: <?= json_encode(__('checkout_specify_phone')) ?>,
    specify_email: <?= json_encode(__('checkout_specify_email')) ?>,
    specify_delivery: <?= json_encode(__('checkout_specify_delivery')) ?>,
    specify_payment: <?= json_encode(__('checkout_specify_payment')) ?>,
    specify_city: <?= json_encode(__('checkout_specify_city')) ?>,
    specify_warehouse: <?= json_encode(__('checkout_specify_warehouse')) ?>,
    specify_address: <?= json_encode(__('checkout_specify_address')) ?>,
    fix_errors: <?= json_encode(__('checkout_fix_errors')) ?>,
    sending: <?= json_encode(__('checkout_sending')) ?>,
    order_error: <?= json_encode(__('checkout_order_error')) ?>,
    order_success: <?= json_encode(__('checkout_order_success')) ?>,
    connection_error: <?= json_encode(__('checkout_connection_error')) ?>,
    submit_button: <?= json_encode(__('checkout_submit_button')) ?>,
    loading_cities: <?= json_encode(__('checkout_loading_cities')) ?>,
    loading_warehouses: <?= json_encode(__('checkout_loading_warehouses')) ?>,
    loading: <?= json_encode(__('checkout_loading')) ?>,
    select_warehouse: <?= json_encode(__('checkout_select_warehouse')) ?>,
    load_failed: <?= json_encode(__('checkout_load_failed')) ?>,
    select_city_first: <?= json_encode(__('checkout_select_city_first')) ?>
};
</script>
<script src="/js/checkout.js"></script>
