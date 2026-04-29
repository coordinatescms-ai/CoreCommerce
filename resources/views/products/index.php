<div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: 1rem; flex-wrap: wrap;">
    <h2 style="margin: 0;"><?= __('products_popular_title') ?></h2>
    <div style="font-size: 0.9rem; color: #64748b;">
        <?= __('products_found_label') ?>: <strong><?= (int)($total ?? count($products ?? [])); ?></strong>
    </div>
</div>

<div style="display:grid; grid-template-columns: 300px 1fr; gap: 1.25rem; align-items: start;">
    <aside>
        <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff;">
            <h3 style="margin: 0 0 0.75rem;"><?= __('categories') ?></h3>
            <?php if (empty($categories ?? [])): ?>
                <p style="margin: 0; color: #64748b;"><?= __('categories_not_found') ?></p>
            <?php else: ?>
                <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach (($categories ?? []) as $category): ?>
                        <li>
                            <a href="/category/<?= htmlspecialchars($category['slug']) ?>" style="text-decoration: none; color: #111827;">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>

    <section>
        <?php if (empty($products)): ?>
            <p><?= __('no_products_found') ?></p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
                <?php foreach($products as $product): ?>
                    <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff; position: relative;">
                        <span style="position: absolute; top: 0.75rem; right: 0.75rem; display: inline-block; padding: 0.2rem 0.5rem; border-radius: 999px; background: #f59e0b; color: #111827; font-size: 0.75rem; font-weight: 700;">
                            <?= __('products_top_badge') ?>
                        </span>
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars(product_image_variant_path((string) $product['image'], 'medium')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                        <?php endif; ?>
                        <h3 style="margin: 0 0 0.5rem;">
                            <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                        </h3>
                        <p style="margin: 0 0 0.75rem;"><strong><?= htmlspecialchars($product['price']) ?> грн</strong></p>
                        <form action="/cart/add/<?= (int)$product['id'] ?>" method="POST" style="display: inline-block; margin: 0; width: 100%;">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                            <button type="submit" style="display: inline-block; padding: 0.5rem 0.85rem; background: #111827; color: #fff; text-decoration: none; border-radius: 0.45rem; border: 0; cursor: pointer; width: 100%;">
                                <?= __('add_to_cart') ?>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (($pages ?? 1) > 1): ?>
            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                <?php
                    $query = $_GET;
                    for ($i = 1; $i <= (int)$pages; $i++):
                        $query['page'] = $i;
                        $url = '/products?' . http_build_query($query);
                ?>
                    <a href="<?= htmlspecialchars($url); ?>"
                       style="padding: 0.45rem 0.75rem; border-radius: 4px; border: 1px solid #ddd; text-decoration:none; color: <?= $i == (int)$page ? '#fff' : '#334155'; ?>; background: <?= $i == (int)$page ? '#2563eb' : '#fff'; ?>;">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
@media (max-width: 992px) {
    div[style*="grid-template-columns: 300px 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
