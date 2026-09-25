<?php
/**
 * @var array                              $products
 * @var array                              $categories
 * @var \App\Core\Pagination\Paginator     $pager
 * @var string                             $search
 * @var int|null                           $catId
 * @var string                             $visibility
 */

$hasFilters = $search !== '' || $catId !== null || $visibility !== 'all';
?>

<style>
/* Filters toolbar */
.prod-toolbar { display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;
                background:#fff; border:1px solid #e2e8f0; border-radius:10px;
                padding:.85rem 1.1rem; margin-bottom:1.1rem; }
.prod-search  { display:flex; align-items:center; gap:.45rem; flex:1; min-width:180px;
                border:1px solid #e2e8f0; border-radius:8px; padding:.42rem .75rem;
                background:#f8fafc; }
.prod-search i     { color:#94a3b8; font-size:.82rem; flex-shrink:0; }
.prod-search input { border:none; background:none; outline:none; font-size:.875rem; width:100%; color:#0f172a; }
.prod-select  { padding:.42rem .75rem; border:1px solid #e2e8f0; border-radius:8px;
                font-size:.875rem; color:#334155; background:#fff; cursor:pointer; }
.prod-filter-tabs { display:flex; gap:.3rem; }
.prod-tab     { padding:.38rem .85rem; border-radius:20px; font-size:.8rem; font-weight:600;
                text-decoration:none; color:#64748b; background:#f1f5f9;
                border:1px solid transparent; white-space:nowrap; transition:.15s; }
.prod-tab:hover   { background:#e2e8f0; }
.prod-tab.active  { background:#6366f1; color:#fff; }
.prod-tab.active.green { background:#10b981; }
.prod-tab.active.red   { background:#ef4444; }
.prod-reset   { display:inline-flex; align-items:center; justify-content:center;
                width:32px; height:32px; border-radius:7px; border:1px solid #e2e8f0;
                background:#fff; color:#94a3b8; text-decoration:none; font-size:.8rem; }
.prod-reset:hover { border-color:#ef4444; color:#ef4444; }

/* Pagination */
.pag-wrap  { display:flex; justify-content:space-between; align-items:center;
             padding:.9rem 1.1rem; border-top:1px solid #f1f5f9; flex-wrap:wrap; gap:.5rem; }
.pag-info  { font-size:.82rem; color:#64748b; }
.pag-links { display:flex; gap:.3rem; flex-wrap:wrap; }
.pag-btn   { display:inline-flex; align-items:center; justify-content:center;
             min-width:34px; height:34px; padding:0 .5rem; border-radius:7px;
             border:1px solid #e2e8f0; background:#fff; text-decoration:none;
             color:#334155; font-size:.82rem; font-weight:500; transition:.15s; }
.pag-btn:hover    { border-color:#6366f1; color:#6366f1; }
.pag-btn.active   { background:#6366f1; color:#fff; border-color:#6366f1; }
.pag-btn.pag-disabled { opacity:.38; pointer-events:none; }
.pag-dots  { display:inline-flex; align-items:center; padding:0 .35rem; color:#94a3b8; font-size:.85rem; }
</style>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string)$_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars((string)$_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><?= __('product_import_csv') ?></div>
    <div class="card-body">
        <form action="/admin/products/import-csv" method="POST" enctype="multipart/form-data"
              style="display:flex; gap:.75rem; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <div class="form-group" style="margin-bottom:0; min-width:280px;">
                <label for="products_csv"><?= __('products_csv_file') ?></label>
                <input id="products_csv" class="form-control" type="file" name="products_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> <?= __('products_import') ?></button>
        </form>
        <p style="margin-top:.75rem; color:#64748b; font-size:.85rem;">
            <?= __('products_format_hint') ?>
        </p>
    </div>
</div>

<div class="page-header">
    <h1 class="page-title">
        <?= __('products_manage') ?>
        <span style="margin-left:.5rem; background:#eff6ff; color:#3b82f6; font-size:.78rem;
                     font-weight:700; padding:2px 10px; border-radius:20px; vertical-align:middle;">
            <?= number_format($pager->total) ?>
        </span>
    </h1>
    <a href="/admin/products/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= __('product_new') ?>
    </a>
    <button type="button" class="btn btn-primary" id="priceBatchOpenBtn" style="background:#0f766e;">
        <i class="fas fa-coins"></i> <?= __('price_batch_open_btn') ?>
    </button>
</div>

<!-- Filters -->
<div class="prod-toolbar">
    <form method="GET" action="/admin/products"
          style="display:contents;">
        <div class="prod-search">
            <i class="fas fa-search"></i>
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="<?= __('products_search_placeholder') ?>">
        </div>

        <select name="category" class="prod-select" onchange="this.form.submit()">
            <option value=""><?= __('products_all_categories') ?></option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>"
                    <?= $catId === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="hidden" name="visibility" value="<?= htmlspecialchars($visibility) ?>">
        <input type="hidden" name="page" value="1">

        <button type="submit" class="btn btn-primary" style="padding:.42rem .9rem; font-size:.875rem;">
            <i class="fas fa-search"></i>
        </button>
    </form>

    <div class="prod-filter-tabs">
        <a href="<?= '/admin/products?' . http_build_query(array_filter(['search' => $search, 'category' => $catId], fn($v) => $v !== null && $v !== '')) ?>"
           class="prod-tab <?= $visibility === 'all' ? 'active' : '' ?>"><?= __('products_all') ?></a>
        <a href="<?= '/admin/products?' . http_build_query(array_filter(['search' => $search, 'category' => $catId, 'visibility' => 'visible'], fn($v) => $v !== null && $v !== '')) ?>"
           class="prod-tab <?= $visibility === 'visible' ? 'active green' : '' ?>">
            <i class="fas fa-eye" style="font-size:.72rem;"></i> <?= __('products_visible') ?>
        </a>
        <a href="<?= '/admin/products?' . http_build_query(array_filter(['search' => $search, 'category' => $catId, 'visibility' => 'hidden'], fn($v) => $v !== null && $v !== '')) ?>"
           class="prod-tab <?= $visibility === 'hidden' ? 'active red' : '' ?>">
            <i class="fas fa-eye-slash" style="font-size:.72rem;"></i> <?= __('products_hidden') ?>
        </a>
    </div>

    <?php if ($hasFilters): ?>
        <a href="/admin/products" class="prod-reset" title="<?= __('reset') ?>">
            <i class="fas fa-times"></i>
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #eee; text-align:left;">
                    <th style="padding:1rem;"><?= __('products_product') ?></th>
                    <th style="padding:1rem;"><?= __('products_category') ?></th>
                    <th style="padding:1rem;"><?= __('products_price') ?></th>
                    <th style="padding:1rem;"><?= __('products_quantity') ?></th>
                    <th style="padding:1rem;"><?= __('products_slug') ?></th>
                    <th style="padding:1rem; text-align:right;"><?= __('products_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" style="padding:2.5rem; text-align:center; color:#94a3b8;">
                            <i class="fas fa-box-open" style="font-size:1.5rem; display:block; margin-bottom:.5rem;"></i>
                            <?= $hasFilters ? __('nothing_found') : __('products_empty')  ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:1rem;">
                                <div style="display:flex; gap:.75rem; align-items:center;">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= htmlspecialchars(product_image_variant_path((string)$product['image'], 'thumb')) ?>"
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             style="width:52px; height:52px; border-radius:6px; object-fit:cover; border:1px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div style="width:52px; height:52px; border-radius:6px; display:flex; align-items:center; justify-content:center; background:#f8fafc; border:1px solid #e2e8f0; color:#94a3b8; flex-shrink:0;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($product['name']) ?></strong>
                                        <div style="font-size:.8rem; color:#64748b;">
                                            ID: <?= (int)$product['id'] ?>
                                            <?php if (!empty($product['sku'])): ?>
                                                &nbsp;·&nbsp; SKU: <?= htmlspecialchars($product['sku']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!(int)($product['is_visible'] ?? 1)): ?>
                                            <span style="font-size:.72rem; background:#fee2e2; color:#991b1b; padding:1px 7px; border-radius:20px; font-weight:600;">
                                                <?= __('products_hidden_badge') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:1rem;">
                                <?php if (!empty($product['category_name'])): ?>
                                    <a href="<?= '/admin/products?' . http_build_query(['category' => $product['category_id']]) ?>"
                                       style="color:#6366f1; text-decoration:none; font-size:.875rem;">
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#94a3b8;"><?= __('products_no_category') ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:1rem; font-weight:600;">
                                <?= format_price((float)$product['price']) ?>
                            </td>
                            <td style="padding:1rem;">
                                <a href="/admin/stocks?sku=<?= urlencode((string)($product['sku'] ?? '')) ?>"
                                   style="font-weight:600; color:#2563eb; text-decoration:none;">
                                    <?= (int)($product['stock_quantity'] ?? 0) ?> <?= __('products_pieces') ?>
                                </a>
                            </td>
                            <td style="padding:1rem; color:#64748b; font-size:.85rem;">
                                /product/<?= htmlspecialchars($product['slug']) ?>
                            </td>
                            <td style="padding:1rem; text-align:right; white-space:nowrap;">
                                <a href="/admin/products/show/<?= (int)$product['id'] ?>"
                                   class="btn btn-outline" style="border:1px solid #ddd; color:#0f766e;" title="<?= __('preview') ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/admin/products/edit/<?= (int)$product['id'] ?>"
                                   class="btn btn-outline" style="border:1px solid #ddd; color:#2563eb;" title="<?= __('edit') ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="/admin/products/delete/<?= (int)$product['id'] ?>"
                                      method="POST" style="display:inline-block; margin:0;"
                                      onsubmit="return confirm('<?= sprintf(__('products_delete_confirm'), htmlspecialchars(addslashes($product['name']))) ?>')">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <button type="submit" class="btn btn-outline"
                                            style="border:1px solid #ddd; color:#ef4444;" title="<?= __('delete') ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div>
        <?= $pager->render(['show_info' => true, 'showing_text' => __('pagination_showing')]) ?>
    </div>
</div>

<style>
.pb-modal { position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:1000; }
.pb-modal.open { display:flex; }
.pb-modal__dialog { background:#fff; width:min(640px,95vw); max-height:92vh; overflow:auto; border-radius:12px; padding:20px 22px; }
.pb-modal__header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
.pb-modal__close { background:none; border:none; font-size:1.3rem; color:#94a3b8; cursor:pointer; line-height:1; }
.pb-section { margin-bottom:1.1rem; }
.pb-section-title { font-size:.8rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.03em; margin-bottom:.5rem; }
.pb-mode-tabs { display:flex; gap:.4rem; flex-wrap:wrap; }
.pb-mode-tab { flex:1; min-width:140px; padding:.55rem .7rem; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc; cursor:pointer; text-align:center; font-size:.82rem; font-weight:600; color:#475569; }
.pb-mode-tab.active { background:#0f766e; border-color:#0f766e; color:#fff; }
.pb-row { display:flex; gap:.6rem; align-items:center; flex-wrap:wrap; }
.pb-row .form-control { flex:1; min-width:120px; }
.pb-preview { margin-top:.9rem; padding:.7rem .9rem; border-radius:8px; background:#f0fdfa; border:1px solid #ccfbf1; color:#0f766e; font-size:.85rem; font-weight:600; }
.pb-preview.empty { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
.pb-actions { display:flex; justify-content:space-between; align-items:center; margin-top:1.3rem; gap:.6rem; flex-wrap:wrap; }
.pb-actions a { font-size:.82rem; color:#64748b; text-decoration:none; }
.pb-actions a:hover { color:#0f766e; }
</style>

<div class="pb-modal" id="priceBatchModal">
    <div class="pb-modal__dialog">
        <div class="pb-modal__header">
            <h2 style="margin:0; font-size:1.15rem;"><?= __('price_batch_title') ?></h2>
            <button type="button" class="pb-modal__close" id="priceBatchCloseBtn">&times;</button>
        </div>

        <div id="priceBatchAlert" style="display:none; margin-bottom:1rem;" class="alert alert-error"></div>

        <div class="pb-section">
            <div class="pb-section-title"><?= __('price_batch_mode_section') ?></div>
            <div class="pb-mode-tabs">
                <div class="pb-mode-tab active" data-mode="percent"><?= __('price_batch_mode_percent') ?></div>
                <div class="pb-mode-tab" data-mode="fixed"><?= __('price_batch_mode_fixed') ?></div>
                <div class="pb-mode-tab" data-mode="currency"><?= __('price_batch_mode_currency') ?></div>
            </div>
        </div>

        <div class="pb-section" id="pbValueSection">
            <div class="pb-row">
                <select class="form-control" id="pbDirection" style="max-width:170px;">
                    <option value="increase"><?= __('price_batch_direction_increase') ?></option>
                    <option value="decrease"><?= __('price_batch_direction_decrease') ?></option>
                </select>
                <input type="number" min="0" step="0.01" class="form-control" id="pbValue" placeholder="0">
                <span id="pbValueUnit" style="color:#64748b; font-size:.85rem;">%</span>
            </div>
        </div>

        <div class="pb-section" id="pbCurrencySection" style="display:none;">
            <label for="pbFromCurrency" style="display:block; font-size:.82rem; color:#64748b; margin-bottom:.3rem;">
                <?= __('price_batch_from_currency_label') ?>
            </label>
            <select class="form-control" id="pbFromCurrency"></select>
            <p style="margin:.5rem 0 0; font-size:.78rem; color:#94a3b8;"><?= __('price_batch_from_currency_hint') ?></p>
        </div>

        <div class="pb-section">
            <label style="display:flex; align-items:center; gap:.4rem; font-size:.85rem; color:#475569; font-weight:normal;">
                <input type="checkbox" id="pbRoundPrice">
                <?= __('price_batch_round_price') ?>
            </label>
            <p style="margin:.35rem 0 0 1.6rem; font-size:.78rem; color:#94a3b8;"><?= __('price_batch_round_price_hint') ?></p>
        </div>

        <div class="pb-section">
            <div class="pb-section-title"><?= __('price_batch_scope_section') ?></div>
            <div class="pb-row" style="margin-bottom:.5rem;">
                <select class="form-control" id="pbCategory">
                    <option value=""><?= __('price_batch_all_categories') ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control" id="pbVendor">
                    <option value=""><?= __('price_batch_all_vendors') ?></option>
                </select>
            </div>
            <label style="display:flex; align-items:center; gap:.4rem; font-size:.85rem; color:#475569; font-weight:normal;">
                <input type="checkbox" id="pbIncludeSubcategories">
                <?= __('price_batch_include_subcategories') ?>
            </label>
        </div>


        <div class="pb-preview" id="pbPreview"><?= __('price_batch_preview_loading') ?></div>

        <div class="pb-actions">
            <a href="/admin/products/price-batch/history"><i class="fas fa-clock-rotate-left"></i> <?= __('price_batch_history_link') ?></a>
            <div style="display:flex; gap:.5rem;">
                <button type="button" class="btn btn-outline" id="priceBatchCancelBtn"><?= __('price_batch_cancel_btn') ?></button>
                <button type="button" class="btn btn-primary" id="priceBatchApplyBtn" style="background:#0f766e;"><?= __('price_batch_apply_btn') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('priceBatchModal');
    const openBtn = document.getElementById('priceBatchOpenBtn');
    const closeBtn = document.getElementById('priceBatchCloseBtn');
    const cancelBtn = document.getElementById('priceBatchCancelBtn');
    const applyBtn = document.getElementById('priceBatchApplyBtn');
    const alertBox = document.getElementById('priceBatchAlert');
    const preview = document.getElementById('pbPreview');

    const modeTabs = document.querySelectorAll('.pb-mode-tab');
    const valueSection = document.getElementById('pbValueSection');
    const currencySection = document.getElementById('pbCurrencySection');
    const valueUnit = document.getElementById('pbValueUnit');
    const directionSelect = document.getElementById('pbDirection');
    const valueInput = document.getElementById('pbValue');
    const fromCurrencySelect = document.getElementById('pbFromCurrency');
    const categorySelect = document.getElementById('pbCategory');
    const vendorSelect = document.getElementById('pbVendor');
    const includeSubcategories = document.getElementById('pbIncludeSubcategories');
    const roundPriceCheckbox = document.getElementById('pbRoundPrice');

    let currentMode = 'percent';
    let optionsLoaded = false;
    let previewTimer = null;
    let currentCount = 0;

    const csrf = <?= json_encode($_SESSION['csrf'] ?? '') ?>;

    const showAlert = (msg) => {
        alertBox.textContent = msg;
        alertBox.style.display = 'block';
    };
    const hideAlert = () => { alertBox.style.display = 'none'; };

    const openModal = () => {
        modal.classList.add('open');
        hideAlert();
        if (!optionsLoaded) loadFormOptions();
        schedulePreview();
    };
    const closeModal = () => modal.classList.remove('open');

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    const loadFormOptions = async () => {
        try {
            const res = await fetch('/admin/products/price-batch/form-options', {headers: {'X-Requested-With': 'XMLHttpRequest'}});
            const data = await res.json();
            if (!data.success) return;

            data.vendors.forEach((v) => {
                const opt = document.createElement('option');
                opt.value = v; opt.textContent = v;
                vendorSelect.appendChild(opt);
            });

            data.currencies.forEach((c) => {
                const opt = document.createElement('option');
                opt.value = c.code;
                opt.textContent = c.code + ' (' + c.symbol + ')' + (c.is_active == 1 ? ' — ' + window.LANG.price_batch_main_currency_tag : '');
                fromCurrencySelect.appendChild(opt);
            });

            optionsLoaded = true;
        } catch (e) { /* noop */ }
    };

    modeTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            modeTabs.forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');
            currentMode = tab.getAttribute('data-mode');

            valueSection.style.display = currentMode === 'currency' ? 'none' : 'block';
            currencySection.style.display = currentMode === 'currency' ? 'block' : 'none';
            valueUnit.textContent = currentMode === 'percent' ? '%' : window.CURRENCY_SYMBOL;

            schedulePreview();
        });
    });

    const schedulePreview = () => {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(fetchPreviewCount, 350);
    };

    const fetchPreviewCount = async () => {
        preview.className = 'pb-preview';
        preview.textContent = window.LANG.price_batch_preview_loading;

        const qs = new URLSearchParams({
            category_id: categorySelect.value,
            include_subcategories: includeSubcategories.checked ? '1' : '',
            vendor: vendorSelect.value,
        });

        try {
            const res = await fetch('/admin/products/price-batch/preview-count?' + qs.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}});
            const data = await res.json();
            currentCount = data.count || 0;
            if (currentCount === 0) {
                preview.className = 'pb-preview empty';
                preview.textContent = window.LANG.price_batch_preview_zero;
            } else {
                preview.className = 'pb-preview';
                preview.textContent = window.LANG.price_batch_preview_count.replace('%d', currentCount);
            }
        } catch (e) {
            preview.textContent = window.LANG.price_batch_network_error;
        }
    };

    [categorySelect, vendorSelect, includeSubcategories].forEach((el) => {
        el.addEventListener('change', schedulePreview);
    });

    applyBtn.addEventListener('click', async () => {
        hideAlert();

        if (currentCount === 0) {
            showAlert(window.LANG.price_batch_preview_zero);
            return;
        }
        if (currentMode !== 'currency' && (!valueInput.value || parseFloat(valueInput.value) <= 0)) {
            showAlert(window.LANG.price_batch_invalid_value);
            return;
        }

        if (!confirm(window.LANG.price_batch_confirm.replace('%d', currentCount))) return;

        applyBtn.disabled = true;
        applyBtn.textContent = window.LANG.price_batch_applying;

        const body = new URLSearchParams({
            csrf,
            mode: currentMode,
            category_id: categorySelect.value,
            include_subcategories: includeSubcategories.checked ? '1' : '',
            vendor: vendorSelect.value,
            value: valueInput.value || '0',
            direction: directionSelect.value,
            from_currency: fromCurrencySelect.value || '',
            round_price: roundPriceCheckbox.checked ? '1' : '',
        });

        try {
            const res = await fetch('/admin/products/price-batch/apply', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body,
            });
            const data = await res.json();

            if (!data.success) {
                showAlert(data.message || window.LANG.price_batch_network_error);
                applyBtn.disabled = false;
                applyBtn.textContent = window.LANG.price_batch_apply_btn;
                return;
            }

            alert(data.message);
            window.location.reload();
        } catch (e) {
            showAlert(window.LANG.price_batch_network_error);
            applyBtn.disabled = false;
            applyBtn.textContent = window.LANG.price_batch_apply_btn;
        }
    });
})();
</script>
