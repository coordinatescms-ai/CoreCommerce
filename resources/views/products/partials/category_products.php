<?php
$products = $products ?? [];
$pages = max(1, (int) ($pages ?? 1));
$page = max(1, (int) ($page ?? 1));
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
                        <img class="product-image category-product-image"
                             src="<?= htmlspecialchars($product['image']) ?>"
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
                        <?= number_format((float) ($product['price'] ?? 0), 2, '.', ' ') ?> грн
                    </div>

                    <form action="/cart/add/<?= (int) $product['id'] ?>" method="POST">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary"><?= __('add_to_cart') ?></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($pages > 1 && $category): ?>
    <nav class="category-pagination" aria-label="Category pagination">
        <?php
        $baseQuery = $_GET;
        for ($i = 1; $i <= $pages; $i++):
            $baseQuery['page'] = $i;
            $url = '/category/' . $category['slug'] . '?' . http_build_query($baseQuery);
            $isCurrent = $i === $page;
        ?>
            <a class="category-pagination-link<?= $isCurrent ? ' is-current' : '' ?>"
               href="<?= htmlspecialchars($url) ?>"
               data-page="<?= $i ?>"
               <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
