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
                <label for="products_csv">CSV-файл</label>
                <input id="products_csv" class="form-control" type="file" name="products_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Імпортувати</button>
        </form>
        <p style="margin-top:.75rem; color:#64748b; font-size:.85rem;">
            Формат: <code>sku,name,price,quantity,description,category</code>
        </p>
    </div>
</div>

<div class="page-header">
    <h1 class="page-title">
        Управління товарами
        <span style="margin-left:.5rem; background:#eff6ff; color:#3b82f6; font-size:.78rem;
                     font-weight:700; padding:2px 10px; border-radius:20px; vertical-align:middle;">
            <?= number_format($pager->total) ?>
        </span>
    </h1>
    <a href="/admin/products/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= __('product_new') ?>
    </a>
</div>

<!-- Filters -->
<div class="prod-toolbar">
    <form method="GET" action="/admin/products"
          style="display:contents;">
        <div class="prod-search">
            <i class="fas fa-search"></i>
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Назва або SKU…">
        </div>

        <select name="category" class="prod-select" onchange="this.form.submit()">
            <option value="">Усі категорії</option>
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
           class="prod-tab <?= $visibility === 'all' ? 'active' : '' ?>">Усі</a>
        <a href="<?= '/admin/products?' . http_build_query(array_filter(['search' => $search, 'category' => $catId, 'visibility' => 'visible'], fn($v) => $v !== null && $v !== '')) ?>"
           class="prod-tab <?= $visibility === 'visible' ? 'active green' : '' ?>">
            <i class="fas fa-eye" style="font-size:.72rem;"></i> Видимі
        </a>
        <a href="<?= '/admin/products?' . http_build_query(array_filter(['search' => $search, 'category' => $catId, 'visibility' => 'hidden'], fn($v) => $v !== null && $v !== '')) ?>"
           class="prod-tab <?= $visibility === 'hidden' ? 'active red' : '' ?>">
            <i class="fas fa-eye-slash" style="font-size:.72rem;"></i> Приховані
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
                    <th style="padding:1rem;">Товар</th>
                    <th style="padding:1rem;">Категорія</th>
                    <th style="padding:1rem;">Ціна</th>
                    <th style="padding:1rem;">Кількість</th>
                    <th style="padding:1rem;">Slug</th>
                    <th style="padding:1rem; text-align:right;">Дії</th>
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
                                                прихований
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
                                    <span style="color:#94a3b8;">Без категорії</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:1rem; font-weight:600;">
                                <?= format_price((float)$product['price']) ?>
                            </td>
                            <td style="padding:1rem;">
                                <a href="/admin/stocks?sku=<?= urlencode((string)($product['sku'] ?? '')) ?>"
                                   style="font-weight:600; color:#2563eb; text-decoration:none;">
                                    <?= (int)($product['stock_quantity'] ?? 0) ?> шт.
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
                                      onsubmit="return confirm('Видалити товар «<?= htmlspecialchars(addslashes($product['name'])) ?>»?')">
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
        <?= $pager->render(['show_info' => true]) ?>
    </div>
</div>
