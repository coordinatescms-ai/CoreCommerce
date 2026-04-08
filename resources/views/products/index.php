<div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: 1rem;">
    <h2 style="margin: 0;"><?= __('products') ?></h2>
    <div style="font-size: 0.9rem; color: #64748b;">
        Знайдено: <strong><?= (int)($total ?? count($products ?? [])); ?></strong>
    </div>
</div>

<div style="display:grid; grid-template-columns: 300px 1fr; gap: 1.25rem; align-items: start;">
    <aside>
        <?php
            $categorySlug = '';
            include __DIR__ . '/../components/product_filters.php';
        ?>
    </aside>

    <section>
        <?php if (empty($products)): ?>
            <p><?= __('no_products_found') ?></p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
                <?php foreach($products as $p): ?>
                    <div style="border: 1px solid #ddd; padding: 1rem; border-radius: 4px; background: #fff;">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 100%; height: auto; margin-bottom: 0.5rem;">
                        <?php endif; ?>
                        <h3 style="margin-top: 0.25rem;"><a href="/product/<?= htmlspecialchars($p['slug']) ?>" style="text-decoration: none; color: #333;"><?= htmlspecialchars($p['name']) ?></a></h3>
                        <p><strong><?= htmlspecialchars($p['price']) ?> грн</strong></p>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="/cart/add/<?= (int)$p['id'] ?>" style="background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; display: inline-block; text-align: center;"><?= __('add_to_cart') ?></a>
                            <?php if (isset($p['stock'])): ?>
                                <small style="color: #64748b; font-size: 0.8rem;"><?= __('in_stock') ?>: <?= (int)$p['stock'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
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
