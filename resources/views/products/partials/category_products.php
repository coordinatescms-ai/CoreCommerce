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
    <div class="products-grid category-products-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card category-product-card">
                <a class="category-product-image-link" href="/product/<?= htmlspecialchars($product['slug']) ?>">
                    <?php if (!empty($product['image'])): ?>
                        <?php $categoryCardImage = product_image_variant_path((string) $product['image'], 'medium'); ?>
                        <img class="product-image category-product-image"
                             src="<?= htmlspecialchars($categoryCardImage) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="category-product-image category-product-image-placeholder"><?= __('products') ?></div>
                    <?php endif; ?>
                </a>

                <div class="product-content category-product-content">
                    <h3 class="product-name category-product-name">
                        <a href="/product/<?= htmlspecialchars($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>

                    <div class="product-price category-product-price">
                        <?= format_price((float) ($product['price'] ?? 0)) ?>
                    </div>

                    <form action="/cart/add/<?= (int) $product['id'] ?>" method="POST">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/products') ?>">
                        <button type="submit" class="btn btn-primary"><?= __('add_to_cart') ?></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($pager !== null && $pager->hasPages()): ?>
    <nav class="category-pagination" aria-label="Pagination">
        <?= $pager->render(['show_info' => false]) ?>
    </nav>
<?php endif; ?>
