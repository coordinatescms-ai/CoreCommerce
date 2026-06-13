<?php
/** @var array $user */
/** @var array $shippingMethods */
/** @var array $paymentMethods */
/** @var string $csrf */
/** @var array $allowedStatuses */
$fullName  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$fullName  = $fullName !== '' ? $fullName : ($user['email'] ?? '—');
$statusLabels = [
    'new'        => 'Новий',
    'confirmed'  => 'Підтверджено',
    'processing' => 'Комплектується',
    'shipped'    => 'Відправлено',
    'delivered'  => 'Доставлено',
    'completed'  => 'Виконано',
    'cancelled'  => 'Скасовано',
    'returned'   => 'Повернення',
];
?>
<style>
.co-grid          { display:grid; grid-template-columns:1fr 340px; gap:1.25rem; align-items:start; }
.co-section       { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
.co-section h3    { margin:0 0 1rem; font-size:1rem; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:.45rem; }
.co-section h3 i  { color:#64748b; font-size:.9rem; }

/* Product search */
.prod-search-wrap { position:relative; }
.prod-search-wrap input { width:100%; box-sizing:border-box; }
.prod-dropdown    { position:absolute; top:100%; left:0; right:0; background:#fff;
                    border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px;
                    max-height:260px; overflow-y:auto; z-index:200; box-shadow:0 8px 24px rgba(0,0,0,.1); display:none; }
.prod-dropdown-item { padding:.6rem 1rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; gap:.5rem; font-size:.9rem; }
.prod-dropdown-item:hover { background:#f1f5f9; }
.prod-dropdown-item .prod-name  { color:#0f172a; font-weight:500; }
.prod-dropdown-item .prod-meta  { color:#64748b; font-size:.8rem; white-space:nowrap; }
.prod-dropdown-item .prod-price { color:#10b981; font-weight:700; white-space:nowrap; }

/* Items table */
.items-table      { width:100%; border-collapse:collapse; font-size:.9rem; }
.items-table th   { background:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.03em;
                    padding:.55rem .75rem; text-align:left; border-bottom:2px solid #e2e8f0; }
.items-table td   { padding:.6rem .75rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.items-table tr:last-child td { border-bottom:none; }
.items-table .qty-input { width:72px; text-align:center; }
.items-table .price-input { width:100px; }
.items-table .remove-btn  { background:none; border:none; cursor:pointer; color:#ef4444; font-size:1rem; padding:.25rem; line-height:1; }
.items-table .remove-btn:hover { color:#b91c1c; }
.empty-items      { text-align:center; color:#94a3b8; padding:1.5rem; font-size:.9rem; }

/* Summary */
.summary-line     { display:flex; justify-content:space-between; align-items:center; padding:.4rem 0; font-size:.9rem; }
.summary-line.total { font-weight:700; font-size:1.05rem; border-top:2px solid #e2e8f0; margin-top:.5rem; padding-top:.75rem; }
.summary-line span:last-child { color:#0f172a; }

/* Form fields */
.field-row        { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.field-group      { margin-bottom:.85rem; }
.field-group label{ display:block; font-size:.82rem; font-weight:600; color:#475569; margin-bottom:.3rem; }
.field-group input,
.field-group select,
.field-group textarea { width:100%; box-sizing:border-box; padding:.5rem .75rem; border:1px solid #e2e8f0; border-radius:6px; font-size:.9rem; color:#0f172a; background:#fff; }
.field-group input:focus,
.field-group select:focus,
.field-group textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.field-group textarea { resize:vertical; min-height:70px; }

/* Delivery extra fields */
.delivery-extra   { display:none; margin-top:.6rem; }
.delivery-extra.visible { display:block; }

/* Toast */
.co-toast         { position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
                    background:#0f172a; color:#fff; padding:.75rem 1.25rem;
                    border-radius:8px; font-size:.9rem; max-width:340px;
                    box-shadow:0 8px 24px rgba(0,0,0,.2); opacity:0;
                    transform:translateY(12px); transition:all .25s ease; pointer-events:none; }
.co-toast.show    { opacity:1; transform:translateY(0); }
.co-toast.error   { background:#ef4444; }
.co-toast.success { background:#10b981; }

/* Submit btn */
.co-submit-btn    { width:100%; padding:.75rem; font-size:1rem; font-weight:600; cursor:pointer;
                    background:#6366f1; color:#fff; border:none; border-radius:8px; transition:background .2s; }
.co-submit-btn:hover   { background:#4f46e5; }
.co-submit-btn:disabled { background:#a5b4fc; cursor:not-allowed; }

@media(max-width:860px) {
    .co-grid  { grid-template-columns:1fr; }
    .field-row { grid-template-columns:1fr; }
}
</style>

<!-- Header -->
<div class="page-header" style="margin-bottom:1.25rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:.25rem;">
            <i class="fas fa-cart-plus" style="color:#6366f1;"></i>
            Нове замовлення
        </h1>
        <div style="font-size:.9rem; color:#64748b;">
            Для клієнта:
            <a href="/admin/users/edit/<?= (int)$user['id'] ?>" style="color:#6366f1; font-weight:600;">
                <?= htmlspecialchars($fullName) ?>
            </a>
            &nbsp;·&nbsp;
            <span style="color:#94a3b8;"><?= htmlspecialchars($user['email'] ?? '') ?></span>
        </div>
    </div>
    <a href="/admin/users/edit/<?= (int)$user['id'] ?>" class="btn btn-outline" style="border:1px solid #ddd; color:#334155;">
        <i class="fas fa-arrow-left"></i> Назад до CRM
    </a>
</div>

<div class="co-grid">
    <!-- LEFT: товари + деталі замовлення -->
    <div>
        <!-- Додати товар -->
        <div class="co-section">
            <h3><i class="fas fa-search"></i> Пошук та додавання товарів</h3>
            <div class="prod-search-wrap">
                <input type="text" id="prod-search" class="form-control"
                       placeholder="Назва або SKU товару…" autocomplete="off">
                <div class="prod-dropdown" id="prod-dropdown"></div>
            </div>
        </div>

        <!-- Таблиця товарів -->
        <div class="co-section">
            <h3><i class="fas fa-list"></i> Товари замовлення</h3>
            <table class="items-table" id="items-table">
                <thead>
                    <tr>
                        <th style="width:40%;">Товар</th>
                        <th>SKU</th>
                        <th>Кіл-ть</th>
                        <th>Ціна, <?= htmlspecialchars(\App\Core\Database\DB::query('SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1')->fetchColumn() ?: '₴') ?></th>
                        <th>Сума</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="items-tbody">
                    <tr id="empty-row">
                        <td colspan="6" class="empty-items">
                            <i class="fas fa-box-open" style="font-size:1.5rem; display:block; margin-bottom:.5rem; color:#cbd5e1;"></i>
                            Товари ще не додані
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Дані клієнта -->
        <div class="co-section">
            <h3><i class="fas fa-user"></i> Дані клієнта</h3>
            <div class="field-row">
                <div class="field-group">
                    <label>ПІБ *</label>
                    <input type="text" id="f-name" value="<?= htmlspecialchars($fullName) ?>" placeholder="Іваненко Іван Іванович">
                </div>
                <div class="field-group">
                    <label>Телефон *</label>
                    <input type="text" id="f-phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+380XXXXXXXXX">
                </div>
            </div>
            <div class="field-group">
                <label>Email</label>
                <input type="email" id="f-email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
        </div>

        <!-- Доставка -->
        <div class="co-section">
            <h3><i class="fas fa-truck"></i> Доставка</h3>
            <div class="field-group">
                <label>Спосіб доставки *</label>
                <select id="f-delivery">
                    <option value="">— оберіть —</option>
                    <?php foreach ($shippingMethods as $m): ?>
                        <option value="<?= htmlspecialchars($m['code'] ?? '') ?>">
                            <?= htmlspecialchars($m['name'] ?? $m['code'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="delivery-extra" id="nova-poshta-fields">
                <div class="field-row">
                    <div class="field-group">
                        <label>Місто</label>
                        <input type="text" id="f-city" placeholder="Київ">
                    </div>
                    <div class="field-group">
                        <label>Відділення</label>
                        <input type="text" id="f-warehouse" placeholder="№1 (вул. Прикладна, 1)">
                    </div>
                </div>
            </div>
            <div class="delivery-extra" id="courier-fields">
                <div class="field-group">
                    <label>Адреса доставки</label>
                    <input type="text" id="f-address" placeholder="вул. Хрещатик, 1, кв. 5">
                </div>
            </div>
        </div>

        <!-- Оплата -->
        <div class="co-section">
            <h3><i class="fas fa-credit-card"></i> Оплата</h3>
            <div class="field-group">
                <label>Спосіб оплати *</label>
                <select id="f-payment">
                    <option value="">— оберіть —</option>
                    <?php foreach ($paymentMethods as $m): ?>
                        <option value="<?= htmlspecialchars($m['code'] ?? '') ?>">
                            <?= htmlspecialchars($m['name'] ?? $m['code'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Коментар -->
        <div class="co-section">
            <h3><i class="fas fa-comment-alt"></i> Коментар</h3>
            <div class="field-group" style="margin:0;">
                <textarea id="f-comment" placeholder="Додаткова інформація до замовлення…"></textarea>
            </div>
        </div>
    </div>

    <!-- RIGHT: підсумок + статус + кнопка -->
    <div>
        <div class="co-section" style="position:sticky; top:1rem;">
            <h3><i class="fas fa-receipt"></i> Підсумок</h3>
            <div id="summary-lines"></div>
            <div class="summary-line total">
                <span>Разом</span>
                <span id="summary-total">0.00 <?= htmlspecialchars(\App\Core\Database\DB::query('SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1')->fetchColumn() ?: '₴') ?></span>
            </div>

            <div class="field-group" style="margin-top:1.25rem;">
                <label>Статус замовлення</label>
                <select id="f-status">
                    <?php foreach ($allowedStatuses as $s): ?>
                        <option value="<?= $s ?>" <?= $s === 'new' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($statusLabels[$s] ?? $s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" id="submit-btn" class="co-submit-btn" disabled>
                <i class="fas fa-check"></i> Оформити замовлення
            </button>

            <div style="margin-top:.75rem; font-size:.8rem; color:#94a3b8; text-align:center;">
                Замовлення буде прив'язане до клієнта #<?= (int)$user['id'] ?>
            </div>
        </div>
    </div>
</div>

<div class="co-toast" id="co-toast"></div>

<script>
(function () {
    const CSRF      = <?= json_encode($csrf) ?>;
    const USER_ID   = <?= (int)$user['id'] ?>;
    const STORE_URL = '/admin/users/store-order/' + USER_ID;
    const SEARCH_URL = '/admin/products/search?q=';

    // ---- state ----
    let items = [];   // [{product_id, name, sku, qty, price, stock}]

    // ---- DOM refs ----
    const searchInput  = document.getElementById('prod-search');
    const dropdown     = document.getElementById('prod-dropdown');
    const tbody        = document.getElementById('items-tbody');
    const emptyRow     = document.getElementById('empty-row');
    const summaryLines = document.getElementById('summary-lines');
    const summaryTotal = document.getElementById('summary-total');
    const submitBtn    = document.getElementById('submit-btn');
    const deliveryEl   = document.getElementById('f-delivery');
    const npFields     = document.getElementById('nova-poshta-fields');
    const courierFields= document.getElementById('courier-fields');

    // ---- Toast ----
    function toast(msg, type = 'info') {
        const el = document.getElementById('co-toast');
        el.textContent = msg;
        el.className = 'co-toast show ' + type;
        clearTimeout(el._t);
        el._t = setTimeout(() => { el.className = 'co-toast'; }, 3500);
    }

    // ---- Product search ----
    let searchTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 1) { dropdown.style.display = 'none'; return; }
        searchTimer = setTimeout(() => fetchProducts(q), 280);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.style.display = 'none';
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.prod-search-wrap')) dropdown.style.display = 'none';
    });

    async function fetchProducts(q) {
        try {
            const res  = await fetch(SEARCH_URL + encodeURIComponent(q));
            const data = await res.json();
            renderDropdown(data.products || []);
        } catch {
            dropdown.style.display = 'none';
        }
    }

    function renderDropdown(products) {
        if (!products.length) {
            dropdown.innerHTML = '<div class="prod-dropdown-item"><span class="prod-meta">Нічого не знайдено</span></div>';
            dropdown.style.display = 'block';
            return;
        }
        dropdown.innerHTML = products.map(p => `
            <div class="prod-dropdown-item" data-id="${p.id}" data-name="${escHtml(p.name)}"
                 data-sku="${escHtml(p.sku||'')}" data-price="${p.price}" data-stock="${p.stock}">
                <div>
                    <div class="prod-name">${escHtml(p.name)}</div>
                    <div class="prod-meta">SKU: ${escHtml(p.sku||'—')} &nbsp;·&nbsp; Залишок: ${p.stock} шт.</div>
                </div>
                <div class="prod-price">${fmtPrice(p.price)} ' + currencySymbol + '</div>
            </div>`).join('');
        dropdown.style.display = 'block';
    }

    dropdown.addEventListener('click', function (e) {
        const item = e.target.closest('.prod-dropdown-item[data-id]');
        if (!item) return;
        addItem({
            product_id: Number(item.dataset.id),
            name:  item.dataset.name,
            sku:   item.dataset.sku,
            price: parseFloat(item.dataset.price),
            stock: Number(item.dataset.stock),
            qty:   1,
        });
        searchInput.value = '';
        dropdown.style.display = 'none';
    });

    // ---- Items ----
    function addItem(product) {
        const existing = items.find(i => i.product_id === product.product_id);
        if (existing) {
            existing.qty = Math.min(existing.qty + 1, existing.stock || 9999);
            renderItems();
            toast('Кількість збільшено', 'success');
            return;
        }
        items.push({ ...product });
        renderItems();
        toast(product.name + ' додано', 'success');
    }

    function renderItems() {
        if (!items.length) {
            tbody.innerHTML = `<tr id="empty-row"><td colspan="6" class="empty-items">
                <i class="fas fa-box-open" style="font-size:1.5rem;display:block;margin-bottom:.5rem;color:#cbd5e1;"></i>
                Товари ще не додані</td></tr>`;
            updateSummary();
            return;
        }
        tbody.innerHTML = items.map((it, idx) => `
            <tr data-idx="${idx}">
                <td style="font-weight:500;">${escHtml(it.name)}</td>
                <td style="color:#64748b;font-size:.82rem;">${escHtml(it.sku||'—')}</td>
                <td>
                    <input type="number" class="form-control qty-input" min="1"
                           max="${it.stock || 9999}" value="${it.qty}"
                           data-idx="${idx}" onchange="window._coQtyChange(this)">
                </td>
                <td>
                    <input type="number" class="form-control price-input" min="0" step="0.01"
                           value="${it.price.toFixed(2)}" data-idx="${idx}"
                           onchange="window._coPriceChange(this)">
                </td>
                <td style="font-weight:600; white-space:nowrap;">
                    ${fmtPrice(it.price * it.qty)} ' + currencySymbol + '
                </td>
                <td>
                    <button class="remove-btn" onclick="window._coRemove(${idx})" title="Видалити">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>`).join('');
        updateSummary();
    }

    window._coQtyChange = function (el) {
        const idx = Number(el.dataset.idx);
        let qty = parseInt(el.value) || 1;
        const maxStock = items[idx]?.stock || 9999;
        if (qty < 1) qty = 1;
        if (qty > maxStock) { qty = maxStock; el.value = qty; toast('Максимальна кількість: ' + maxStock, 'error'); }
        items[idx].qty = qty;
        renderItems();
    };

    window._coPriceChange = function (el) {
        const idx = Number(el.dataset.idx);
        const price = parseFloat(el.value) || 0;
        items[idx].price = Math.max(0, price);
        renderItems();
    };

    window._coRemove = function (idx) {
        items.splice(idx, 1);
        renderItems();
    };

    function updateSummary() {
        const total = items.reduce((s, it) => s + it.price * it.qty, 0);
        summaryLines.innerHTML = items.map(it =>
            `<div class="summary-line">
                <span style="color:#475569; max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escHtml(it.name)}</span>
                <span>${it.qty} × ${fmtPrice(it.price)} ' + currencySymbol + '</span>
             </div>`).join('');
        summaryTotal.textContent = fmtPrice(total) + ' ' + currencySymbol;
        submitBtn.disabled = items.length === 0;
    }

    // ---- Delivery extra fields ----
    deliveryEl.addEventListener('change', function () {
        npFields.classList.remove('visible');
        courierFields.classList.remove('visible');
        if (this.value === 'nova_poshta') npFields.classList.add('visible');
        if (this.value === 'courier')     courierFields.classList.add('visible');
    });

    // ---- Submit ----
    submitBtn.addEventListener('click', async function () {
        if (!items.length) { toast('Додайте хоча б один товар', 'error'); return; }

        const name  = document.getElementById('f-name').value.trim();
        const phone = document.getElementById('f-phone').value.trim();
        const email = document.getElementById('f-email').value.trim();
        const delivery = document.getElementById('f-delivery').value;
        const payment  = document.getElementById('f-payment').value;
        const status   = document.getElementById('f-status').value;

        if (name.length < 2)  { toast('Введіть імʼя клієнта', 'error'); return; }
        if (phone === '')      { toast('Введіть номер телефону', 'error'); return; }
        if (delivery === '')   { toast('Оберіть спосіб доставки', 'error'); return; }
        if (payment === '')    { toast('Оберіть спосіб оплати', 'error'); return; }

        const payload = {
            csrf:             CSRF,
            customer_name:    name,
            customer_phone:   phone,
            customer_email:   email,
            delivery_method:  delivery,
            delivery_city:    document.getElementById('f-city')?.value.trim() || '',
            delivery_warehouse: document.getElementById('f-warehouse')?.value.trim() || '',
            delivery_address: document.getElementById('f-address')?.value.trim() || '',
            payment_method:   payment,
            status:           status,
            comment:          document.getElementById('f-comment').value.trim(),
            items: items.map(it => ({
                product_id: it.product_id,
                qty:        it.qty,
                price:      it.price.toFixed(2),
            })),
        };

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження…';

        try {
            const res  = await fetch(STORE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (data.success) {
                toast('✓ ' + data.message, 'success');
                setTimeout(() => {
                    window.location.href = '/admin/orders/details/' + data.order_id;
                }, 900);
            } else {
                toast(data.message || 'Помилка збереження', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Оформити замовлення';
            }
        } catch {
            toast('Помилка мережі', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Оформити замовлення';
        }
    });

    // ---- Helpers ----
    function fmtPrice(n) {
        return Number(n).toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
