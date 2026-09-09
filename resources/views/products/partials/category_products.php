<?php
/**
 * @var array                               $products
 * @var \App\Core\Pagination\Paginator|null $pager
 * @var array|null                          $category
 */
$products = $products ?? [];
$pager    = $pager    ?? null;
$category = $category ?? null;
?>

<?php if (empty($products)): ?>
    <div class="category-empty-state"><?= __('no_products_found') ?></div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
        <?php foreach ($products as $product): ?>
            <?php $outOfStock = render_stock_badge($product) !== ''; ?>
            <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff; display: flex; flex-direction: column;">
                <?php if (!empty($product['image'])): ?>
                    <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;">
                        <img src="<?= htmlspecialchars(product_image_variant_path((string) $product['image'], 'medium')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem;<?= $outOfStock ? ' opacity: 0.55;' : '' ?>">
                    </a>
                <?php endif; ?>
                <h3 style="margin: 0 0 0.5rem;">
                    <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                </h3>
                <?php if ($outOfStock): ?>
                    <p style="margin: 0 0 0.5rem;"><?= render_stock_badge($product) ?></p>
                <?php endif; ?>
                <p style="margin: 0 0 0.75rem; margin-top: auto;"><?= render_product_price($product) ?></p>
                <form action="/cart/add/<?= (int)$product['id'] ?>" method="POST" style="display: inline-block; margin: 0;">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/products') ?>">
                    <button type="submit" <?= $outOfStock ? 'disabled' : '' ?> style="display: inline-block; padding: 0.5rem 0.85rem; background: <?= $outOfStock ? '#9ca3af' : '#111827' ?>; color: #fff; text-decoration: none; border-radius: 0.45rem; border: 0; cursor: <?= $outOfStock ? 'not-allowed' : 'pointer' ?>;">
                        <?= $outOfStock ? __('out_of_stock') : __('add_to_cart') ?>
                    </button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($pager !== null && $pager->hasPages()): ?>
    <nav class="category-pagination" aria-label="Pagination" style="margin-top: 2rem;">
        <?= $pager->render(['show_info' => false]) ?>
    </nav>
<?php endif; ?>
