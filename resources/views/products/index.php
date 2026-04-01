<h2><?= __('products') ?></h2>
<?php if (empty($products)): ?>
    <p><?= __('no_products_found') ?></p>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
        <?php foreach($products as $p): ?>
            <div style="border: 1px solid #ddd; padding: 1rem; border-radius: 4px;">
                <?php if (!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 100%; height: auto; margin-bottom: 0.5rem;">
                <?php endif; ?>
                <h3><a href="/product/<?= htmlspecialchars($p['slug']) ?>" style="text-decoration: none; color: #333;"><?= htmlspecialchars($p['name']) ?></a></h3>
                <p><strong><?= htmlspecialchars($p['price']) ?> грн</strong></p>
                <a href="/cart/add/<?= $p['id'] ?>" style="background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; display: inline-block;"><?= __('add_to_cart') ?></a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>